<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_grade_portal_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('academic_grade_portal_pins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->string('pin_hash', 255);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('active');
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_id', 'guardian_id'], 'agpp_school_student_guardian_unique');
            $table->index(['school_id', 'status'], 'agpp_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_grade_portal_pins');
        Schema::dropIfExists('academic_grade_portal_settings');
    }
};
