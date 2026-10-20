<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->resolveActiveMerchant()?->id === $wallet->merchant_id || $user->hasPermission('wallets.view');
    }

    public function adjust(User $user, Wallet $wallet): bool
    {
        return $user->hasPermission('wallets.adjust');
    }
}
