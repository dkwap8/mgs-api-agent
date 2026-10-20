<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderFundingCharge;
use InvalidArgumentException;

class FundingChargeService
{
    public function calculate(
        Provider $provider,
        float|int|string $amount
    ): array {
        $amount = \App\Support\Money::positive($amount);

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'The funding amount must be greater than zero.'
            );
        }

        $provider->loadMissing('fundingCharge');

        $configuration = $provider->fundingCharge;

        if (
            ! $configuration ||
            ! $configuration->is_enabled
        ) {
            return [
                'provider_id' => $provider->id,
                'provider' => $provider->name,
                'amount' => $amount,
                'charge' => '0.00',
                'total_payable' => $amount,
                'wallet_credit' => $amount,
                'collection_mode' =>
                    ProviderFundingCharge::MODE_ADD_ON,
                'charge_type' => null,
                'charge_value' => 0,
                'maximum_charge' => null,
            ];
        }

        $charge = $configuration->calculate($amount);

        $addOn = $configuration->collection_mode ===
            ProviderFundingCharge::MODE_ADD_ON;

        return [
            'provider_id' => $provider->id,
            'provider' => $provider->name,
            'amount' => $amount,
            'charge' => $charge,

            'total_payable' => $addOn
                ? (string) \App\Support\Money::decimal($amount)->plus($charge)
                : $amount,

            'wallet_credit' => $addOn
                ? $amount
                : (string) \Brick\Math\BigDecimal::max(\App\Support\Money::decimal($amount)->minus($charge), '0.00'),

            'collection_mode' =>
                $configuration->collection_mode,

            'charge_type' =>
                $configuration->charge_type,

            'charge_value' =>
                $configuration->charge_type ===
                ProviderFundingCharge::TYPE_PERCENTAGE
                    ? (string) $configuration->percentage
                    : (string) $configuration->fixed_amount,

            'maximum_charge' =>
                $configuration->maximum_charge !== null
                    ? (string) $configuration->maximum_charge
                    : null,
        ];
    }
}