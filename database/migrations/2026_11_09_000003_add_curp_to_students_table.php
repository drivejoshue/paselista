<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {

            // CURP del alumno.
            // Opcional para conservar expedientes existentes.
            $table->string('curp', 18)
                ->nullable()
                ->after('student_code');

            // Evita registrar dos veces la misma CURP
            // dentro de una misma escuela.
            // Permite múltiples NULL en MariaDB/MySQL.
            $table->unique(
                ['school_id', 'curp'],
                'students_school_curp_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {

            $table->dropUnique(
                'students_school_curp_unique'
            );

            $table->dropColumn('curp');
        });
    }
};
