<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $cycle = DB::table('academic_cycles')
                ->where('id', 3)
                ->where('school_id', 1)
                ->lockForUpdate()
                ->first();

            if ($cycle === null) {
                throw new RuntimeException(
                    'No se encontró el ciclo 3 de la escuela 1.'
                );
            }

            if (
                $cycle->name !== '2026-2028'
                && $cycle->name !== '2026-2027'
            ) {
              throw new \RuntimeException(
                    'El ciclo encontrado no corresponde al periodo esperado. '
                    .'Nombre actual: '.$cycle->name
                );
            }

            DB::table('academic_cycles')
                ->where('id', 3)
                ->where('school_id', 1)
                ->update([
                    'name' => '2026-2027',
                    'ends_on' => '2027-06-30',
                    'updated_at' => now(),
                ]);

            /*
             * En caso de que ya existieran patrocinios reales asociados
             * al ciclo, ajusta también su vencimiento al nuevo cierre.
             */
            if (Schema::hasTable('guardian_memberships')) {
                DB::table('guardian_memberships')
                    ->where('school_id', 1)
                    ->where('academic_cycle_id', 3)
                    ->where('source', 'school_sponsorship')
                    ->update([
                        'expires_at' => '2027-06-30',
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('academic_cycles')
                ->where('id', 3)
                ->where('school_id', 1)
                ->where('name', '2026-2027')
                ->update([
                    'name' => '2026-2028',
                    'ends_on' => '2028-06-30',
                    'updated_at' => now(),
                ]);

            if (Schema::hasTable('guardian_memberships')) {
                DB::table('guardian_memberships')
                    ->where('school_id', 1)
                    ->where('academic_cycle_id', 3)
                    ->where('source', 'school_sponsorship')
                    ->whereDate('expires_at', '2027-06-30')
                    ->update([
                        'expires_at' => '2028-06-30',
                        'updated_at' => now(),
                    ]);
            }
        });
    }
};