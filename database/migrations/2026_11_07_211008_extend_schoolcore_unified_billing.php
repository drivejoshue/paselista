<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * 1. Módulos incluidos en los planes.
         *
         * Puede existir porque MySQL alcanzó a crearla
         * antes de que fallara la migración anterior.
         */
        if (!Schema::hasTable('subscription_plan_modules')) {
            Schema::create(
                'subscription_plan_modules',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('subscription_plan_id')
                        ->constrained('subscription_plans')
                        ->restrictOnDelete();

                    $table->foreignId('service_module_id')
                        ->constrained('service_modules')
                        ->restrictOnDelete();

                    $table->boolean('is_included')
                        ->default(true);

                    $table->boolean('is_primary')
                        ->default(false);

                    $table->timestamps();

                    $table->unique(
                        [
                            'subscription_plan_id',
                            'service_module_id',
                        ],
                        'plan_module_unique'
                    );
                }
            );
        }

        /*
         * 2. Módulo principal del contrato.
         *
         * La columna puede existir por una migración
         * anterior; nunca intentamos duplicarla.
         */
        if (
            Schema::hasTable('school_contract_modules')
            && !Schema::hasColumn(
                'school_contract_modules',
                'is_primary'
            )
        ) {
            Schema::table(
                'school_contract_modules',
                function (Blueprint $table): void {
                    $table->boolean('is_primary')
                        ->default(false);
                }
            );
        }

        /*
         * 3. Cargos de facturación unificada.
         *
         * Plan base, módulos adicionales, ajustes
         * y otros conceptos comerciales.
         */
        if (!Schema::hasTable('school_billing_charges')) {
            Schema::create(
                'school_billing_charges',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('school_id')
                        ->constrained('schools')
                        ->restrictOnDelete();

                    $table->foreignId('school_contract_id')
                        ->nullable()
                        ->constrained('school_contracts')
                        ->restrictOnDelete();

                    $table->foreignId('service_module_id')
                        ->nullable()
                        ->constrained('service_modules')
                        ->restrictOnDelete();

                    $table->string('source_type', 40);

                    $table->unsignedBigInteger('source_id')
                        ->nullable();

                    $table->string('description', 255);

                    $table->date('period_start')
                        ->nullable();

                    $table->date('period_end')
                        ->nullable();

                    $table->decimal('amount', 12, 2);

                    $table->char('currency', 3)
                        ->default('MXN');

                    $table->string('status', 30)
                        ->default('pending');

                    $table->string('idempotency_key', 150)
                        ->unique();

                    $table->timestamps();

                    $table->index(
                        [
                            'school_id',
                            'status',
                            'created_at',
                        ],
                        'billing_school_status_idx'
                    );
                }
            );
        }

        /*
         * 4. Catálogo comercial de paquetes de IA.
         */
        if (!Schema::hasTable('ai_credit_packages')) {
            Schema::create(
                'ai_credit_packages',
                function (Blueprint $table): void {
                    $table->id();

                    $table->string('code', 80)
                        ->unique();

                    $table->string('name', 120);

                    $table->unsignedInteger('credits');

                    $table->decimal('price', 12, 2);

                    $table->char('currency', 3)
                        ->default('MXN');

                    $table->string('status', 30)
                        ->default('active');

                    $table->timestamps();
                }
            );
        }

        /*
         * 5. Wallet de créditos IA por escuela.
         */
        if (!Schema::hasTable('school_ai_wallets')) {
            Schema::create(
                'school_ai_wallets',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('school_id')
                        ->unique()
                        ->constrained('schools')
                        ->restrictOnDelete();

                    $table->unsignedBigInteger('balance_credits')
                        ->default(0);

                    $table->timestamps();
                }
            );
        }

        /*
         * 6. Historial de movimientos del wallet.
         *
         * Las recargas, consumos y ajustes quedarán
         * registrados con referencia única.
         */
        if (!Schema::hasTable('school_ai_wallet_movements')) {
            Schema::create(
                'school_ai_wallet_movements',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('wallet_id')
                        ->constrained('school_ai_wallets')
                        ->restrictOnDelete();

                    $table->string('type', 30);

                    $table->bigInteger('credits_delta');

                    $table->unsignedBigInteger('balance_after');

                    $table->string('reference', 150)
                        ->unique();

                    $table->json('metadata_json')
                        ->nullable();

                    $table->timestamps();

                    $table->index(
                        [
                            'wallet_id',
                            'created_at',
                        ],
                        'ai_wallet_movements_idx'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        /*
         * Rollback conservador.
         *
         * No eliminamos estructuras que pudieron existir
         * antes de esta migración ni las relaciones
         * comerciales que ya insertó el seeder.
         *
         * Las siguientes tablas son nuevas del billing.
         * Su eliminación debe hacerse únicamente cuando
         * sea seguro descartar sus datos.
         */

        Schema::dropIfExists('school_ai_wallet_movements');

        Schema::dropIfExists('school_ai_wallets');

        Schema::dropIfExists('ai_credit_packages');

        Schema::dropIfExists('school_billing_charges');

        /*
         * No eliminar:
         *
         * - school_contract_modules.is_primary
         * - subscription_plan_modules
         *
         * Ambas estructuras pueden contener datos
         * anteriores a la aplicación de esta migración.
         */
    }
};
