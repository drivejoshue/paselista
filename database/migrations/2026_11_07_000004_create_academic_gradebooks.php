<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_grading_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('academic_cycle_id')->constrained('academic_cycles')->restrictOnDelete();
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->index(['school_id', 'academic_cycle_id', 'sequence'], 'academic_period_cycle_idx');
        });

        Schema::create('academic_gradebooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained('academic_teaching_assignments')->restrictOnDelete();
            $table->foreignId('grading_period_id')->constrained('academic_grading_periods')->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['teaching_assignment_id', 'grading_period_id'], 'academic_gradebook_assignment_period_unique');
        });

        Schema::create('academic_student_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('gradebook_id')->constrained('academic_gradebooks')->restrictOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->restrictOnDelete();
            $table->decimal('numeric_value', 7, 3)->nullable();
            $table->string('text_value', 80)->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('graded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['gradebook_id', 'student_enrollment_id'], 'academic_one_grade_per_enrollment');
            $table->index(['school_id', 'student_enrollment_id'], 'academic_grades_enrollment_idx');
        });

        Schema::create('academic_grade_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('student_grade_id')->nullable()->constrained('academic_student_grades')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_grade_audit_logs');
        Schema::dropIfExists('academic_student_grades');
        Schema::dropIfExists('academic_gradebooks');
        Schema::dropIfExists('academic_grading_periods');
    }
};
