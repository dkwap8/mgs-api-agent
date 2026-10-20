<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class PruneIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:prune {--days=1 : Also remove completed records older than this many days}';
    protected $description = 'Remove expired and stale idempotency records';

    public function handle(): int
    {
        $days = max(1, min((int) $this->option('days'), 30));
        $deleted = IdempotencyKey::query()
            ->where('status', 'completed')
            ->where(fn ($query) => $query
                ->where('expires_at', '<', now())
                ->orWhere(fn ($inner) => $inner
                    ->where('status', 'completed')
                    ->where('completed_at', '<', now()->subDays($days))))
            ->delete();

        $this->info("Pruned {$deleted} idempotency record(s).");
        return self::SUCCESS;
    }
}
