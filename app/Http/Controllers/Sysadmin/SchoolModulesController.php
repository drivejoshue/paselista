<?php
declare(strict_types=1);

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Services\Billing\SchoolBillingService;
use App\Services\Billing\UnifiedSubscriptionService;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SchoolModulesController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $access,
        private readonly UnifiedSubscriptionService $subscription,
        private readonly SchoolBillingService $billing,
    ) {}

    public function index(int $school): View
    {
        $institution = DB::table('schools')->where('id', $school)->first(['id', 'name']);
        abort_unless($institution, 404);

        $subscription = $this->subscription->forSchool($school);
        $contracts = DB::table('school_contracts')->where('school_id', $school)
            ->orderByDesc('id')->get();
        $modules = DB::table('service_modules')->orderBy('sort_order')->orderBy('name')->get()
            ->map(function ($module) use ($school) {
                $module->contractLine = DB::table('school_contract_modules as cm')
                    ->join('school_contracts as c', 'c.id', '=', 'cm.school_contract_id')
                    ->where('cm.school_id', $school)->where('c.school_id', $school)
                    ->where('cm.service_module_id', $module->id)
                    ->orderByRaw("CASE WHEN cm.status = 'active' AND c.status = 'active' THEN 0 ELSE 1 END")
                    ->orderByDesc('cm.id')->select('cm.*')->first();
                $module->entitlement = DB::table('school_module_entitlements')
                    ->where('school_id', $school)->where('service_module_id', $module->id)->first();
                $module->accessSource = $this->access->accessSource($school, (string) $module->code);
                return $module;
            });
        $charges = DB::table('school_billing_charges')->where('school_id', $school)
            ->orderByDesc('id')->limit(12)->get();

        return view('sysadmin.schools.modules', compact(
            'institution', 'school', 'subscription', 'contracts', 'modules', 'charges'
        ));
    }

    /** Compatibility endpoint for the existing contract form. The one-click action below does not require it. */
    public function storeContract(Request $request, int $school): RedirectResponse
    {
        abort_unless(DB::table('schools')->where('id', $school)->exists(), 404);
        $data = $request->validate([
            'contract_number' => ['required', 'string', 'max:80', Rule::unique('school_contracts', 'contract_number')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'term_type' => ['required', Rule::in(['fixed', 'open_ended'])],
            'billing_frequency' => ['required', Rule::in(['monthly', 'annual'])],
            'payment_mode' => ['required', Rule::in(['invoice', 'prepaid', 'sponsored'])],
            'service_package_id' => ['nullable', 'integer', Rule::exists('service_packages', 'id')],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $subscription = $this->subscription->forSchool($school);
        $this->assertCompatibleSubscription($subscription);
        if ($data['billing_frequency'] !== $subscription['billingCycle']) {
            throw ValidationException::withMessages(['billing_frequency' => 'Debe coincidir con la licencia.']);
        }
        if ($data['term_type'] === 'fixed' && empty($data['ends_on'])) {
            throw ValidationException::withMessages(['ends_on' => 'Requerida para contrato fijo.']);
        }
        if ($data['term_type'] === 'open_ended' && !empty($data['ends_on'])) {
            throw ValidationException::withMessages(['ends_on' => 'Un contrato abierto no tiene vencimiento.']);
        }
        if ($data['starts_on'] > $subscription['expiresOn'] ||
            (!empty($data['ends_on']) && $data['ends_on'] < $subscription['expiresOn'])) {
            throw ValidationException::withMessages(['ends_on' => 'El contrato debe cubrir el período vigente.']);
        }
        DB::transaction(function () use ($school, $data): void {
            DB::table('schools')->where('id', $school)->lockForUpdate()->first();
            if (DB::table('school_contracts')->where('school_id', $school)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['contract' => 'Ya existe un contrato activo.']);
            }
            $values = [
                'school_id' => $school, 'contract_number' => $data['contract_number'],
                'status' => 'active', 'term_type' => $data['term_type'],
                'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'] ?? null,
                'billing_frequency' => $data['billing_frequency'],
                'payment_mode' => $data['payment_mode'], 'currency' => 'MXN',
                'notes' => $data['notes'] ?? null, 'legacy_school_license_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (Schema::hasColumn('school_contracts', 'service_package_id')) {
                $values['service_package_id'] = $data['service_package_id'] ?? null;
            }
            DB::table('school_contracts')->insert($values);
        }, 3);
        return redirect()->route('sysadmin.schools.modules.index', $school)
            ->with('status', 'Contrato de complementos registrado.');
    }

    public function updateModule(Request $request, int $school, int $module): RedirectResponse
    {
        abort_unless(DB::table('schools')->where('id', $school)->exists(), 404);
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'unit_price' => ['required_if:enabled,1', 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
        ]);
        $enabled = (bool) $data['enabled'];
        $catalogModule = DB::table('service_modules')->where('id', $module)->first();
        abort_unless($catalogModule, 404);
        if ((bool) $catalogModule->is_core) {
            throw ValidationException::withMessages(['module' => 'SchoolCore no puede contratarse ni suspenderse por separado.']);
        }

        $subscription = $this->subscription->forSchool($school);
        $this->assertCompatibleSubscription($subscription);
        $included = collect($subscription['modules'] ?? [])->firstWhere('id', $module);
        if ($included && ($included['included'] ?? false)) {
            throw ValidationException::withMessages(['module' => 'El módulo ya está incluido en el plan principal.']);
        }
        if ($enabled && $catalogModule->status !== 'active') {
            throw ValidationException::withMessages(['module' => 'El módulo no está disponible.']);
        }
        // The actual billing operation and automatic contract creation share one transaction.
        $message = DB::transaction(function () use (
            $school, $module, $enabled, $catalogModule, $subscription, $data, $request
        ): string {
            DB::table('schools')->where('id', $school)->lockForUpdate()->first();
            // Reload after obtaining the school lock.
            $current = $this->subscription->forSchool($school);
            $this->assertCompatibleSubscription($current);
            $contract = DB::table('school_contracts')->where('school_id', $school)
                ->where('status', 'active')->lockForUpdate()->orderByDesc('id')->first();

            if (!$enabled) {
                if (!$contract) {
                    throw ValidationException::withMessages(['contract' => 'No hay contrato activo para suspender.']);
                }
                $this->billing->suspend($school, $module, (int) $contract->id);
                return 'Módulo suspendido. Se conservan los datos y los cargos históricos.';
            }

            $price = number_format((float) $data['unit_price'], 2, '.', '');
            if ((string) $catalogModule->pricing_model === 'paid' && (float) $price <= 0) {
                throw ValidationException::withMessages(['unit_price' => 'Academic y demás módulos de pago requieren precio mayor a cero.']);
            }
            if ((string) $catalogModule->pricing_model === 'free' && (float) $price !== 0.0) {
                throw ValidationException::withMessages(['unit_price' => 'Este módulo gratuito debe costar cero.']);
            }

            if (!$contract) {
                $licenseId = (int) $current['licenseId'];
                $values = [
                    'school_id' => $school,
                    'contract_number' => sprintf('SC-%d-L%d', $school, $licenseId),
                    'status' => 'active', 'term_type' => 'fixed',
                    'starts_on' => $current['startsOn'], 'ends_on' => $current['expiresOn'],
                    'billing_frequency' => $current['billingCycle'],
                    'payment_mode' => 'invoice', 'currency' => $current['currency'] ?? 'MXN',
                    'notes' => 'Contrato de complementos generado desde Sysadmin.',
                    'legacy_school_license_id' => null, 'created_at' => now(), 'updated_at' => now(),
                ];
                $contractId = DB::table('school_contracts')->insertGetId($values);
            } else {
                if ((string) $contract->billing_frequency !== (string) $current['billingCycle'] ||
                    (string) $contract->starts_on > now()->toDateString() ||
                    ($contract->ends_on !== null && $contract->ends_on < $current['expiresOn'])) {
                    throw ValidationException::withMessages([
                        'contract' => 'El contrato vigente no cubre la licencia actual. Revisa sus fechas y periodicidad.',
                    ]);
                }
                $contractId = (int) $contract->id;
            }
            $result = $this->billing->activate(
                schoolId: $school, moduleId: $module, contractId: $contractId,
                price: $price, pricePeriod: (string) $current['billingCycle'],
                actorId: (int) $request->user()->id,
            );
            return $result['charge_created']
                ? 'Módulo activado. Cargo proporcional registrado como pendiente.'
                : 'Módulo activado. Sin nuevo cargo en este período.';
        }, 3);

        return redirect()->route('sysadmin.schools.modules.index', $school)->with('status', $message);
    }

    private function assertCompatibleSubscription(array $subscription): void
    {
        if (!($subscription['licenseActive'] ?? false) || !($subscription['licenseId'] ?? null)) {
            throw ValidationException::withMessages(['license' => 'Se requiere licencia vigente.']);
        }
        if (!in_array($subscription['billingCycle'] ?? null, ['monthly', 'annual'], true) ||
            !$subscription['startsOn'] || !$subscription['expiresOn']) {
            throw ValidationException::withMessages(['license' => 'La licencia requiere período mensual o anual con fechas.']);
        }
    }
}
