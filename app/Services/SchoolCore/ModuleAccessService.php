<?php
declare(strict_types=1);

namespace App\Services\SchoolCore;

use App\Services\Licensing\SchoolLicenseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ModuleAccessService
{
    public function __construct(
        private readonly SchoolLicenseService $licenses,
    ) {
    }

    public function canAccess(int $schoolId, string $code): bool
    {
        return $this->accessSource($schoolId, $code) !== null;
    }


public function accessSource(int $schoolId, string $code): ?string
{
    if ($schoolId <= 0 || $code === '') {
        return null;
    }

    if (!$this->licenses->isActive($schoolId)) {
        return null;
    }

    $module = DB::table('service_modules')
        ->where('code', $code)
        ->where('status', 'active')
        ->first(['id', 'is_core']);

    if (!$module) {
        return null;
    }

    if ((bool) $module->is_core) {
        return 'core';
    }

    $today = Carbon::today()->toDateString();

    $entitlement = DB::table('school_module_entitlements')
        ->where('school_id', $schoolId)
        ->where('service_module_id', $module->id)
        ->first();

    if ($entitlement) {
        // Una suspensión explícita prevalece sobre el fallback heredado.
        if ($entitlement->status !== 'active') {
            return null;
        }

        if (
            ($entitlement->starts_on !== null
                && $entitlement->starts_on > $today)
            || ($entitlement->expires_on !== null
                && $entitlement->expires_on < $today)
        ) {
            return null;
        }

        // Entitlement administrado por un contrato.
        if ($entitlement->school_contract_module_id !== null) {
            $line = DB::table('school_contract_modules as cm')
                ->join(
                    'school_contracts as c',
                    'c.id',
                    '=',
                    'cm.school_contract_id'
                )
                ->where(
                    'cm.id',
                    $entitlement->school_contract_module_id
                )
                ->where('cm.school_id', $schoolId)
                ->where('cm.service_module_id', $module->id)
                ->where('cm.status', 'active')
                ->where('c.school_id', $schoolId)
                ->where('c.status', 'active')
                ->where('cm.starts_on', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('cm.ends_on')
                        ->orWhere('cm.ends_on', '>=', $today);
                })
                ->where('c.starts_on', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('c.ends_on')
                        ->orWhere('c.ends_on', '>=', $today);
                })
                ->exists();

            return $line ? 'contract' : null;
        }

        // Activación independiente, por ejemplo gratuita.
        return 'contract';
    }

    // Compatibilidad solo si no existe una decisión explícita.
    // Módulos incluidos: fuente de verdad subscription_plan_modules.
    // La licencia principal ya fue validada al inicio del método.
    $license = $this->licenses->current($schoolId);
    if ($license && DB::table('subscription_plan_modules')
        ->where('subscription_plan_id', $license->subscription_plan_id)
        ->where('service_module_id', $module->id)
        ->where('is_included', true)
        ->exists()) {
        return 'plan';
    }

    if (
        $code === 'attendance'
        && config('schoolcore.legacy_attendance_fallback', true)
        && $this->licenses->hasFeature($schoolId, 'attendance')
    ) {
        return 'legacy';
    }

    return null;
}


    public function catalog(int $schoolId): array
    {
        return DB::table('service_modules')
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('is_public', true)
                    ->orWhere('is_core', true);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'code',
                'name',
                'description',
                'category',
                'icon',
                'is_core',
            ])
            ->map(function ($module) use ($schoolId): array {
                $source = $this->accessSource(
                    $schoolId,
                    $module->code
                );

                return [
                    'code' => $module->code,
                    'name' => $module->name,
                    'description' => $module->description,
                    'category' => $module->category,
                    'icon' => $module->icon,
                    'isCore' => (bool) $module->is_core,
                    'active' => $source !== null,
                    'source' => $source,
                ];
            })
            ->all();
    }
}
