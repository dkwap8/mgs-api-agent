<?php

namespace App\Listeners;

use App\Events\VirtualAccountActivated;
use App\Events\VirtualAccountRegenerated;
use App\Events\VirtualAccountSuspended;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantVirtualAccountUpdated implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(VirtualAccountActivated|VirtualAccountSuspended|VirtualAccountRegenerated $event): void
    {
        $account = $event->virtualAccount->loadMissing('merchant.user');
        $action = match (true) {
            $event instanceof VirtualAccountActivated => 'activated',
            $event instanceof VirtualAccountSuspended => 'suspended',
            default => 'regenerated',
        };

        $data = [
            'virtual_account_id' => $account->id,
            'merchant_id' => $account->merchant_id,
            'business_id' => $account->business_id,
            'customer_id' => $account->customer_id,
            'status' => $account->status,
        ];

        $this->notifications->notifyAdmin('virtual_account_'.$action, 'Virtual account '.$action, "Virtual account {$account->account_number} was {$action}.", $data);

        if ($account->merchant?->user_id) {
            $this->notifications->notifyUser(
                $account->merchant->user_id,
                'virtual_account_'.$action,
                'Virtual account '.ucfirst($action),
                "Virtual account {$account->account_number} was {$action}.",
                $data
            );
        }
    }
}
