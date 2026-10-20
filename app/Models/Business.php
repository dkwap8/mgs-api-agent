<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const VA_REQUIREMENT_NONE = 'none';
    public const VA_REQUIREMENT_BVN = 'bvn';
    public const VA_REQUIREMENT_NIN = 'nin';
    public const VA_REQUIREMENT_BVN_OR_NIN = 'bvn_or_nin';
    public const VA_REQUIREMENT_BVN_AND_NIN = 'bvn_and_nin';

    protected $fillable = [
        'merchant_id',
        'name',
        'business_code',
        'verification_status',
        'is_verified',
        'verified_at',
        'metadata',
        'activation_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function activation(): HasOne
    {
        return $this->hasOne(BusinessActivation::class)->latestOfMany();
    }

    public function activations(): HasMany
    {
        return $this->hasMany(BusinessActivation::class);
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

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'business_id');
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
