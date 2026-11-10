<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('contract_number', 80)->unique();
            $table->string('status', 30)->default('draft');
            $table->string('term_type', 30)->default('fixed'); // fixed | open_ended
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('billing_frequency', 30)->default('monthly');
            $table->string('payment_mode', 30)->default('invoice'); // invoice | prepaid | sponsored
            $table->char('currency', 3)->default('MXN');
            $table->text('notes')->nullable();
            $table->foreignId('legacy_school_license_id')->nullable()->constrained('school_licenses')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });

        Schema::create('school_contract_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('school_contract_id')->constrained('school_contracts')->restrictOnDelete();
            $table->foreignId('service_module_id')->constrained('service_modules')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('price_period', 30)->default('monthly');
            $table->json('limits_json')->nullable();
            $table->json('features_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'service_module_id', 'status'], 'school_module_status_idx');
        });

        Schema::create('school_module_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('service_module_id')->constrained('service_modules')->restrictOnDelete();
            $table->foreignId('school_contract_module_id')->nullable()->constrained('school_contract_modules')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->date('starts_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->date('grace_ends_on')->nullable();
            $table->json('limits_json')->nullable();
            $table->json('features_json')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'service_module_id'], 'school_module_entitlement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_module_entitlements');
        Schema::dropIfExists('school_contract_modules');
        Schema::dropIfExists('school_contracts');
    }
};
