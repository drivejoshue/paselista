<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_modules', function (Blueprint $table) {
            $table->string('pricing_model', 30)
                ->default('quote');

            $table->decimal('monthly_price', 12, 2)
                ->nullable();

            $table->decimal('annual_price', 12, 2)
                ->nullable();

            $table->char('currency', 3)
                ->default('MXN');

            $table->boolean('self_activatable')
                ->default(false);

            $table->string('navigation_placement', 30)
                ->default('sidebar');
        });
    }

    public function down(): void
    {
        Schema::table('service_modules', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_model',
                'monthly_price',
                'annual_price',
                'currency',
                'self_activatable',
                'navigation_placement',
            ]);
        });
    }
};
