<?php
declare(strict_types=1);

namespace App\Services\SchoolCore;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FreeModuleService
{
    public function __construct(
        private readonly ModuleAccessService $access,
    ) {
    }

    public function setEnabled(
        int $schoolId,
        string $code,
        bool $enabled
    ): void {
        $definition = config(
            "schoolcore_modules.free.{$code}"
        );

        if (!is_array($definition) || $schoolId <= 0) {
            throw ValidationException::withMessages([
                'module' => 'Este módulo no admite activación gratuita.',
            ]);
        }

        if (!$this->access->canAccess($schoolId, 'core')) {
            abort(403, 'SchoolCore no está habilitado.');
        }

        DB::transaction(function () use (
            $schoolId,
            $code,
            $enabled
        ): void {

        $module = DB::table('service_modules')
            ->where('code', $code)
            ->where('status', 'active')
            ->where('is_core', false)
            ->where('pricing_model', 'free')
            ->where('self_activatable', true)
            ->lockForUpdate()
            ->first(['id']);


            if (!$module) {
                throw ValidationException::withMessages([
                    'module' => 'El módulo no está disponible.',
                ]);
            }

            $existing = DB::table('school_module_entitlements')
                ->where('school_id', $schoolId)
                ->where('service_module_id', $module->id)
                ->lockForUpdate()
                ->first();

            // Nunca sobrescribir una activación comercial.
            if ($existing && $existing->school_contract_module_id !== null) {
                abort(409, 'El módulo está administrado por un contrato.');
            }

            // Evitar modificar activaciones ajenas al catálogo gratuito.
            if (
                $existing
                && $existing->school_contract_module_id === null
                && $existing->features_json !== null
            ) {
                $features = json_decode(
                    $existing->features_json,
                    true
                );

                if (
                    !is_array($features)
                    || ($features['activation_source'] ?? null) !== 'free_catalog'
                ) {
                    abort(409, 'La activación requiere revisión administrativa.');
                }
            }

            $values = [
                'status' => $enabled ? 'active' : 'inactive',
                'starts_on' => $enabled ? now()->toDateString() : null,
                'expires_on' => null,
                'grace_ends_on' => null,
                'features_json' => json_encode([
                    'activation_source' => 'free_catalog',
                ]),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('school_module_entitlements')
                    ->where('id', $existing->id)
                    ->update($values);

                return;
            }

            DB::table('school_module_entitlements')->insert([
                'school_id' => $schoolId,
                'service_module_id' => $module->id,
                'school_contract_module_id' => null,
                'status' => $values['status'],
                'starts_on' => $values['starts_on'],
                'expires_on' => null,
                'grace_ends_on' => null,
                'limits_json' => null,
                'features_json' => $values['features_json'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
