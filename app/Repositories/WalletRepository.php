<?php

namespace App\Repositories;

use App\Models\Wallet;

class WalletRepository
{
    public function createForBusiness(
        int $merchantId,
        int $businessId,
        string $currency = 'NGN'
    ): Wallet {
        return Wallet::query()->firstOrCreate(
            [
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'currency' => strtoupper($currency),
            ],
            [
                'available_balance' => 0,
                'ledger_balance' => 0,
                'reserved_balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]
        );
    }
}
