<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'device_name', 'ip_address',
        'platform', 'trusted', 'last_seen_at', 'revoked_at'
    ];

    protected $casts = ['trusted' => 'boolean', 'last_seen_at' => 'datetime', 'revoked_at' => 'datetime'];
}
