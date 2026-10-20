<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantWithdrawalSetting extends Model
{
    public const TYPE_STARTER = 'starter';
    public const TYPE_REGISTERED = 'registered';

    public const CHARGE_PERCENTAGE = 'percentage';
    public const CHARGE_FIXED = 'fixed';

    public const FEE_MODE_DEDUCT = 'deduct';
    public const FEE_MODE_ADD_ON = 'add_on';

    protected $fillable = [
        'merchant_type',
        'withdrawals_enabled',
        'automatic_withdrawals_enabled',
        'automatic_withdrawal_limit',
        'minimum_withdrawal_amount',
        'maximum_withdrawal_amount',
        'daily_withdrawal_limit',
        'charge_enabled',
        'charge_type',
        'percentage',
        'fixed_amount',
        'maximum_charge',
        'minimum_charge',
        'fee_collection_mode',
        'daily_request_limit',
        'metadata',
    ];

    protected $casts = [
        'withdrawals_enabled' => 'boolean',
        'automatic_withdrawals_enabled' => 'boolean',
        'automatic_withdrawal_limit' => 'decimal:2',
        'minimum_withdrawal_amount' => 'decimal:2',
        'maximum_withdrawal_amount' => 'decimal:2',
        'daily_withdrawal_limit' => 'decimal:2',
        'charge_enabled' => 'boolean',
        'percentage' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'maximum_charge' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'daily_request_limit' => 'integer',
        'metadata' => 'array',
    ];

    public function calculateFee(float|int|string $amount): string
    {
        if (! $this->charge_enabled || \App\Support\Money::decimal($amount)->isLessThanOrEqualTo(0)) return '0.00';
        return \App\Support\Money::fee($amount, (string) $this->charge_type, $this->percentage, $this->fixed_amount, $this->minimum_charge, $this->maximum_charge);
    }

    public function requiresManualReview(float|int|string $amount): bool
    {
        return ! $this->automatic_withdrawals_enabled
            || \App\Support\Money::decimal($this->automatic_withdrawal_limit)->isLessThanOrEqualTo(0)
            || \App\Support\Money::decimal($amount)->isGreaterThan(\App\Support\Money::decimal($this->automatic_withdrawal_limit));
    }
}
