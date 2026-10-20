<?php

namespace App\Services;

use App\Contracts\ProviderInterface;
use App\Models\Provider;
use App\Models\ProviderHealthCheck;
use App\Models\ProviderOperationAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProviderManager
{
    public const CACHE_KEY = 'provider_manager.active_providers';

    private ?Collection $providers = null;

    public function __construct(
        protected ProviderServiceFactory $factory,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function loadProviders(bool $force = false): Collection
    {
        if ($force) {
            $this->forgetCache();
        }

        if ($this->providers instanceof Collection) {
            return $this->providers;
        }

        $this->providers = Cache::remember(
            self::CACHE_KEY,
            now()->addMinute(),
            fn (): Collection => Provider::query()
                ->active()
                ->whereIn('health_status', [
                    Provider::HEALTH_HEALTHY,
                    Provider::HEALTH_DEGRADED,
                ])
                ->orderByRaw("CASE health_status WHEN 'healthy' THEN 1 WHEN 'degraded' THEN 2 ELSE 3 END")
                ->orderBy('priority')
                ->orderBy('id')
                ->get()
        );

        return $this->providers;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->providers = null;
    }

    public function getProvidersForType(string $type, array $excludeProviderIds = []): Collection
    {
        return $this->loadProviders()
            ->filter(fn (Provider $provider): bool => $provider->type === $type || (bool) data_get($provider->metadata ?? [], 'capabilities.' . $type, false))
            ->reject(fn (Provider $provider): bool => in_array($provider->id, $excludeProviderIds, true))
            ->reject(fn (Provider $provider): bool => $this->circuitIsOpen($provider))
            ->values();
    }

    public function findProvider(int $providerId): ?Provider
    {
        return Provider::query()->find($providerId);
    }

    public function getBestProvider(string $type): Provider
    {
        $provider = $this->getProvidersForType($type)->first();

        if (! $provider) {
            throw new RuntimeException("No active provider is available for type: {$type}");
        }

        return $provider;
    }

    public function switchProvider(Provider $currentProvider, string $type): Provider
    {
        Log::warning('Switching payment provider.', [
            'current_provider_id' => $currentProvider->id,
            'current_provider' => $currentProvider->slug,
            'type' => $type,
        ]);

        $provider = $this->getProvidersForType($type, [$currentProvider->id])->first();

        if (! $provider) {
            throw new RuntimeException("No fallback provider is available for type: {$type}");
        }

        return $provider;
    }

    public function getProviderService(Provider $provider): ProviderInterface
    {
        return $this->factory->make($provider);
    }


    public function supportsVirtualAccounts(Provider $provider): bool
    {
        return $provider->type === Provider::TYPE_VIRTUAL_ACCOUNT
            && $provider->is_active
            && (bool) data_get($provider->metadata ?? [], 'capabilities.virtual_accounts', true);
    }

    public function supportsTransactionVerification(Provider $provider): bool
    {
        return (bool) data_get($provider->metadata ?? [], 'capabilities.transaction_verification', true);
    }

    public function getAvailableVirtualAccountProviders(): Collection
    {
        return $this->getProvidersForType(Provider::TYPE_VIRTUAL_ACCOUNT)
            ->filter(fn (Provider $provider): bool => $this->supportsVirtualAccounts($provider))
            ->values();
    }

    public function verifyTransaction(Provider $provider, string $reference): array
    {
        if (! $this->supportsTransactionVerification($provider)) {
            throw new RuntimeException("Provider {$provider->slug} does not support transaction verification.");
        }

        return $this->getProviderService($provider)->verifyTransaction($reference);
    }

    public function executeWithFailover(string $type, callable $operation): array
    {
        $attempts = [];

        foreach ($this->getProvidersForType($type) as $provider) {
            $started = hrtime(true);
            try {
                $service = $this->getProviderService($provider);
                $result = $operation($service, $provider);
                ProviderOperationAttempt::query()->create([
                    'provider_id' => $provider->id,
                    'operation' => $type,
                    'status' => 'success',
                    'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                    'response_payload' => is_array($result) ? $result : ['result' => $result],
                    'attempted_at' => now(),
                ]);

                return [
                    'provider' => $provider,
                    'result' => $result,
                    'attempts' => $attempts,
                ];
            } catch (Throwable $exception) {
                report($exception);

                $attempts[] = [
                    'provider_id' => $provider->id,
                    'provider' => $provider->slug,
                    'message' => $exception->getMessage(),
                ];
                ProviderOperationAttempt::query()->create([
                    'provider_id' => $provider->id,
                    'operation' => $type,
                    'status' => 'failed',
                    'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                    'error' => $exception->getMessage(),
                    'attempted_at' => now(),
                ]);

                Log::warning('Provider operation failed; trying the next provider.', end($attempts));
            }
        }

        throw new RuntimeException('All available providers failed for type: ' . $type);
    }

    public function checkProviderHealth(Provider $provider): array
    {
        try {
            $result = $this->getProviderService($provider)->healthCheck();
            $healthStatus = $result['status'] ?? Provider::HEALTH_UNHEALTHY;

            if (! in_array($healthStatus, [
                Provider::HEALTH_HEALTHY,
                Provider::HEALTH_DEGRADED,
                Provider::HEALTH_UNHEALTHY,
            ], true)) {
                $healthStatus = Provider::HEALTH_UNHEALTHY;
            }

            $checkedAt = now();
            $metadata = $provider->metadata ?? [];
            $failures = $healthStatus === Provider::HEALTH_UNHEALTHY
                ? ((int) data_get($metadata, 'circuit.consecutive_failures', 0)) + 1
                : 0;
            $threshold = max(1, (int) data_get($metadata, 'circuit.failure_threshold', 3));
            $cooldown = max(1, (int) data_get($metadata, 'circuit.cooldown_minutes', 5));
            data_set($metadata, 'circuit.consecutive_failures', $failures);
            data_set($metadata, 'circuit.open_until', $failures >= $threshold ? $checkedAt->copy()->addMinutes($cooldown)->toIso8601String() : null);
            data_set($metadata, 'last_response_time_ms', $result['response_time_ms'] ?? $result['response_time'] ?? null);
            data_set($metadata, 'last_status_code', $result['status_code'] ?? null);
            data_set($metadata, 'last_health_error', $result['error'] ?? null);

            $provider->update([
                'last_health_check' => $checkedAt,
                'health_status' => $healthStatus,
                'metadata' => $metadata,
            ]);

            ProviderHealthCheck::query()->create([
                'provider_id' => $provider->id,
                'status' => $healthStatus,
                'status_code' => $result['status_code'] ?? null,
                'response_time_ms' => $result['response_time_ms'] ?? $result['response_time'] ?? null,
                'error' => $result['error'] ?? null,
                'checked_at' => $checkedAt,
                'metadata' => ['provider_response' => $result],
            ]);

            $this->forgetCache();

            return $result;
        } catch (Throwable $exception) {
            report($exception);

            $checkedAt = now();
            $metadata = $provider->metadata ?? [];
            $failures = ((int) data_get($metadata, 'circuit.consecutive_failures', 0)) + 1;
            $threshold = max(1, (int) data_get($metadata, 'circuit.failure_threshold', 3));
            $cooldown = max(1, (int) data_get($metadata, 'circuit.cooldown_minutes', 5));
            data_set($metadata, 'circuit.consecutive_failures', $failures);
            data_set($metadata, 'circuit.open_until', $failures >= $threshold ? $checkedAt->copy()->addMinutes($cooldown)->toIso8601String() : null);
            data_set($metadata, 'last_health_error', $exception->getMessage());

            $provider->update([
                'last_health_check' => $checkedAt,
                'health_status' => Provider::HEALTH_UNHEALTHY,
                'metadata' => $metadata,
            ]);

            ProviderHealthCheck::query()->create([
                'provider_id' => $provider->id,
                'status' => Provider::HEALTH_UNHEALTHY,
                'error' => $exception->getMessage(),
                'checked_at' => $checkedAt,
            ]);

            $this->forgetCache();

            return [
                'status' => Provider::HEALTH_UNHEALTHY,
                'error' => $exception->getMessage(),
            ];
        }
    }


    public function circuitIsOpen(Provider $provider): bool
    {
        $openUntil = data_get($provider->metadata ?? [], 'circuit.open_until');

        return is_string($openUntil) && $openUntil !== '' && now()->lt(\Illuminate\Support\Carbon::parse($openUntil));
    }

    public function resetCircuit(Provider $provider): Provider
    {
        $metadata = $provider->metadata ?? [];
        data_set($metadata, 'circuit.consecutive_failures', 0);
        data_set($metadata, 'circuit.open_until', null);
        $provider->forceFill(['metadata' => $metadata])->save();
        $this->forgetCache();

        return $provider->fresh();
    }

    public function getProviderConfig(Provider $provider): array
    {
        return $provider->config ?? [];
    }
}
