<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'academic_assignment_teachers',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained('schools')
                    ->restrictOnDelete();

                $table->foreignId('teaching_assignment_id')
                    ->constrained('academic_teaching_assignments')
                    ->cascadeOnDelete();

                $table->foreignId('teacher_profile_id')
                    ->constrained('academic_teacher_profiles')
                    ->restrictOnDelete();

                $table->string('status', 20)
                    ->default('active');

                $table->timestamp('ended_at')->nullable();

                $table->timestamps();

                // El mismo docente no se duplica
                // dentro de una asignación.
                $table->unique(
                    [
                        'teaching_assignment_id',
                        'teacher_profile_id',
                    ],
                    'academic_asgt_teacher_unique'
                );

                $table->index(
                    [
                        'school_id',
                        'teacher_profile_id',
                        'status',
                    ],
                    'academic_asgt_teacher_lookup'
                );
            }
        );

        /*
         * Migrar las relaciones actuales.
         *
         * Conservamos las asignaciones originales
         * y sus IDs, ya utilizados por gradebooks.
         */
        DB::table('academic_teaching_assignments')
            ->whereNotNull('teacher_profile_id')
            ->orderBy('id')
            ->chunkById(500, function ($assignments): void {
                $rows = [];

                foreach ($assignments as $assignment) {
                    $rows[] = [
                        'school_id' => $assignment->school_id,
                        'teaching_assignment_id' => $assignment->id,
                        'teacher_profile_id' => $assignment->teacher_profile_id,
                        'status' => $assignment->status === 'active'
                            ? 'active'
                            : 'inactive',
                        'ended_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('academic_assignment_teachers')
                        ->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_assignment_teachers');
    }
};
