<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SchoolCorePackageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $packages = [
                [
                    'code' => 'core_academic',
                    'name' => 'SchoolCore Academic',
                    'modules' => ['core', 'academic'],
                    'primary' => 'academic',
                ],
                [
                    'code' => 'core_attendance',
                    'name' => 'SchoolCore PaseLista',
                    'modules' => ['core', 'attendance'],
                    'primary' => 'attendance',
                ],
                [
                    'code' => 'core_integral',
                    'name' => 'SchoolCore Integral',
                    'modules' => ['core', 'academic', 'attendance'],
                    'primary' => 'academic',
                ],
                [
                    'code' => 'core_custom',
                    'name' => 'SchoolCore Personalizado',
                    'modules' => ['core'],
                    'primary' => null,
                ],
            ];

            foreach ($packages as $package) {
                DB::table('service_packages')->updateOrInsert(
                    ['code' => $package['code']],
                    [
                        'name' => $package['name'],
                        'description' => 'Paquete configurable SchoolCore',
                        'status' => 'active',
                        'is_public' => true,
                        'updated_at' => now(),
                    ]
                );

                $packageId = DB::table('service_packages')
                    ->where('code', $package['code'])
                    ->value('id');

                foreach ($package['modules'] as $code) {
                    $moduleId = DB::table('service_modules')
                        ->where('code', $code)
                        ->value('id');

                    if (! $moduleId) {
                        throw new RuntimeException(
                            "No existe el módulo: {$code}"
                        );
                    }

                    DB::table('service_package_modules')
                        ->updateOrInsert(
                            [
                                'service_package_id' => $packageId,
                                'service_module_id' => $moduleId,
                            ],
                            [
                                'inclusion_type' => 'included',
                                'is_primary' => $code === $package['primary'],
                                'updated_at' => now(),
                            ]
                        );
                }
            }
        });
    }
}
