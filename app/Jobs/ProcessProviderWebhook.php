<?php

namespace App\Jobs;

use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessProviderWebhook implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    public function __construct(
        public readonly int $providerId,
        public readonly array $payload
    ) {
        $this->onQueue('payments');
    }

    public function uniqueId(): string
    {
        return $this->providerId . ':' . ($this->payload['reference'] ?? sha1(serialize($this->payload)));
    }

    public function backoff(): array
    {
        return [15, 60, 180, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('provider-webhook:' . $this->uniqueId()))
                ->releaseAfter(15)
                ->expireAfter(180),
        ];
    }

    public function handle(PayoutService $payoutService): void
    {
        $payoutService->settleFromProviderWebhook(
            $this->providerId,
            $this->payload
        );
    }
}
