<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('academic_curriculum_plans', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(1);

            $table->foreignId('parent_plan_id')
                ->nullable()
                ->constrained('academic_curriculum_plans')
                ->nullOnDelete();
        });

        Schema::create('academic_group_curriculum_plans', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->restrictOnDelete();

            $table->foreignId('academic_cycle_id')
                ->constrained('academic_cycles')
                ->restrictOnDelete();

            $table->foreignId('school_group_id')
                ->constrained('school_groups')
                ->restrictOnDelete();

            $table->foreignId('curriculum_plan_id')
                ->constrained('academic_curriculum_plans')
                ->restrictOnDelete();

            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(
                'school_group_id',
                'ac_group_plan_one_per_group'
            );

            $table->index(
                ['school_id', 'curriculum_plan_id'],
                'ac_group_plan_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_group_curriculum_plans');

        Schema::table('academic_curriculum_plans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_plan_id');
            $table->dropColumn('revision');
        });
    }
};
