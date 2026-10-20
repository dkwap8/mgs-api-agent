<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    protected $casts = ['metadata' => 'array'];
    protected $fillable = [
        'user_id', 'email',
        'ip_address',
        'device',
        'status', 'location', 'metadata'
    ];
}