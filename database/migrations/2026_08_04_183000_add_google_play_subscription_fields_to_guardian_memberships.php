<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_package_name'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_package_name',
                            255
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_base_plan_id'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_base_plan_id',
                            100
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_purchase_token_encrypted'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->text(
                            'google_play_purchase_token_encrypted'
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_linked_purchase_token_hash'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_linked_purchase_token_hash',
                            64
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_subscription_state'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_subscription_state',
                            80
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_acknowledgement_state'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_acknowledgement_state',
                            80
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_auto_renewing'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->boolean(
                            'google_play_auto_renewing'
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_region_code'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'google_play_region_code',
                            8
                        )
                        ->nullable();
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_is_test_purchase'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->boolean(
                            'google_play_is_test_purchase'
                        )
                        ->default(false);
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_payload'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->json(
                            'google_play_payload'
                        )
                        ->nullable();
                }
            );
        }

        $this->createTokenHashUniqueIndex();
    }

    public function down(): void
    {
        $this->dropTokenHashUniqueIndex();

        $columns = collect([
            'google_play_package_name',
            'google_play_base_plan_id',
            'google_play_purchase_token_encrypted',
            'google_play_linked_purchase_token_hash',
            'google_play_subscription_state',
            'google_play_acknowledgement_state',
            'google_play_auto_renewing',
            'google_play_region_code',
            'google_play_is_test_purchase',
            'google_play_payload',
        ])
            ->filter(
                fn (string $column): bool => (
                    Schema::hasColumn(
                        'guardian_memberships',
                        $column
                    )
                )
            )
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table) use (
                    $columns
                ): void {
                    $table->dropColumn($columns);
                }
            );
        }
    }

    private function createTokenHashUniqueIndex(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || $this->indexExists(
                'guardian_memberships',
                'gm_play_token_hash_uq'
            )
        ) {
            return;
        }

        DB::statement(
            '
            CREATE UNIQUE INDEX gm_play_token_hash_uq
            ON guardian_memberships (
                google_play_purchase_token_hash
            )
            '
        );
    }

    private function dropTokenHashUniqueIndex(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || ! $this->indexExists(
                'guardian_memberships',
                'gm_play_token_hash_uq'
            )
        ) {
            return;
        }

        DB::statement(
            '
            DROP INDEX gm_play_token_hash_uq
            ON guardian_memberships
            '
        );
    }

    private function indexExists(
        string $table,
        string $index
    ): bool {
        return DB::table('information_schema.statistics')
            ->whereRaw(
                'table_schema = DATABASE()'
            )
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};