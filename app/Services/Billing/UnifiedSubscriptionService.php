<?php
declare(strict_types=1);

namespace App\Services\Billing;

use App\Services\Licensing\SchoolLicenseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UnifiedSubscriptionService
{
    public function __construct(
        private readonly SchoolLicenseService $licenses,
    ) {
    }

    /**
     * Obtiene la suscripción comercial y los módulos
     * efectivos de una escuela.
     *
     * Prioridad:
     *
     * 1. Escuela y licencia vigentes.
     * 2. Núcleo obligatorio.
     * 3. Suspensión explícita del entitlement.
     * 4. Complementos contratados.
     * 5. Módulos incluidos en el plan.
     * 6. Entitlements independientes gratuitos.
     */
    public function forSchool(int $schoolId): array
    {
        $license = $this->licenses->current($schoolId);

        $licenseActive = $this->licenses->isActive($schoolId);

        $plan = null;

        if ($license !== null && $license->subscription_plan_id !== null) {
            $plan = DB::table('subscription_plans')
                ->where('id', $license->subscription_plan_id)
                ->first();
        }

        $today = CarbonImmutable::today()->toDateString();

        /*
         * Contrato comercial vigente.
         */
        $contract = DB::table('school_contracts')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('starts_on', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        /*
         * Módulos incluidos en el plan principal.
         *
         * En el modelo actual:
         * - core
         * - attendance (PaseLista)
         */
        $includedModules = collect();

        if ($plan !== null) {
            $includedModules = DB::table('subscription_plan_modules')
                ->where('subscription_plan_id', $plan->id)
                ->where('is_included', true)
                ->get()
                ->keyBy('service_module_id');
        }

        /*
         * Derechos explícitos de la escuela.
         */
        $entitlements = DB::table('school_module_entitlements')
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('service_module_id');

        /*
         * Líneas del contrato vigente.
         */
        $contractModules = $contract !== null
            ? DB::table('school_contract_modules')
                ->where('school_id', $schoolId)
                ->where('school_contract_id', $contract->id)
                ->get()
                ->keyBy('service_module_id')
            : collect();

        /*
         * Catálogo general de módulos.
         */
        $catalog = DB::table('service_modules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $modules = $catalog
            ->map(function ($module) use (
                $licenseActive,
                $includedModules,
                $entitlements,
                $contractModules,
                $today
            ): array {
                $moduleId = (int) $module->id;

                $included = $includedModules->get($moduleId);
                $entitlement = $entitlements->get($moduleId);
                $contractLine = $contractModules->get($moduleId);

                $isCore = (bool) $module->is_core;
                $isIncluded = $included !== null;

                $active = false;
                $source = null;

                /*
                 * Una licencia inválida bloquea los módulos.
                 */
                if (
                    $licenseActive
                    && $module->status === 'active'
                ) {
                    /*
                     * El núcleo es obligatorio.
                     */
                    if ($isCore) {
                        $active = true;
                        $source = 'core';
                    } elseif ($entitlement !== null) {
                        /*
                         * Una decisión explícita tiene prioridad
                         * sobre los módulos incluidos en el plan.
                         */
                        if (
                            $entitlement->status === 'active'
                            && $this->dateIsValid(
                                $entitlement->starts_on,
                                $entitlement->expires_on,
                                $today
                            )
                        ) {
                            /*
                             * Complemento asociado a un contrato.
                             */
                            if (
                                $entitlement->school_contract_module_id !== null
                            ) {
                                if (
                                    $contractLine !== null
                                    && (int) $contractLine->id ===
                                        (int) $entitlement->school_contract_module_id
                                    && $contractLine->status === 'active'
                                    && $this->dateIsValid(
                                        $contractLine->starts_on,
                                        $contractLine->ends_on,
                                        $today
                                    )
                                ) {
                                    $active = true;
                                    $source = 'contract';
                                }
                            } else {
                                /*
                                 * Entitlement gratuito o independiente.
                                 */
                                $active = true;
                                $source = $isIncluded
                                    ? 'plan'
                                    : 'entitlement';
                            }
                        }
                    } elseif ($isIncluded) {
                        /*
                         * Incluido en el precio del plan base.
                         *
                         * No genera un cargo adicional.
                         */
                        $active = true;
                        $source = 'plan';
                    }
                }

                return [
                    'id' => $moduleId,
                    'code' => (string) $module->code,
                    'name' => (string) $module->name,
                    'description' => $module->description,

                    'isCore' => $isCore,
                    'included' => $isIncluded,

                    'isPrimary' => $included !== null
                        ? (bool) $included->is_primary
                        : false,

                    'active' => $active,
                    'source' => $source,

                    'catalogStatus' => (string) $module->status,

                    'entitlementStatus' => $entitlement?->status,

                    'contractLineId' => $contractLine?->id,
                    'contractStatus' => $contractLine?->status,

                    'contractPrice' => $contractLine?->unit_price,
                    'pricePeriod' => $contractLine?->price_period,

                    'startsOn' => $contractLine?->starts_on,
                    'endsOn' => $contractLine?->ends_on,
                ];
            })
            ->values()
            ->all();

        /*
         * Precio base:
         * es el precio pactado en school_licenses,
         * NO la suma de complementos.
         */
        $basePrice = $license?->contract_price;

        /*
         * Complementos activos con la misma periodicidad
         * de la licencia principal.
         *
         * El total es informativo.
         * No genera facturas ni movimientos.
         */
        $recurringAddons = '0.00';

        if ($contract !== null && $license !== null) {
            $priceSum = DB::table('school_contract_modules')
                ->where('school_id', $schoolId)
                ->where('school_contract_id', $contract->id)
                ->where('status', 'active')
                ->where('price_period', $license->billing_cycle)
                ->where('starts_on', '<=', $today)
                ->where(function ($query) use ($today): void {
                    $query->whereNull('ends_on')
                        ->orWhere('ends_on', '>=', $today);
                })
                ->sum('unit_price');

            $recurringAddons = number_format(
                (float) $priceSum,
                2,
                '.',
                ''
            );
        }

        $basePriceDecimal = $basePrice !== null
            ? number_format((float) $basePrice, 2, '.', '')
            : '0.00';

        $estimatedTotal = number_format(
            (float) $basePriceDecimal + (float) $recurringAddons,
            2,
            '.',
            ''
        );

        $wallet = DB::table('school_ai_wallets')
            ->where('school_id', $schoolId)
            ->first(['balance_credits']);

        return [
            'schoolId' => $schoolId,

            'licenseId' => $license?->id,
            'licenseActive' => $licenseActive,
            'licenseStatus' => $license?->status,

            'plan' => $plan !== null
                ? [
                    'id' => (int) $plan->id,
                    'code' => (string) $plan->code,
                    'name' => (string) $plan->name,
                    'monthlyPrice' => $plan->monthly_price,
                    'annualPrice' => $plan->annual_price,
                ]
                : null,

            'billingCycle' => $license?->billing_cycle,

            'startsOn' => $license?->starts_at?->toDateString(),
            'expiresOn' => $license?->expires_at?->toDateString(),

            'contractId' => $contract?->id,
            'contractNumber' => $contract?->contract_number,

            'currency' => $license?->currency ?: 'MXN',

            'baseContractPrice' => $basePrice,

            'recurringAddons' => $recurringAddons,

            'estimatedRecurringTotal' => $estimatedTotal,

            'aiWalletCredits' => (int) (
                $wallet?->balance_credits ?? 0
            ),

            'modules' => $modules,
        ];
    }

    /**
     * Comprueba si un módulo está activo.
     */
    public function moduleActive(
        int $schoolId,
        string $moduleCode
    ): bool {
        $subscription = $this->forSchool($schoolId);

        foreach ($subscription['modules'] as $module) {
            if ($module['code'] === $moduleCode) {
                return (bool) $module['active'];
            }
        }

        return false;
    }

    /**
     * Valida vigencias inclusivas.
     */
    private function dateIsValid(
        mixed $startsOn,
        mixed $endsOn,
        string $today
    ): bool {
        $start = $startsOn !== null
            ? CarbonImmutable::parse((string) $startsOn)
                ->toDateString()
            : null;

        $end = $endsOn !== null
            ? CarbonImmutable::parse((string) $endsOn)
                ->toDateString()
            : null;

        if ($start !== null && $start > $today) {
            return false;
        }

        if ($end !== null && $end < $today) {
            return false;
        }

        return true;
    }
}
