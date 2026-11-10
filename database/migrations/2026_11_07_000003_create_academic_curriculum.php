<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('code', 80);
            $table->string('kind', 30)->default('custom'); // official | custom
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['school_id', 'code'], 'academic_subject_school_code_unique');
        });

        Schema::create('academic_curriculum_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->restrictOnDelete();
            $table->foreignId('academic_level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('framework_code', 80)->nullable(); // e.g. sep_mx_2022; catalog only
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->index(['school_id', 'academic_cycle_id', 'academic_level_id'], 'academic_plan_scope_idx');
        });

        Schema::create('academic_curriculum_plan_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('curriculum_plan_id')->constrained('academic_curriculum_plans')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('academic_subjects')->restrictOnDelete();
            $table->string('grade_label', 100)->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('affects_average')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['school_id', 'curriculum_plan_id'], 'ac_plan_subject_school_plan_idx');
        });

        Schema::create('academic_teacher_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('employee_code', 60)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['school_id', 'user_id'], 'academic_teacher_school_user_unique');
        });

        Schema::create('academic_teaching_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->restrictOnDelete();
            $table->foreignId('school_group_id')->constrained('school_groups')->restrictOnDelete();
            $table->foreignId('plan_subject_id')->constrained('academic_curriculum_plan_subjects')->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('academic_teacher_profiles')->restrictOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index(['school_id', 'school_group_id', 'status'], 'academic_assignment_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_teaching_assignments');
        Schema::dropIfExists('academic_teacher_profiles');
        Schema::dropIfExists('academic_curriculum_plan_subjects');
        Schema::dropIfExists('academic_curriculum_plans');
        Schema::dropIfExists('academic_subjects');
    }
};
