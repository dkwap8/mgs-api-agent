<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    public const SUCCESSFUL_STATUSES = ['completed', 'successful'];
    public const PENDING_STATUSES = ['pending', 'processing'];
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const MODE_AUTOMATIC = 'automatic';
    public const MODE_MANUAL = 'manual';

    protected $fillable = [
        'merchant_id',
        'bank_account_id',
        'reference',
        'provider_reference',
        'amount',
        'fee',
        'net_amount',
        'narration',
        'status',
        'risk_level',
        'processing_mode',
        'review_reason',
        'failure_reason',
        'approved_at',
        'approved_by',
        'approved_by_admin_id',
        'funds_reserved_at',
        'processed_at',
        'refunded_at',
        'provider_response',
        'metadata',
        'business_id',
        'withdrawal_setting_id',
    ];

    protected $hidden = ['provider_response', 'metadata'];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'funds_reserved_at' => 'datetime',
        'processed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'provider_response' => 'array',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'approved_by_admin_id'
        );
    }

    public function isFinal(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED,
                self::STATUS_SUCCESSFUL,
                self::STATUS_REJECTED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
            ],
            true
        );
    }

    public function canBeReviewed(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
            ],
            true
        );
    }

    public function hasBeenRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    public function business(): BelongsTo
    {
    return $this->belongsTo(Business::class);
    }

    public function withdrawalSetting(): BelongsTo
    {
    return $this->belongsTo(
        MerchantWithdrawalSetting::class,
        'withdrawal_setting_id'
    );
    }

}