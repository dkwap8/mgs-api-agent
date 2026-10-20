<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalOtp extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'request_id',
        'user_id',
        'merchant_id',
        'business_id',
        'bank_account_id',
        'payout_id',
        'amount',
        'otp_hash',
        'payload',
        'attempts',
        'resend_count',
        'status',
        'last_sent_at',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected $hidden = ['otp_hash'];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'attempts' => 'integer',
        'resend_count' => 'integer',
        'last_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}