<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Services\MerchantWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendWebhookJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;
    public array $backoff = [30, 120, 300, 900];

    public function __construct(public readonly int $webhookLogId)
    {
        $this->onQueue('webhooks');
    }

    public function uniqueId(): string
    {
        return 'merchant-webhook:' . $this->webhookLogId;
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->releaseAfter(30)
                ->expireAfter(120),
        ];
    }

    public function handle(MerchantWebhookService $service): void
    {
        $log = WebhookLog::query()->find($this->webhookLogId);

        if (! $log || $log->status === WebhookLog::STATUS_DELIVERED) {
            return;
        }

        $service->deliverLog($log);
    }
}
