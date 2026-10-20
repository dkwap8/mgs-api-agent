<?php

namespace App\Listeners;

use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyWalletActivity implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(WalletCredited|WalletDebited $event): void
    {
        $transaction = $event->transaction->loadMissing('wallet.merchant.user');
        $userId = $transaction->wallet?->merchant?->user_id;

        if (! $userId) {
            return;
        }

        $amount = number_format((float) $transaction->amount, 2, '.', ',');
        $currency = (string) ($transaction->wallet?->currency ?? 'NGN');

        if ($event instanceof WalletCredited) {
            $this->notifications->walletCredited($userId, $transaction->id, $transaction->reference, $amount, $currency);
            return;
        }

        $this->notifications->walletDebited($userId, $transaction->id, $transaction->reference, $amount, $currency);
    }
}
