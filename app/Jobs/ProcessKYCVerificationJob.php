<?php

namespace App\Jobs;

use App\Models\CustomerKYCVerification;
use App\Services\KYCService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessKYCVerificationJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    public function __construct(public readonly int $verificationId)
    {
        $this->onQueue('kyc');
    }

    public function uniqueId(): string
    {
        return 'kyc-verification:'.$this->verificationId;
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->releaseAfter(30)->expireAfter(180)];
    }

    public function handle(KYCService $kycService): void
    {
        $verification = CustomerKYCVerification::query()->find($this->verificationId);
        if (! $verification || $verification->status === 'verified') {
            return;
        }
        if ($verification->next_retry_at?->isFuture()) {
            $this->release(max(30, now()->diffInSeconds($verification->next_retry_at)));
            return;
        }

        $kycService->processQueuedVerification($verification);
    }

    public function failed(?Throwable $exception): void
    {
        CustomerKYCVerification::query()
            ->whereKey($this->verificationId)
            ->where('status', 'processing')
            ->update([
                'status' => 'pending',
                'processing_at' => null,
                'next_retry_at' => now()->addMinutes((int) config('kyc.retry_delay_minutes', 10)),
                'last_error' => $exception?->getMessage() ?: 'KYC queue processing failed.',
                'updated_at' => now(),
            ]);
    }
}
