<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
{
    public function view(User $user, Merchant $merchant): bool { return $merchant->user_id === $user->id || $user->hasPermission('merchants.view'); }
    public function update(User $user, Merchant $merchant): bool { return $merchant->user_id === $user->id || $user->hasPermission('merchants.manage'); }
}
