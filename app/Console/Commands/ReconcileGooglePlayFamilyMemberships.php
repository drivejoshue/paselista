<?php

namespace App\Console\Commands;

use App\Exceptions\GooglePlayPurchaseException;
use App\Models\GuardianMembership;
use App\Services\Family\GooglePlaySubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileGooglePlayFamilyMemberships extends Command
{
    protected $signature = 'family:reconcile-google-play
        {--membership= : ID específico de guardian_memberships}
        {--limit=200 : Máximo de membresías por ejecución}
        {--stale-minutes=15 : Minutos desde la última verificación}
        {--dry-run : Solo muestra las membresías candidatas}';

    protected $description = (
        'Concilia membresías PaseLista Family con Google Play, '
        .'actualiza vigencia exacta y confirma compras pendientes.'
    );

    public function handle(
        GooglePlaySubscriptionService $subscriptions,
    ): int {
        if (
            ! (bool) config(
                'family_memberships.google_play.enabled',
                false
            )
        ) {
            $this->error(
                'Google Play está deshabilitado en la configuración.'
            );

            return self::FAILURE;
        }

        $membershipId = $this->positiveIntegerOption(
            'membership',
            allowNull: true,
        );

        $limit = $this->positiveIntegerOption(
            'limit',
            default: 200,
        );

        $staleMinutes = $this->nonNegativeIntegerOption(
            'stale-minutes',
            default: 15,
        );

        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMinutes($staleMinutes);

        $query = GuardianMembership::query()
            ->where('source', 'google_play')
            ->whereNull('revoked_at')
            ->whereNotNull(
                'google_play_purchase_token_encrypted'
            )
            ->whereIn('status', [
                'active',
                'expired',
                'pending',
            ]);

        if ($membershipId !== null) {
            $query->whereKey($membershipId);
        } else {
            $query->where(function (Builder $candidateQuery) use (
                $cutoff,
            ): void {
                $candidateQuery
                    ->whereNull('last_verified_at')
                    ->orWhere(
                        'last_verified_at',
                        '<=',
                        $cutoff
                    )
                    ->orWhere(
                        'google_play_acknowledgement_state',
                        'ACKNOWLEDGEMENT_STATE_PENDING'
                    )
                    ->orWhere(function (
                        Builder $expiredLocallyQuery
                    ): void {
                        $expiredLocallyQuery
                            ->where('status', 'active')
                            ->whereNotNull(
                                'google_play_expires_at'
                            )
                            ->where(
                                'google_play_expires_at',
                                '<=',
                                now()
                            );
                    });
            });
        }

        $memberships = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($memberships->isEmpty()) {
            $this->info(
                'No hay membresías de Google Play pendientes '
                .'de conciliación.'
            );

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d membresía(s) candidata(s).%s',
            $memberships->count(),
            $dryRun ? ' Modo DRY RUN.' : ''
        ));

        $summary = [
            'processed' => 0,
            'active' => 0,
            'expired' => 0,
            'failed' => 0,
            'locally_expired' => 0,
        ];

        foreach ($memberships as $membership) {
            if ($dryRun) {
                $this->line($this->candidateLine($membership));
                continue;
            }

            if ($this->expireLocallyWhenNecessary($membership)) {
                $summary['locally_expired']++;
            }

            try {
                $result = $subscriptions
                    ->reconcileMembership($membership);

                $summary['processed']++;

                if (($result['status'] ?? null) === 'active') {
                    $summary['active']++;
                } else {
                    $summary['expired']++;
                }

                $this->info(sprintf(
                    '#%d %s · Google=%s · vence=%s',
                    $membership->id,
                    strtoupper((string) ($result['status'] ?? 'unknown')),
                    (string) (
                        $result['subscription_state']
                        ?? 'sin estado'
                    ),
                    (string) (
                        $result['google_play_expires_at']
                        ?? 'sin vencimiento'
                    )
                ));
            } catch (
                GooglePlayPurchaseException $exception
            ) {
                if (
                    $exception->errorCode
                        === 'GOOGLE_PLAY_PURCHASE_EXPIRED'
                ) {
                    $this->markTokenAsExpired(
                        membership: $membership,
                    );

                    $summary['processed']++;
                    $summary['expired']++;

                    $this->warn(sprintf(
                        '#%d EXPIRED: Google ya no conserva '
                        .'el token consultable.',
                        $membership->id
                    ));

                    continue;
                }

                $summary['failed']++;

                $this->error(sprintf(
                    '#%d %s: %s',
                    $membership->id,
                    $exception->errorCode,
                    $exception->getMessage()
                ));

                Log::warning(
                    'Falló la conciliación Google Play Family.',
                    [
                        'membership_id' => $membership->id,
                        'school_id' => $membership->school_id,
                        'guardian_id' => $membership->guardian_id,
                        'error_code' => $exception->errorCode,
                        'message' => $exception->getMessage(),
                        'context' => $exception->context,
                    ]
                );
            } catch (Throwable $exception) {
                $summary['failed']++;

                $this->error(sprintf(
                    '#%d ERROR: %s',
                    $membership->id,
                    $exception->getMessage()
                ));

                Log::error(
                    'Error inesperado conciliando Google Play Family.',
                    [
                        'membership_id' => $membership->id,
                        'school_id' => $membership->school_id,
                        'guardian_id' => $membership->guardian_id,
                        'exception' => $exception,
                    ]
                );
            }
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Procesadas', 'Activas', 'Vencidas', 'Fallidas', 'Vencidas localmente'],
            [[
                $summary['processed'],
                $summary['active'],
                $summary['expired'],
                $summary['failed'],
                $summary['locally_expired'],
            ]]
        );

        return $summary['failed'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function expireLocallyWhenNecessary(
        GuardianMembership $membership,
    ): bool {
        if (
            $membership->status !== 'active'
            || $membership->google_play_expires_at === null
            || $membership->google_play_expires_at->gt(now())
        ) {
            return false;
        }

        $membership->forceFill([
            'status' => 'expired',
        ])->save();

        return true;
    }

    private function markTokenAsExpired(
        GuardianMembership $membership,
    ): void {
        $membership->forceFill([
            'status' => 'expired',
            'google_play_subscription_state' => (
                'SUBSCRIPTION_STATE_EXPIRED'
            ),
            'last_verified_at' => now(),
        ])->save();
    }

    private function candidateLine(
        GuardianMembership $membership,
    ): string {
        return sprintf(
            '#%d school=%d guardian=%d status=%s '
            .'google=%s expires=%s verified=%s',
            $membership->id,
            $membership->school_id,
            $membership->guardian_id,
            $membership->status,
            $membership->google_play_subscription_state
                ?? 'null',
            $membership->google_play_expires_at
                ?->toIso8601String()
                ?? 'null',
            $membership->last_verified_at
                ?->toIso8601String()
                ?? 'null'
        );
    }

    private function positiveIntegerOption(
        string $name,
        ?int $default = null,
        bool $allowNull = false,
    ): ?int {
        $raw = $this->option($name);

        if (
            $allowNull
            && ($raw === null || trim((string) $raw) === '')
        ) {
            return null;
        }

        $value = filter_var(
            $raw ?? $default,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($value === false) {
            throw new \InvalidArgumentException(
                "La opción --{$name} debe ser un entero positivo."
            );
        }

        return (int) $value;
    }

    private function nonNegativeIntegerOption(
        string $name,
        int $default,
    ): int {
        $value = filter_var(
            $this->option($name) ?? $default,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        );

        if ($value === false) {
            throw new \InvalidArgumentException(
                "La opción --{$name} debe ser cero o un entero positivo."
            );
        }

        return (int) $value;
    }
}