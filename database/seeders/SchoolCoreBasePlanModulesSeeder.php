<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SchoolCoreBasePlanModulesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $modules = DB::table('service_modules')
                ->whereIn('code', ['core', 'attendance'])
                ->pluck('id', 'code');

            foreach (['core', 'attendance'] as $code) {
                if (!isset($modules[$code])) {
                    throw new RuntimeException(
                        "No está registrado el módulo {$code}."
                    );
                }
            }

            $planCodes = [
                'essential',
                'professional',
                'institutional',
                'multi_campus',
            ];

            $plans = DB::table('subscription_plans')
                ->whereIn('code', $planCodes)
                ->get(['id', 'code']);

            if ($plans->count() !== count($planCodes)) {
                throw new RuntimeException(
                    'No se encontraron los cuatro planes base. '
                    .'Verifica subscription_plans antes de continuar.'
                );
            }

            foreach ($plans as $plan) {
                foreach ([
                    'core' => false,
                    'attendance' => true,
                ] as $code => $isPrimary) {
                    DB::table('subscription_plan_modules')
                        ->updateOrInsert(
                            [
                                'subscription_plan_id' => $plan->id,
                                'service_module_id' => $modules[$code],
                            ],
                            [
                                'is_included' => true,
                                'is_primary' => $isPrimary,
                                'updated_at' => now(),
                            ]
                        );
                }
            }

            $this->command?->info(
                'Composición de planes SchoolCore actualizada.'
            );
        });
    }
}
