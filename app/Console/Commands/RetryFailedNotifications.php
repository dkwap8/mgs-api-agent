<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDelivery;
use Illuminate\Console\Command;

class RetryFailedNotifications extends Command
{
    protected $signature = 'notifications:retry {--limit=200}';
    protected $description = 'Queue due failed notification deliveries for retry';

    public function handle(): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $ids = NotificationDelivery::query()
            ->where('status', NotificationDelivery::STATUS_FAILED)
            ->where('attempts', '<', 5)
            ->where(fn ($query) => $query->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()))
            ->oldest('next_retry_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            SendNotificationJob::dispatch((int) $id);
        }

        $this->info("Queued {$ids->count()} notification deliveries.");
        return self::SUCCESS;
    }
}
