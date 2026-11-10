<?php
declare(strict_types=1);

namespace App\Services\Billing;

use App\Services\Licensing\SchoolLicenseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SchoolBillingService
{
    public function __construct(
        private readonly SchoolLicenseService $licenses,
        private readonly ProrationCalculator $proration,
    ) {
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'billing' => $message,
        ]);
    }

    public function activate(
        int $schoolId,
        int $moduleId,
        int $contractId,
        string $price,
        string $pricePeriod,
        int $actorId
    ): array {
        return DB::transaction(function () use (
            $schoolId,
            $moduleId,
            $contractId,
            $price,
            $pricePeriod,
            $actorId
        ): array {
            // Bloqueo por escuela para serializar activaciones.
            $school = DB::table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first(['id', 'status']);

            if (!$school || $school->status !== 'active') {
                $this->fail('La escuela no está activa.');
            }

            $license = $this->licenses->current($schoolId);

            if (!$license || !$this->licenses->isActive($schoolId)) {
                $this->fail('La escuela no tiene una suscripción vigente.');
            }

            if (
                !in_array(
                    (string) $license->billing_cycle,
                    ['monthly', 'annual'],
                    true
                )
            ) {
                $this->fail(
                    'El prorrateo automático requiere un ciclo mensual o anual.'
                );
            }

            if ($pricePeriod !== $license->billing_cycle) {
                $this->fail(
                    'El complemento debe utilizar la periodicidad del plan.'
                );
            }

            $module = DB::table('service_modules')
                ->where('id', $moduleId)
                ->where('status', 'active')
                ->first();

            if (!$module || (bool) $module->is_core) {
                $this->fail('El módulo no está disponible para contratación.');
            }

            // El catálogo es comercial; el precio pactado se valida en servidor.
            $pricingModel = (string) ($module->pricing_model ?? 'quote');
            if (!in_array($pricingModel, ['paid', 'free', 'quote'], true)) {
                $this->fail('Este módulo no se contrata individualmente.');
            }
            if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $price)) {
                $this->fail('El precio pactado no tiene un formato válido.');
            }
            if ($pricingModel === 'paid' && (float) $price <= 0) {
                $this->fail('Un módulo de pago requiere un precio mayor a cero.');
            }
            if ($pricingModel === 'free' && (float) $price !== 0.0) {
                $this->fail('Un módulo gratuito debe tener precio cero.');
            }

            $included = DB::table('subscription_plan_modules')
                ->where(
                    'subscription_plan_id',
                    $license->subscription_plan_id
                )
                ->where('service_module_id', $moduleId)
                ->where('is_included', true)
                ->exists();

            if ($included) {
                $this->fail(
                    'El módulo ya está incluido en el plan base. '
                    .'No debe cobrarse nuevamente.'
                );
            }

            $contract = DB::table('school_contracts')
                ->where('id', $contractId)
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$contract) {
                $this->fail('No existe un contrato comercial activo.');
            }
            if ($contract->billing_frequency !== $pricePeriod) {
                $this->fail('El contrato no coincide con la periodicidad de facturación.');
            }

            $today = CarbonImmutable::today();

            $contractStarts = CarbonImmutable::parse(
                $contract->starts_on
            )->startOfDay();

            if ($contractStarts->greaterThan($today)) {
                $this->fail('El contrato todavía no entra en vigor.');
            }

            $contractEnd = $contract->ends_on
                ? CarbonImmutable::parse($contract->ends_on)->startOfDay()
                : null;

            if ($contractEnd && $contractEnd->lessThan($today)) {
                $this->fail('El contrato está vencido.');
            }

            if (!$license->starts_at || !$license->expires_at) {
                $this->fail(
                    'La licencia debe tener inicio y vencimiento para prorratear.'
                );
            }

            $periodStart = CarbonImmutable::parse(
                $license->starts_at
            )->startOfDay();

            // expires_at es inclusivo en el licenciamiento existente.
            $periodEndExclusive = CarbonImmutable::parse(
                $license->expires_at
            )->startOfDay()->addDay();

            if (
                $today->lessThan($periodStart)
                || !$today->lessThan($periodEndExclusive)
            ) {
                $this->fail('No existe un período de facturación vigente.');
            }

            if (
                $contractEnd !== null
                && $contractEnd->lessThan(
                    $periodEndExclusive->subDay()
                )
            ) {
                $this->fail(
                    'El contrato vence antes del período actual. '
                    .'Ajusta su vigencia antes de activar el complemento.'
                );
            }

            $existingLine = DB::table('school_contract_modules')
                ->where('school_id', $schoolId)
                ->where('school_contract_id', $contractId)
                ->where('service_module_id', $moduleId)
                ->lockForUpdate()
                ->first();

            $entitlement = DB::table('school_module_entitlements')
                ->where('school_id', $schoolId)
                ->where('service_module_id', $moduleId)
                ->lockForUpdate()
                ->first();

            if (
                $entitlement
                && $entitlement->school_contract_module_id === null
            ) {
                $this->fail(
                    'El módulo tiene un derecho independiente. '
                    .'Debe revisarse antes de convertirlo a complemento de pago.'
                );
            }

            if (
                $entitlement
                && (
                    !$existingLine
                    || (int) $entitlement->school_contract_module_id
                        !== (int) $existingLine->id
                )
            ) {
                $this->fail(
                    'El módulo está vinculado a otra línea contractual.'
                );
            }

            if ($existingLine && $existingLine->unit_price !== null
                && abs((float) $existingLine->unit_price - (float) $price) > 0.001) {
                $this->fail('El precio existente no puede modificarse al reactivar. Registra primero una modificación comercial.');
            }

            if (
                $existingLine
                && $existingLine->status === 'active'
                && $entitlement
                && $entitlement->status === 'active'
            ) {
                $this->fail(
                    'El módulo ya está activo. '
                    .'No se generó un cargo adicional.'
                );
            }

            // La primera activación cobra solo los días pendientes.
            // En una reactivación del mismo período no duplicamos cargo.
            $chargeCents = $this->proration->calculate(
                $price,
                $periodStart->toDateString(),
                $periodEndExclusive->toDateString(),
                $today->toDateString()
            );

            $lineValues = [
                'status' => 'active',
                'unit_price' => $price,
                'price_period' => $pricePeriod,
                'starts_on' => $today->toDateString(),
                'ends_on' => $contractEnd?->toDateString(),
                'updated_at' => now(),
            ];

            if ($existingLine) {
                DB::table('school_contract_modules')
                    ->where('id', $existingLine->id)
                    ->update($lineValues);

                $lineId = (int) $existingLine->id;
            } else {
                $lineId = DB::table('school_contract_modules')
                    ->insertGetId([
                        'school_id' => $schoolId,
                        'school_contract_id' => $contractId,
                        'service_module_id' => $moduleId,
                        'is_primary' => false,
                        'limits_json' => null,
                        'features_json' => null,
                        'notes' => null,
                        ...$lineValues,
                        'created_at' => now(),
                    ]);
            }

            $rights = [
                'school_contract_module_id' => $lineId,
                'status' => 'active',
                'starts_on' => $today->toDateString(),
                'expires_on' => $contractEnd?->toDateString(),
                'grace_ends_on' => null,
                'updated_at' => now(),
            ];

            if ($entitlement) {
                DB::table('school_module_entitlements')
                    ->where('id', $entitlement->id)
                    ->update($rights);
            } else {
                DB::table('school_module_entitlements')
                    ->insert([
                        'school_id' => $schoolId,
                        'service_module_id' => $moduleId,
                        'limits_json' => null,
                        'features_json' => null,
                        ...$rights,
                        'created_at' => now(),
                    ]);
            }

            $key = sprintf(
                'module:%d:%d:%s',
                $schoolId,
                $moduleId,
                $periodStart->format('Ymd')
            );

            $previousCharge = DB::table('school_billing_charges')
                ->where('idempotency_key', $key)
                ->first(['id', 'amount']);

            $chargeCreated = false;

            if (!$previousCharge && $chargeCents > 0) {
                DB::table('school_billing_charges')
                    ->insert([
                        'school_id' => $schoolId,
                        'school_contract_id' => $contractId,
                        'service_module_id' => $moduleId,
                        'source_type' => 'module_activation',
                        'source_id' => $lineId,
                        'description' => 'Activación proporcional: '.$module->name,
                        'period_start' => $today->toDateString(),
                        'period_end' => $periodEndExclusive
                            ->subDay()
                            ->toDateString(),
                        'amount' => number_format(
                            $chargeCents / 100,
                            2,
                            '.',
                            ''
                        ),
                        'currency' => $license->currency ?: 'MXN',
                        'status' => 'pending',
                        'idempotency_key' => $key,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $chargeCreated = true;
            }

            return [
                'module' => $module->code,
                'active' => true,
                'line_id' => $lineId,
                'proration_cents' => $chargeCents,
                'charge_created' => $chargeCreated,
                'billing_cycle' => $license->billing_cycle,
                'next_full_period_price' => $price,
            ];
        }, 3);
    }

    public function suspend(
        int $schoolId,
        int $moduleId,
        int $contractId
    ): void {
        DB::transaction(function () use (
            $schoolId,
            $moduleId,
            $contractId
        ): void {
            DB::table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first();

            $module = DB::table('service_modules')
                ->where('id', $moduleId)
                ->first();

            if (!$module || (bool) $module->is_core) {
                $this->fail('El módulo no se puede suspender.');
            }

            $line = DB::table('school_contract_modules')
                ->where('school_id', $schoolId)
                ->where('school_contract_id', $contractId)
                ->where('service_module_id', $moduleId)
                ->lockForUpdate()
                ->first();

            if (!$line) {
                $this->fail(
                    'No existe una línea contractual para este módulo.'
                );
            }

            DB::table('school_contract_modules')
                ->where('id', $line->id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            DB::table('school_module_entitlements')
                ->where('school_id', $schoolId)
                ->where('service_module_id', $moduleId)
                ->where('school_contract_module_id', $line->id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            // No borrar cargos ni crear créditos automáticos.
            // Las devoluciones se gestionarán como ajustes auditados.
        }, 3);
    }
}
