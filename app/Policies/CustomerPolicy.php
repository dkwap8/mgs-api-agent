<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool { return $this->owns($user, $customer->merchant_id, $customer->business_id); }
    public function update(User $user, Customer $customer): bool { return $this->view($user, $customer); }
    public function verifyKyc(User $user, Customer $customer): bool { return $this->view($user, $customer); }
    private function owns(User $user, int $merchantId, ?int $businessId): bool
    {
        $merchant = $user->resolveActiveMerchant();
        return $merchant?->id === $merchantId && (! $businessId || $user->resolveActiveBusiness()?->id === $businessId);
    }
}
