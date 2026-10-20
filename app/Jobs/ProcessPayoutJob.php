<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessPayoutJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public int $uniqueFor = 600;

    public function __construct(public readonly int $payoutId)
    {
        $this->onQueue('payments');
    }

    public function uniqueId(): string
    {
        return (string) $this->payoutId;
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('payout:'.$this->payoutId))
                ->releaseAfter(30)
                ->expireAfter(180),
        ];
    }

    public function backoff(): array
    {
        return [15, 60, 180, 300];
    }

    public function handle(PayoutService $payoutService): void
    {
        $payout = Payout::query()->find($this->payoutId);

        if (! $payout || $payout->isFinal()) {
            return;
        }

        if ($payout->status === Payout::STATUS_PROCESSING) {
            $payoutService->reconcilePayout($payout);

            return;
        }

        $payoutService->processPayout($payout);
    }
}
