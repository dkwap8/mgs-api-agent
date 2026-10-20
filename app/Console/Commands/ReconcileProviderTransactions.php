<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\ProviderOperationsService;
use Illuminate\Console\Command;
use Throwable;

class ReconcileProviderTransactions extends Command
{
    protected $signature = 'providers:reconcile {--limit=100} {--stale=10} {--transaction=}';
    protected $description = 'Reconcile pending and processing collection transactions with their providers.';

    public function handle(ProviderOperationsService $operations): int
    {
        $limit = min(1000, max(1, (int) $this->option('limit')));
        $query = Transaction::query()->where('type', Transaction::TYPE_COLLECTION)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING]);
        if ($id = $this->option('transaction')) {
            $query->whereKey((int) $id);
        } else {
            $query->where('updated_at', '<=', now()->subMinutes(max(1, (int) $this->option('stale'))));
        }

        $processed = 0;
        $query->oldest('updated_at')->limit($limit)->get()->each(function (Transaction $transaction) use ($operations, &$processed): void {
            try { $operations->reconcileCollection($transaction); } catch (Throwable $e) { report($e); }
            $processed++;
        });
        $this->info("Processed {$processed} collection reconciliation record(s).");
        return self::SUCCESS;
    }
}
