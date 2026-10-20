<?php

namespace App\Console\Commands;

use App\Jobs\CheckProviderHealthJob;
use App\Models\Provider;
use App\Services\ProviderManager;
use Illuminate\Console\Command;

class CheckProviderHealth extends Command
{
    protected $signature = 'providers:health {--provider=} {--sync} {--include-inactive}';
    protected $description = 'Check payment provider health and update failover availability.';

    public function handle(ProviderManager $manager): int
    {
        $query = Provider::query()->orderBy('priority')->orderBy('id');

        if (! $this->option('include-inactive')) {
            $query->where('is_active', true);
        }

        if ($slug = trim((string) $this->option('provider'))) {
            $query->where('slug', $slug);
        }

        $providers = $query->get();
        if ($providers->isEmpty()) {
            $this->warn('No matching providers were found.');
            return self::SUCCESS;
        }

        foreach ($providers as $provider) {
            if ($this->option('sync')) {
                $result = $manager->checkProviderHealth($provider);
                $this->line(sprintf('%s: %s', $provider->slug, $result['status'] ?? 'unknown'));
            } else {
                CheckProviderHealthJob::dispatch($provider->id);
            }
        }

        $this->info($this->option('sync') ? 'Provider health checks completed.' : 'Provider health checks queued.');
        return self::SUCCESS;
    }
}
