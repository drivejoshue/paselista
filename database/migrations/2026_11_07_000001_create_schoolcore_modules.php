<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('category', 50)->default('operations');
            $table->string('status', 25)->default('active');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_module_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('service_modules')->cascadeOnDelete();
            $table->foreignId('required_module_id')->constrained('service_modules')->restrictOnDelete();
            $table->unique(['module_id', 'required_module_id'], 'module_dependencies_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_module_dependencies');
        Schema::dropIfExists('service_modules');
    }
};
