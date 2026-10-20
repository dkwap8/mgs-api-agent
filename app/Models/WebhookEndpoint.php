<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    public const ENVIRONMENT_TEST = 'test';
    public const ENVIRONMENT_LIVE = 'live';

    protected $fillable = [
        'merchant_id',
        'business_id',
        'url',
        'secret',
        'environment',
        'status',
        'events',
        'metadata',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'events' => 'array',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function subscribesTo(string $event): bool
    {
        $events = $this->events ?? [];

        return in_array('*', $events, true)
            || in_array($event, $events, true);
    }
}