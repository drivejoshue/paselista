<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'guardian_memberships',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained('schools')
                    ->cascadeOnDelete();

                $table->foreignId('guardian_id')
                    ->constrained('guardians')
                    ->cascadeOnDelete();

                /*
                 * Solo se utiliza cuando la membresía depende de un ciclo,
                 * como los patrocinios escolares.
                 *
                 * Una compra anual de Google Play puede atravesar dos ciclos,
                 * por eso es nullable.
                 */
                $table->foreignId('academic_cycle_id')
                    ->nullable()
                    ->constrained('academic_cycles')
                    ->nullOnDelete();

                $table->string('status', 30)
                    ->default('pending');

                $table->string('source', 40)
                    ->nullable();

                $table->date('starts_at')
                    ->nullable();

                $table->date('expires_at')
                    ->nullable();

                /*
                 * Referencias futuras de Google Play.
                 *
                 * El token completo no se guardará aquí.
                 * Después tendrá su propia tabla cifrada.
                 */
                $table->string('google_play_product_id', 120)
                    ->nullable();

                $table->string('google_play_order_id', 190)
                    ->nullable();

                $table->char(
                    'google_play_purchase_token_hash',
                    64
                )->nullable();

                $table->timestamp('last_verified_at')
                    ->nullable();

                /*
                 * Datos de patrocinio o activación administrativa.
                 */
                $table->foreignId('sponsored_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('revoked_at')
                    ->nullable();

                $table->foreignId('revoked_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('revocation_reason', 500)
                    ->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                /*
                 * Solo existe una membresía vigente o actualizable
                 * por tutor y escuela.
                 */
                $table->unique(
                    [
                        'school_id',
                        'guardian_id',
                    ],
                    'guardian_memberships_school_guardian_unique'
                );

                $table->index(
                    [
                        'school_id',
                        'status',
                        'expires_at',
                    ],
                    'guardian_memberships_status_index'
                );

                $table->index(
                    [
                        'school_id',
                        'source',
                        'academic_cycle_id',
                    ],
                    'guardian_memberships_source_index'
                );

                $table->unique(
                    'google_play_purchase_token_hash',
                    'guardian_memberships_purchase_hash_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_memberships');
    }
};