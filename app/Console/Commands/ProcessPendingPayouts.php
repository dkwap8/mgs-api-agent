<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPayoutJob;
use App\Models\Payout;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('payouts:process {--limit=100 : Maximum payouts to queue} {--stale=10 : Minutes before a processing payout is reconciled} {--max-attempts=12 : Reconciliation attempts before escalation} {--sync : Process jobs synchronously}')] 
#[Description('Queue eligible payouts and reconcile stale provider-processing payouts.')]
class ProcessPendingPayouts extends Command
{
    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $staleMinutes = max(1, (int) $this->option('stale'));
        $maxAttempts = max(1, (int) $this->option('max-attempts'));

        $payouts = Payout::query()
            ->where(function ($query) use ($staleMinutes): void {
                $query->whereIn('status', [
                    Payout::STATUS_APPROVED,
                ])->orWhere(function ($query) use ($staleMinutes): void {
                    $query->where('status', Payout::STATUS_PROCESSING)
                        ->where('processed_at', '<=', now()->subMinutes($staleMinutes));
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'reference', 'status']);

        $escalated = 0;

        foreach ($payouts as $payout) {
            $current = Payout::query()->find($payout->id);
            $metadata = $current?->metadata ?? [];
            $attempts = (int) ($metadata['reconciliation_attempts'] ?? 0);

            if (
                $current?->status === Payout::STATUS_PROCESSING
                && $attempts >= $maxAttempts
            ) {
                if (empty($metadata['reconciliation_escalated_at'])) {
                    $metadata['reconciliation_escalated_at'] = now()->toIso8601String();
                    $metadata['reconciliation_escalation_reason'] =
                        'Maximum automatic reconciliation attempts reached.';
                    $current->forceFill(['metadata' => $metadata])->save();

                    Log::critical('Payout requires manual reconciliation.', [
                        'payout_id' => $current->id,
                        'reference' => $current->reference,
                        'provider_reference' => $current->provider_reference,
                        'reconciliation_attempts' => $attempts,
                    ]);
                }

                $escalated++;
                continue;
            }

            if ($this->option('sync')) {
                ProcessPayoutJob::dispatchSync($payout->id);
            } else {
                ProcessPayoutJob::dispatch($payout->id);
            }
        }

        $this->info(sprintf(
            'Queued %d payout(s); %d payout(s) require manual reconciliation.',
            $payouts->count() - $escalated,
            $escalated
        ));

        return self::SUCCESS;
    }
}
