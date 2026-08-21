<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_started_at'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->dateTime('google_play_started_at')
                        ->nullable()
                        ->after('expires_at');
                }
            );
        }

        if (
            ! Schema::hasColumn(
                'guardian_memberships',
                'google_play_expires_at'
            )
        ) {
            Schema::table(
                'guardian_memberships',
                function (Blueprint $table): void {
                    $table
                        ->dateTime('google_play_expires_at')
                        ->nullable()
                        ->after('google_play_started_at');
                }
            );
        }

        $this->createExpiryIndex();
        $this->backfillExactGooglePlayTimes();
    }

    public function down(): void
    {
        $this->dropExpiryIndex();

        $columns = collect([
            'google_play_started_at',
            'google_play_expires_at',
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

    private function backfillExactGooglePlayTimes(): void
    {
        DB::table('guardian_memberships')
            ->where('source', 'google_play')
            ->whereNotNull('google_play_payload')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($memberships): void {
                    foreach ($memberships as $membership) {
                        $payload = $this->decodePayload(
                            $membership->google_play_payload
                        );

                        if ($payload === null) {
                            continue;
                        }

                        $startedAt = $this->parseUtcTimestamp(
                            $payload['startTime'] ?? null
                        );

                        $expiresAt = $this->latestMatchingExpiry(
                            payload: $payload,
                            productId: (
                                $membership
                                    ->google_play_product_id
                                    ?? null
                            ),
                            basePlanId: (
                                $membership
                                    ->google_play_base_plan_id
                                    ?? null
                            ),
                        );

                        if (
                            $startedAt === null
                            && $expiresAt === null
                        ) {
                            continue;
                        }

                        DB::table('guardian_memberships')
                            ->where('id', $membership->id)
                            ->update([
                                'google_play_started_at' => (
                                    $startedAt
                                ),
                                'google_play_expires_at' => (
                                    $expiresAt
                                ),
                            ]);
                    }
                },
                'id'
            );
    }

    private function decodePayload(
        mixed $payload,
    ): ?array {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_object($payload)) {
            return json_decode(
                json_encode($payload),
                true
            );
        }

        if (! is_string($payload) || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded)
            ? $decoded
            : null;
    }

    private function latestMatchingExpiry(
        array $payload,
        ?string $productId,
        ?string $basePlanId,
    ): ?string {
        $latest = null;

        foreach ($payload['lineItems'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (
                $productId !== null
                && ($item['productId'] ?? null) !== $productId
            ) {
                continue;
            }

            $itemBasePlanId = data_get(
                $item,
                'offerDetails.basePlanId'
            );

            if (
                $basePlanId !== null
                && $itemBasePlanId !== $basePlanId
            ) {
                continue;
            }

            $parsed = $this->parseUtcTimestamp(
                $item['expiryTime'] ?? null
            );

            if ($parsed === null) {
                continue;
            }

            if (
                $latest === null
                || $parsed > $latest
            ) {
                $latest = $parsed;
            }
        }

        return $latest;
    }

    private function parseUtcTimestamp(
        mixed $value,
    ): ?string {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->setTimezone(
                    (string) config('app.timezone', 'UTC')
                )
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function createExpiryIndex(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || $this->indexExists(
                'guardian_memberships',
                'gm_play_expires_at_idx'
            )
        ) {
            return;
        }

        DB::statement(
            '
            CREATE INDEX gm_play_expires_at_idx
            ON guardian_memberships (
                google_play_expires_at
            )
            '
        );
    }

    private function dropExpiryIndex(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || ! $this->indexExists(
                'guardian_memberships',
                'gm_play_expires_at_idx'
            )
        ) {
            return;
        }

        DB::statement(
            '
            DROP INDEX gm_play_expires_at_idx
            ON guardian_memberships
            '
        );
    }

    private function indexExists(
        string $table,
        string $index,
    ): bool {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};