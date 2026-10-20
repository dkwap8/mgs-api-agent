<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Services\CollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessCollectionWebhookJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public array $backoff = [15, 60, 180, 300];

    public function __construct(
        public readonly int $providerId,
        public readonly array $payload
    ) {
        $this->onQueue('payments');
    }

    public function uniqueId(): string
    {
        return $this->providerId . ':' . ($this->payload['provider_reference'] ?? 'unknown');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('collection:' . $this->uniqueId()))
            ->releaseAfter(15)
            ->expireAfter(150)];
    }

    public function handle(CollectionService $collectionService): void
    {
        $provider = Provider::query()->find($this->providerId);

        if (! $provider || ! $provider->is_active) {
            return;
        }

        $collectionService->process($provider, $this->payload);
    }
}
