<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuardianMembership extends Model
{
    protected $table = 'guardian_memberships';

    protected $fillable = [
        'school_id',
        'guardian_id',
        'academic_cycle_id',
        'status',
        'source',
        'starts_at',
        'expires_at',

        'google_play_started_at',
        'google_play_expires_at',
        'google_play_package_name',
        'google_play_product_id',
        'google_play_base_plan_id',
        'google_play_order_id',
        'google_play_purchase_token_hash',
        'google_play_purchase_token_encrypted',
        'google_play_linked_purchase_token_hash',
        'google_play_subscription_state',
        'google_play_acknowledgement_state',
        'google_play_auto_renewing',
        'google_play_region_code',
        'google_play_is_test_purchase',
        'google_play_payload',
        'last_verified_at',

        'sponsored_by_user_id',

        'revoked_at',
        'revoked_by_user_id',
        'revocation_reason',

        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',

            'google_play_started_at' => 'datetime',
            'google_play_expires_at' => 'datetime',
            'google_play_auto_renewing' => 'boolean',
            'google_play_is_test_purchase' => 'boolean',
            'google_play_payload' => 'array',
            'last_verified_at' => 'datetime',

            'revoked_at' => 'datetime',
        ];
    }

    public function scopeUsable(
        Builder $query,
    ): Builder {
        $now = now();
        $today = $now->toDateString();

        return $query
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function (Builder $accessQuery) use (
                $now,
                $today,
            ): void {
                $accessQuery
                    ->where(function (Builder $googlePlayQuery) use (
                        $now,
                    ): void {
                        $googlePlayQuery
                            ->where('source', 'google_play')
                            ->whereNotNull(
                                'google_play_expires_at'
                            )
                            ->where(
                                'google_play_expires_at',
                                '>',
                                $now
                            )
                            ->where(function (
                                Builder $startQuery
                            ) use ($now): void {
                                $startQuery
                                    ->whereNull(
                                        'google_play_started_at'
                                    )
                                    ->orWhere(
                                        'google_play_started_at',
                                        '<=',
                                        $now
                                    );
                            });
                    })
                    ->orWhere(function (
                        Builder $nonGooglePlayQuery
                    ) use ($today): void {
                        $nonGooglePlayQuery
                            ->where(function (
                                Builder $sourceQuery
                            ): void {
                                $sourceQuery
                                    ->whereNull('source')
                                    ->orWhere(
                                        'source',
                                        '!=',
                                        'google_play'
                                    );
                            })
                            ->where(function (
                                Builder $startQuery
                            ) use ($today): void {
                                $startQuery
                                    ->whereNull('starts_at')
                                    ->orWhereDate(
                                        'starts_at',
                                        '<=',
                                        $today
                                    );
                            })
                            ->where(function (
                                Builder $expiryQuery
                            ) use ($today): void {
                                $expiryQuery
                                    ->whereNull('expires_at')
                                    ->orWhereDate(
                                        'expires_at',
                                        '>=',
                                        $today
                                    );
                            });
                    });
            });
    }

    public function isUsable(): bool
    {
        if (
            $this->status !== 'active'
            || $this->revoked_at !== null
        ) {
            return false;
        }

        $now = now();

        if ($this->source === 'google_play') {
            if ($this->google_play_expires_at === null) {
                return false;
            }

            if (
                $this->google_play_started_at !== null
                && $this->google_play_started_at->gt($now)
            ) {
                return false;
            }

            return $this->google_play_expires_at->gt($now);
        }

        $today = $now->copy()->startOfDay();

        if (
            $this->starts_at !== null
            && $this->starts_at
                ->copy()
                ->startOfDay()
                ->gt($today)
        ) {
            return false;
        }

        if (
            $this->expires_at !== null
            && $this->expires_at
                ->copy()
                ->endOfDay()
                ->lt($now)
        ) {
            return false;
        }

        return true;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class,
            'school_id'
        );
    }

    public function academicCycle(): BelongsTo
    {
        return $this->belongsTo(
            AcademicCycle::class,
            'academic_cycle_id'
        );
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sponsored_by_user_id'
        );
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by_user_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function sponsorshipAssignment(): HasOne
    {
        return $this->hasOne(
            SchoolFamilySponsorshipAssignment::class,
            'guardian_membership_id'
        );
    }
}