<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'user_id',
        'title',
        'scope_type',
        'scope_id',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'scope_id' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AiRun::class, 'conversation_id')
            ->orderBy('id');
    }
}
