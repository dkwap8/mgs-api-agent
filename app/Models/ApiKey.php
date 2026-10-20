<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    public const ENVIRONMENT_TEST = 'test';
    public const ENVIRONMENT_LIVE = 'live';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'merchant_id', 'business_id', 'public_key', 'secret_key_hash', 'secret_key_fingerprint', 'webhook_secret',
        'environment', 'status', 'ip_whitelist', 'last_used_at',
        'revoked_at', 'last_rotated_at',
    ];

    protected $hidden = ['secret_key_hash', 'secret_key_fingerprint', 'webhook_secret'];

    protected $casts = [
        'ip_whitelist' => 'array',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_rotated_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function isTest(): bool { return $this->environment === self::ENVIRONMENT_TEST; }
    public function isLive(): bool { return $this->environment === self::ENVIRONMENT_LIVE; }
    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE && $this->revoked_at === null; }
}
