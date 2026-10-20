<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'scope', 'key_hash', 'request_hash', 'method', 'path',
        'status', 'response_status', 'response_body', 'response_headers',
        'locked_at', 'completed_at', 'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'response_headers' => 'array',
        'locked_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
