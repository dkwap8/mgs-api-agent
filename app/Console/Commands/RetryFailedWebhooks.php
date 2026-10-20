<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedWebhookJob;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class RetryFailedWebhooks extends Command
{
    protected $signature = 'webhooks:retry {--limit=100 : Maximum webhook deliveries to queue} {--max-attempts=8 : Stop automatic retries after this many delivery attempts}';

    protected $description = 'Queue failed merchant webhook deliveries that are due for retry.';

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $maxAttempts = max(1, min((int) $this->option('max-attempts'), 50));

        $logs = WebhookLog::query()
            ->where('attempt', '<', $maxAttempts)
            ->where(function ($query): void {
                $query->where(function ($failed): void {
                    $failed->where('status', WebhookLog::STATUS_FAILED)
                        ->whereNotNull('next_retry_at')->where('next_retry_at', '<=', now());
                })->orWhere(function ($pending): void {
                    $pending->where('status', WebhookLog::STATUS_PENDING)
                        ->where('updated_at', '<=', now()->subMinutes(5));
                });
            })
            ->whereHas('endpoint', fn ($query) => $query->where('status', 'active'))
            ->oldest('next_retry_at')
            ->oldest('id')
            ->limit($limit)
            ->get(['id']);

        foreach ($logs as $log) {
            RetryFailedWebhookJob::dispatch($log->id);
        }

        $this->info(sprintf('Queued %d failed webhook delivery attempt(s).', $logs->count()));

        return self::SUCCESS;
    }
}
