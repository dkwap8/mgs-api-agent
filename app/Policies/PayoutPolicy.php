<?php

namespace App\Policies;

use App\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    public function view(User $user, Payout $payout): bool { return $this->owns($user, $payout) || $user->hasPermission('payouts.view'); }
    public function review(User $user, Payout $payout): bool { return $user->hasPermission('payouts.review'); }
    private function owns(User $user, Payout $payout): bool
    {
        return $user->resolveActiveMerchant()?->id === $payout->merchant_id
            && (! $payout->business_id || $user->resolveActiveBusiness()?->id === $payout->business_id);
    }
}
