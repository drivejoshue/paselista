<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolCoreFreeModulesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('service_modules')->updateOrInsert(
            ['code' => 'notices'],
            [
                'name' => 'Avisos escolares',
                'description' => 'Publicación de comunicados y avisos para la comunidad escolar.',
                'icon' => 'megaphone',
                'category' => 'communications',
                'status' => 'active',
                'is_core' => false,
                'is_public' => true,
                'sort_order' => 30,
                'updated_at' => now(),
            ]
        );
    }
}
