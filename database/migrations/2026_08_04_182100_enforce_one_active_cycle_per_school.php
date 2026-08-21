<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('academic_cycles')
            ->select([
                'school_id',
                DB::raw('COUNT(*) as active_cycle_count'),
            ])
            ->where('is_active', true)
            ->groupBy('school_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates
                ->map(
                    fn (object $row): string => (
                        'escuela '.$row->school_id
                        .' = '.$row->active_cycle_count
                        .' ciclos activos'
                    )
                )
                ->implode(', ');

           throw new \RuntimeException(
                'No puede crearse la restricción porque existen '
                .'escuelas con varios ciclos activos: '.$details
            );
        }

        if (
            ! Schema::hasColumn(
                'academic_cycles',
                'active_cycle_school_id'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE academic_cycles
                ADD COLUMN active_cycle_school_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (
                    CASE
                        WHEN is_active = 1
                        THEN school_id
                        ELSE NULL
                    END
                ) STORED
                "
            );
        }

        DB::statement(
            "
            CREATE UNIQUE INDEX ac_one_active_cycle_school_unique
            ON academic_cycles (
                active_cycle_school_id
            )
            "
        );
    }

    public function down(): void
    {
        DB::statement(
            "
            DROP INDEX ac_one_active_cycle_school_unique
            ON academic_cycles
            "
        );

        if (
            Schema::hasColumn(
                'academic_cycles',
                'active_cycle_school_id'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE academic_cycles
                DROP COLUMN active_cycle_school_id
                "
            );
        }
    }
};