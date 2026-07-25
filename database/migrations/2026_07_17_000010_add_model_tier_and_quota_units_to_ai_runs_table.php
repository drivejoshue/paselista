<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addTier = ! Schema::hasColumn(
            'ai_runs',
            'requested_model_tier'
        );

        $addUnits = ! Schema::hasColumn(
            'ai_runs',
            'quota_units'
        );

        if ($addTier || $addUnits) {
            Schema::table(
                'ai_runs',
                function (
                    Blueprint $table
                ) use (
                    $addTier,
                    $addUnits
                ): void {
                    if ($addTier) {
                        $table
                            ->string(
                                'requested_model_tier',
                                20
                            )
                            ->default('fast')
                            ->after('provider');
                    }

                    if ($addUnits) {
                        $table
                            ->unsignedSmallInteger(
                                'quota_units'
                            )
                            ->default(1)
                            ->after(
                                'requested_model_tier'
                            );
                    }
                }
            );
        }

        DB::table('ai_runs')->update([
            'requested_model_tier' => 'fast',
            'quota_units' => 1,
        ]);

        DB::table('ai_runs')
            ->where(
                'model',
                'like',
                '%pro%'
            )
            ->update([
                'requested_model_tier' => 'pro',
                'quota_units' => 6,
            ]);
    }

    public function down(): void
    {
        $dropTier = Schema::hasColumn(
            'ai_runs',
            'requested_model_tier'
        );

        $dropUnits = Schema::hasColumn(
            'ai_runs',
            'quota_units'
        );

        if (! $dropTier && ! $dropUnits) {
            return;
        }

        Schema::table(
            'ai_runs',
            function (
                Blueprint $table
            ) use (
                $dropTier,
                $dropUnits
            ): void {
                $columns = [];

                if ($dropTier) {
                    $columns[] =
                        'requested_model_tier';
                }

                if ($dropUnits) {
                    $columns[] =
                        'quota_units';
                }

                $table->dropColumn(
                    $columns
                );
            }
        );
    }
};
