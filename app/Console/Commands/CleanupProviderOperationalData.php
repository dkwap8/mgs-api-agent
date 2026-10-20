<?php

namespace App\Console\Commands;

use App\Models\ProviderHealthCheck;
use App\Models\ProviderIncident;
use App\Models\ProviderOperationAttempt;
use Illuminate\Console\Command;

class CleanupProviderOperationalData extends Command
{
    protected $signature = 'providers:cleanup {--days=90}';
    protected $description = 'Remove old provider operational records while retaining open incidents.';

    public function handle(): int
    {
        $before = now()->subDays(max(7, (int) $this->option('days')));
        $attempts = ProviderOperationAttempt::query()->where('attempted_at', '<', $before)->delete();
        $health = ProviderHealthCheck::query()->where('checked_at', '<', $before)->delete();
        $incidents = ProviderIncident::query()->where('status', ProviderIncident::STATUS_RESOLVED)->where('resolved_at', '<', $before)->delete();
        $this->info("Deleted {$attempts} attempts, {$health} health checks, and {$incidents} resolved incidents.");
        return self::SUCCESS;
    }
}
