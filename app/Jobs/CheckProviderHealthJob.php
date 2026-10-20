<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Services\ProviderManager;
use App\Services\ProviderOperationsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class CheckProviderHealthJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [15, 60, 180];

    public function __construct(public int $providerId)
    {
        $this->onQueue('providers');
    }

    public function uniqueId(): string
    {
        return (string) $this->providerId;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('provider-health:' . $this->providerId))->releaseAfter(15)->expireAfter(90)];
    }

    public function handle(ProviderManager $manager, ProviderOperationsService $operations): void
    {
        $provider = Provider::query()->find($this->providerId);
        if (! $provider) {
            return;
        }

        $result = $manager->checkProviderHealth($provider);

        if (($result['status'] ?? Provider::HEALTH_UNHEALTHY) === Provider::HEALTH_UNHEALTHY) {
            $operations->openIncident(
                $provider,
                'Provider health check failed',
                (string) ($result['error'] ?? 'Provider health check returned an unhealthy result.'),
                'critical'
            );
        } else {
            $operations->resolveOpenIncidents($provider);
        }
    }
}
