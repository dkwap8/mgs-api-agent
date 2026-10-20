<?php

namespace Database\Seeders;

use App\Models\MerchantWithdrawalSetting;
use Illuminate\Database\Seeder;

class MerchantWithdrawalSettingSeeder extends Seeder
{
    public function run(): void
    {
        MerchantWithdrawalSetting::query()->updateOrCreate(
            [
                'merchant_type' => 'starter',
            ],
            [
                'withdrawals_enabled' => true,
                'automatic_withdrawals_enabled' => true,
                'automatic_withdrawal_limit' => 100000,
                'minimum_withdrawal_amount' => 500,
                'maximum_withdrawal_amount' => 500000,
                'daily_withdrawal_limit' => 500000,
                'charge_enabled' => true,
                'charge_type' => 'percentage',
                'percentage' => 1,
                'fixed_amount' => 0,
                'maximum_charge' => 100,
                'minimum_charge' => null,
                'fee_collection_mode' => 'add_on',
                'daily_request_limit' => 10,
            ]
        );

        MerchantWithdrawalSetting::query()->updateOrCreate(
            [
                'merchant_type' => 'registered',
            ],
            [
                'withdrawals_enabled' => true,
                'automatic_withdrawals_enabled' => true,
                'automatic_withdrawal_limit' => 500000,
                'minimum_withdrawal_amount' => 1000,
                'maximum_withdrawal_amount' => 5000000,
                'daily_withdrawal_limit' => 5000000,
                'charge_enabled' => true,
                'charge_type' => 'percentage',
                'percentage' => 0.5,
                'fixed_amount' => 0,
                'maximum_charge' => 250,
                'minimum_charge' => null,
                'fee_collection_mode' => 'add_on',
                'daily_request_limit' => 20,
            ]
        );
    }
}