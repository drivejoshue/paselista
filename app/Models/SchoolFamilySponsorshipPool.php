<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolFamilySponsorshipPool extends Model
{
    protected $fillable = [
        'school_id',
        'academic_cycle_id',
        'quota_total',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'academic_cycle_id' => 'integer',
            'quota_total' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            SchoolFamilySponsorshipAssignment::class,
            'pool_id'
        );
    }

    public function usedCount(): int
    {
        return $this->assignments()
            ->where('status', 'assigned')
            ->count();
    }

    public function remainingCount(): int
    {
        return max(
            0,
            $this->quota_total - $this->usedCount()
        );
    }

    public function hasAvailability(): bool
    {
        return $this->status === 'active'
            && $this->remainingCount() > 0;
    }
}