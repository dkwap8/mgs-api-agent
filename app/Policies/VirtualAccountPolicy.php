<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualAccount;

class VirtualAccountPolicy
{
    public function view(User $user, VirtualAccount $account): bool { return $this->owns($user, $account); }
    public function update(User $user, VirtualAccount $account): bool { return $this->owns($user, $account); }
    private function owns(User $user, VirtualAccount $account): bool
    {
        return $user->resolveActiveMerchant()?->id === $account->merchant_id
            && (! $account->business_id || $user->resolveActiveBusiness()?->id === $account->business_id);
    }
}
