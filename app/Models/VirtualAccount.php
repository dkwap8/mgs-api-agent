<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualAccount extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'merchant_id', 'business_id', 'customer_id', 'provider_id',
        'provider_customer_id', 'account_number', 'account_name', 'bank_name',
        'bank_code', 'reference', 'status', 'is_test', 'metadata', 'expires_at',
    ];

    protected $hidden = ['metadata', 'provider_customer_id'];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'is_test' => 'boolean',
    ];

    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function isSuspended(): bool { return $this->status === self::STATUS_SUSPENDED; }
    public function isInactive(): bool { return $this->status === self::STATUS_INACTIVE; }
}
