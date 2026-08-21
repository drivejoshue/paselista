<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'school_family_sponsorship_pools',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained('schools')
                    ->cascadeOnDelete();

                $table->foreignId('academic_cycle_id')
                    ->constrained('academic_cycles')
                    ->cascadeOnDelete();

                $table->unsignedInteger('quota_total')
                    ->default(20);

                $table->string('status', 30)
                    ->default('active');

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'school_id',
                        'academic_cycle_id',
                    ],
                    'family_sponsorship_pool_school_cycle_unique'
                );
            }
        );

        /*
         * Crea automáticamente una bolsa para cada ciclo
         * que ya se encuentre activo.
         */
        $now = now();

        DB::table('academic_cycles')
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'id',
                'school_id',
            ])
            ->each(
                function (object $cycle) use ($now): void {
                    DB::table(
                        'school_family_sponsorship_pools'
                    )->insertOrIgnore([
                        'school_id' => $cycle->school_id,
                        'academic_cycle_id' => $cycle->id,
                        'quota_total' => 20,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'school_family_sponsorship_pools'
        );
    }
};