<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFamilySponsorshipAssignment extends Model
{
    protected $fillable = [
        'pool_id',
        'school_id',
        'academic_cycle_id',
        'guardian_id',
        'guardian_membership_id',
        'status',
        'reason',
        'assigned_by_user_id',
        'assigned_at',
        'released_by_user_id',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'pool_id' => 'integer',
            'school_id' => 'integer',
            'academic_cycle_id' => 'integer',
            'guardian_id' => 'integer',
            'guardian_membership_id' => 'integer',
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(
            SchoolFamilySponsorshipPool::class,
            'pool_id'
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(
            GuardianMembership::class,
            'guardian_membership_id'
        );
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_by_user_id'
        );
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'released_by_user_id'
        );
    }
}