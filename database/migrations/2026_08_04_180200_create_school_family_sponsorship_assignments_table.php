<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'school_family_sponsorship_assignments',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Declaramos primero las columnas y después las
                 * llaves foráneas con nombres cortos.
                 */
                $table->foreignId('pool_id');

                $table->foreignId('school_id');

                $table->foreignId('academic_cycle_id');

                $table->foreignId('guardian_id');

                $table->foreignId('guardian_membership_id')
                    ->nullable();

                $table->string('status', 30)
                    ->default('assigned');

                $table->string('reason', 500)
                    ->nullable();

                $table->foreignId('assigned_by_user_id')
                    ->nullable();

                $table->timestamp('assigned_at')
                    ->useCurrent();

                /*
                 * Solamente Sysadmin podrá liberar un patrocinio
                 * asignado por error.
                 */
                $table->foreignId('released_by_user_id')
                    ->nullable();

                $table->timestamp('released_at')
                    ->nullable();

                $table->string('release_reason', 500)
                    ->nullable();

                $table->timestamps();

                /*
                 * Foreign keys con nombres explícitos menores
                 * al límite de 64 caracteres de MySQL.
                 */
                $table->foreign(
                    'pool_id',
                    'sfsa_pool_fk'
                )
                    ->references('id')
                    ->on('school_family_sponsorship_pools')
                    ->cascadeOnDelete();

                $table->foreign(
                    'school_id',
                    'sfsa_school_fk'
                )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table->foreign(
                    'academic_cycle_id',
                    'sfsa_cycle_fk'
                )
                    ->references('id')
                    ->on('academic_cycles')
                    ->cascadeOnDelete();

                $table->foreign(
                    'guardian_id',
                    'sfsa_guardian_fk'
                )
                    ->references('id')
                    ->on('guardians')
                    ->cascadeOnDelete();

                $table->foreign(
                    'guardian_membership_id',
                    'sfsa_membership_fk'
                )
                    ->references('id')
                    ->on('guardian_memberships')
                    ->nullOnDelete();

                $table->foreign(
                    'assigned_by_user_id',
                    'sfsa_assigned_by_fk'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign(
                    'released_by_user_id',
                    'sfsa_released_by_fk'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                /*
                 * Un tutor consume como máximo un lugar
                 * dentro de una bolsa del ciclo.
                 */
                $table->unique(
                    [
                        'pool_id',
                        'guardian_id',
                    ],
                    'sfsa_pool_guardian_unique'
                );

                $table->index(
                    [
                        'pool_id',
                        'status',
                    ],
                    'sfsa_pool_status_index'
                );

                $table->index(
                    [
                        'school_id',
                        'academic_cycle_id',
                        'status',
                    ],
                    'sfsa_school_cycle_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'school_family_sponsorship_assignments'
        );
    }
};