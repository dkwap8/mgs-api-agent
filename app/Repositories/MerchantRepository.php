<?php

namespace App\Repositories;

use App\Models\Merchant;

class MerchantRepository
{
    public function approve(Merchant $merchant): Merchant
    {
        $merchant->update([
            'verification_status' => 'approved',
            'live_access' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return $merchant;
    }

    public function reject(Merchant $merchant): Merchant
    {
        $merchant->update([
            'verification_status' => 'rejected',
            'live_access' => false,
            'is_verified' => false,
        ]);

        return $merchant;
    }
}
