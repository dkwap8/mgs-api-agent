<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\Transaction;
use App\Services\CollectionService;
use App\Services\ProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class VerifyCollectionTransactionJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public array $backoff = [30, 120, 300, 900];

    public function __construct(public readonly int $transactionId)
    {
        $this->onQueue('payments');
    }

    public function uniqueId(): string
    {
        return (string) $this->transactionId;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('collection-verification:' . $this->transactionId))
            ->releaseAfter(30)
            ->expireAfter(150)];
    }

    public function handle(
        ProviderManager $providerManager,
        CollectionService $collectionService
    ): void {
        $transaction = Transaction::query()->find($this->transactionId);

        if (! $transaction || $transaction->status === Transaction::STATUS_SUCCESS) {
            return;
        }

        $provider = Provider::query()->find($transaction->provider_id);

        if (! $provider || ! $provider->is_active) {
            throw new RuntimeException('The collection provider is unavailable.');
        }

        $verification = $providerManager
            ->getProviderService($provider)
            ->verifyTransaction($transaction->provider_reference);

        $settled = $collectionService->settleVerified($transaction, $provider, $verification);
        if (in_array($settled->status, [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)) {
            throw new RuntimeException('Provider verification is not final yet.');
        }
    }
}
