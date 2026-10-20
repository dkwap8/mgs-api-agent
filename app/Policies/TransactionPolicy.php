<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->resolveActiveMerchant()?->id === $transaction->merchant_id || $user->hasPermission('transactions.view');
    }
}
