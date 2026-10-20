<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKYCVerificationJob;
use App\Models\CustomerKYCVerification;
use Illuminate\Console\Command;

class ReconcileKYCVerifications extends Command
{
    protected $signature = 'kyc:reconcile {--limit=200} {--stale=15}';
    protected $description = 'Queue retryable and stale KYC verifications.';

    public function handle(): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $stale = max(1, (int) $this->option('stale'));

        $records = CustomerKYCVerification::query()
            ->where(function ($query) use ($stale): void {
                $query->where(function ($pending): void {
                    $pending->where('status', 'pending')
                        ->where(fn ($due) => $due->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()));
                })->orWhere(function ($processing) use ($stale): void {
                    $processing->where('status', 'processing')
                        ->where('processing_at', '<=', now()->subMinutes($stale));
                });
            })
            ->where('attempts', '<', (int) config('kyc.maximum_attempts', 8))
            ->oldest('updated_at')
            ->limit($limit)
            ->get(['id']);

        foreach ($records as $record) {
            ProcessKYCVerificationJob::dispatch($record->id);
        }

        $this->info("Queued {$records->count()} KYC verification(s).");
        return self::SUCCESS;
    }
}
