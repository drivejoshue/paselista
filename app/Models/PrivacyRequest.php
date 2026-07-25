<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequest extends Model
{
    protected $fillable = [
        'request_code',
        'request_type',
        'full_name',
        'email',
        'relationship',
        'school_name',
        'account_reference',
        'description',
        'status',
        'ip_hash',
        'user_agent',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
}
