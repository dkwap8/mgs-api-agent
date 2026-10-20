<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'merchant_id',
        'business_id',
        'customer_id',
        'virtual_account_id',
        'provider_id',
        'reference',
        'provider_reference',
        'amount',
        'fee',
        'merchant_amount',
        'type',
        'status',
        'description',
        'provider_response',
        'confirmed_at',
        'metadata'
    ];

    protected $hidden = [
        'provider_response',
        'metadata',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'merchant_amount' => 'decimal:2'
    ];

    const TYPE_COLLECTION = 'collection';
    const TYPE_PAYOUT = 'payout';
    const TYPE_REFUND = 'refund';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    public const SUCCESSFUL_STATUSES = [self::STATUS_SUCCESS];
    public const PENDING_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING];
    public const FAILED_STATUSES = [self::STATUS_FAILED, self::STATUS_REVERSED];

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', self::SUCCESSFUL_STATUSES);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', self::PENDING_STATUSES);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', self::FAILED_STATUSES);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function virtualAccount()
    {
        return $this->belongsTo(VirtualAccount::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function walletTransaction()
    {
        return $this->morphOne(WalletTransaction::class, 'source');
    }
}