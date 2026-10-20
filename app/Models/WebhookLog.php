<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'webhook_endpoint_id',
        'merchant_id',
        'business_id',
        'transaction_id',
        'event',
        'url',
        'payload',
        'request_headers',
        'status_code',
        'response_body',
        'attempt',
        'status',
        'last_error',
        'delivered_at',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'request_headers' => 'array',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(
            WebhookEndpoint::class,
            'webhook_endpoint_id'
        );
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}