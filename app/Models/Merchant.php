<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'business_name',
        'business_email',
        'business_phone',
        'business_address',
        'cac_number',
        'cac_document',
        'verification_status',
        'live_access',
        'is_verified',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'live_access' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function businessActivations(): HasMany
    {
        return $this->hasMany(BusinessActivation::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === self::STATUS_APPROVED
            && $this->is_verified;
    }

    public function isSuspended(): bool
    {
        return $this->verification_status === self::STATUS_SUSPENDED;
    }
}
