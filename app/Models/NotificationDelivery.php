<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    protected $fillable = [
        'notification_id', 'user_id', 'channel', 'destination', 'subject',
        'payload', 'status', 'attempts', 'next_retry_at', 'delivered_at',
        'failed_at', 'last_error', 'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
