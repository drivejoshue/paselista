<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'thinking_enabled' => 'boolean',
            'quota_units' => 'integer',
            'estimated_cost_usd' => 'decimal:8',
            'period_from' => 'date',
            'period_to' => 'date',
            'context_generated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            AiConversation::class,
            'conversation_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function previousRun(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'previous_run_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            AiMessage::class,
            'ai_run_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            AiRunEvent::class,
            'ai_run_id'
        )
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
