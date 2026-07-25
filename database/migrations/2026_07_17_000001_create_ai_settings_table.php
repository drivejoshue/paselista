<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')
                ->unique()
                ->constrained('schools')
                ->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('default_model', 30)->default('fast');
            $table->boolean('allow_pro')->default(false);
            $table->unsignedInteger('monthly_query_limit')->default(300);
            $table->unsignedSmallInteger('max_range_days')->default(120);
            $table->boolean('allow_school_analysis')->default(true);
            $table->boolean('allow_group_analysis')->default(true);
            $table->boolean('allow_student_analysis')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
