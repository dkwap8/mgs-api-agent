<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderOperationAttempt extends Model
{
    protected $fillable = [
        'provider_id', 'operation', 'reference', 'status', 'duration_ms',
        'error', 'request_payload', 'response_payload', 'attempted_at', 'metadata',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'attempted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
