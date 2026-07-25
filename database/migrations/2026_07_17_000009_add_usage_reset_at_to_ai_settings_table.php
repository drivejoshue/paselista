<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_settings', 'usage_reset_at')) {
            Schema::table('ai_settings', function (Blueprint $table): void {
                $table->timestamp('usage_reset_at')
                    ->nullable()
                    ->after('allow_student_analysis');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_settings', 'usage_reset_at')) {
            Schema::table('ai_settings', function (Blueprint $table): void {
                $table->dropColumn('usage_reset_at');
            });
        }
    }
};
