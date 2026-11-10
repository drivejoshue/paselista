
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 25)->default('active');
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_package_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_package_id')
                ->constrained('service_packages')
                ->cascadeOnDelete();

            $table->foreignId('service_module_id')
                ->constrained('service_modules')
                ->restrictOnDelete();

            $table->string('inclusion_type', 25)
                ->default('included');

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(
                ['service_package_id', 'service_module_id'],
                'spm_package_module_unique'
            );
        });

        Schema::table('school_contracts', function (Blueprint $table) {
            $table->foreignId('service_package_id')
                ->nullable()
                ->constrained('service_packages')
                ->nullOnDelete();
        });

        Schema::table('school_contract_modules', function (Blueprint $table) {
            $table->string('pricing_type', 25)
                ->default('custom');

            $table->boolean('is_primary')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('school_contract_modules', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'is_primary']);
        });

        Schema::table('school_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_package_id');
        });

        Schema::dropIfExists('service_package_modules');
        Schema::dropIfExists('service_packages');
    }
};
