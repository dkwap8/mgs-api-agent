<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderFundingCharge extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    public const MODE_ADD_ON = 'add_on';
    public const MODE_DEDUCT = 'deduct';

    protected $fillable = [
        'provider_id',
        'is_enabled',
        'charge_type',
        'percentage',
        'fixed_amount',
        'maximum_charge',
        'minimum_charge',
        'collection_mode',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'percentage' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'maximum_charge' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function calculate(float|int|string $amount): string
    {
        if (! $this->is_enabled || \App\Support\Money::decimal($amount)->isLessThanOrEqualTo(0)) return '0.00';
        return \App\Support\Money::fee($amount, (string) $this->charge_type, $this->percentage, $this->fixed_amount, $this->minimum_charge, $this->maximum_charge);
    }

}