<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'ai_run_id',
        'role',
        'content',
        'structured_json',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'structured_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            AiConversation::class,
            'conversation_id'
        );
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(
            AiRun::class,
            'ai_run_id'
        );
    }
}
