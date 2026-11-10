<?php
declare(strict_types=1);

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ModuleCatalogController extends Controller
{
    public function index(): View
    {
        $modules = DB::table('service_modules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $included = DB::table('subscription_plan_modules')
            ->where('is_included', true)
            ->select('service_module_id')
            ->distinct()
            ->pluck('service_module_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $subscriptions = DB::table('school_module_entitlements')
            ->where('status', 'active')
            ->select(
                'service_module_id',
                DB::raw('COUNT(DISTINCT school_id) as total')
            )
            ->groupBy('service_module_id')
            ->pluck('total', 'service_module_id');

        return view('sysadmin.modules.index', [
            'modules' => $modules,
            'includedIds' => $included,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function update(
        Request $request,
        int $module
    ): RedirectResponse {
        $record = DB::table('service_modules')
            ->where('id', $module)
            ->first();

        abort_unless($record, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'coming_soon']),
            ],
            'pricing_model' => [
                'required',
                Rule::in(['included', 'free', 'paid', 'quote']),
            ],
            'monthly_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'decimal:0,2',
            ],
            'annual_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'decimal:0,2',
            ],
            'self_activatable' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        if ((bool) $record->is_core) {
            $data['status'] = 'active';
            $data['pricing_model'] = 'included';
            $data['monthly_price'] = null;
            $data['annual_price'] = null;
            $data['self_activatable'] = false;
        }

        if (
            $data['pricing_model'] !== 'paid'
            && $data['pricing_model'] !== 'quote'
        ) {
            $data['monthly_price'] = null;
            $data['annual_price'] = null;
        }

        if (
            $data['pricing_model'] === 'paid'
            && $data['monthly_price'] === null
            && $data['annual_price'] === null
        ) {
            throw ValidationException::withMessages([
                'monthly_price' =>
                    'Un módulo de pago requiere al menos una tarifa.',
            ]);
        }

        if (
            $data['pricing_model'] === 'quote'
            || $data['status'] !== 'active'
        ) {
            $data['self_activatable'] = false;
        }

        // Evitar reclasificar como gratuito un módulo
        // con líneas contractuales de pago ya activas.
        $hasPaidContracts = DB::table('school_contract_modules')
            ->where('service_module_id', $module)
            ->where('status', 'active')
            ->where('unit_price', '>', 0)
            ->exists();

        if (
            $hasPaidContracts
            && in_array(
                $data['pricing_model'],
                ['included', 'free'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'pricing_model' =>
                    'El módulo tiene contratos de pago activos. '
                    .'No puede reclasificarse como gratuito sin '
                    .'migrar primero sus contratos.',
            ]);
        }

        $values = [
            'name' => trim($data['name']),
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'],
            'updated_at' => now(),
        ];

        foreach ([
            'pricing_model',
            'monthly_price',
            'annual_price',
            'self_activatable',
        ] as $column) {
            if (Schema::hasColumn('service_modules', $column)) {
                $values[$column] = $data[$column];
            } else {
                throw ValidationException::withMessages([
                    $column =>
                        "Falta la columna {$column} en service_modules.",
                ]);
            }
        }

        DB::table('service_modules')
            ->where('id', $module)
            ->update($values);

        return redirect()
            ->route('sysadmin.modules.index')
            ->with('status', 'Catálogo de módulos actualizado.');
    }
}
