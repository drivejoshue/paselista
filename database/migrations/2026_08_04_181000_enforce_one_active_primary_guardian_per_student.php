<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * La migración se detiene si todavía existen alumnos
         * con más de un tutor principal activo.
         */
        $duplicateStudentIds = DB::table('student_guardians')
            ->select('student_id')
            ->where('is_primary', true)
            ->where('status', 'active')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('student_id');

        if ($duplicateStudentIds->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede crear la restricción. '
                .'Existen alumnos con más de un tutor principal activo: '
                .$duplicateStudentIds->implode(', ')
            );
        }

        /*
         * MySQL y MariaDB permiten varios NULL en un índice UNIQUE.
         *
         * La columna generada contiene student_id solamente cuando
         * la relación es principal y está activa.
         *
         * Para tutores secundarios o relaciones inactivas devuelve NULL.
         */
        if (
            ! Schema::hasColumn(
                'student_guardians',
                'active_primary_student_id'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE student_guardians
                ADD COLUMN active_primary_student_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (
                    CASE
                        WHEN is_primary = 1
                             AND status = 'active'
                        THEN student_id
                        ELSE NULL
                    END
                ) STORED
                "
            );
        }

        DB::statement(
            "
            CREATE UNIQUE INDEX
                sg_one_active_primary_per_student
            ON student_guardians (
                active_primary_student_id
            )
            "
        );
    }

    public function down(): void
    {
        DB::statement(
            "
            DROP INDEX
                sg_one_active_primary_per_student
            ON student_guardians
            "
        );

        if (
            Schema::hasColumn(
                'student_guardians',
                'active_primary_student_id'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE student_guardians
                DROP COLUMN active_primary_student_id
                "
            );
        }
    }
};