<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Models\ProviderOperationAttempt;
use App\Services\ProviderManager;
use Illuminate\Console\Command;

class OptimiseProviderPriorities extends Command
{
    protected $signature = 'providers:optimise-priorities {--hours=24}';
    protected $description = 'Adjust provider priority using recent success rate and response time.';

    public function handle(ProviderManager $manager): int
    {
        $since = now()->subHours(max(1, (int) $this->option('hours')));
        $providers = Provider::query()->active()->get()->map(function (Provider $provider) use ($since): array {
            $query = ProviderOperationAttempt::query()->where('provider_id', $provider->id)->where('attempted_at', '>=', $since);
            $total = (clone $query)->count();
            $success = (clone $query)->where('status', 'success')->count();
            $average = (int) round((float) ((clone $query)->whereNotNull('duration_ms')->avg('duration_ms') ?? 999999));
            $rate = $total > 0 ? $success / $total : 0;
            return ['provider' => $provider, 'score' => ($rate * 100000) - $average, 'samples' => $total];
        })->filter(fn (array $row): bool => $row['samples'] >= 5)->sortByDesc('score')->values();

        foreach ($providers as $index => $row) {
            $row['provider']->update(['priority' => $index + 1]);
        }
        $manager->forgetCache();
        $this->info('Updated priorities for ' . $providers->count() . ' provider(s).');
        return self::SUCCESS;
    }
}
