<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderIncident;
use App\Models\ProviderOperationAttempt;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProviderOperationsService
{
    public function __construct(
        protected ProviderManager $providers,
        protected CollectionService $collections,
        protected NotificationService $notifications,
    ) {}

    public function recordAttempt(Provider $provider, string $operation, string $status, array $context = []): ProviderOperationAttempt
    {
        return ProviderOperationAttempt::query()->create([
            'provider_id' => $provider->id,
            'operation' => $operation,
            'reference' => $context['reference'] ?? null,
            'status' => $status,
            'duration_ms' => $context['duration_ms'] ?? null,
            'error' => $context['error'] ?? null,
            'request_payload' => $this->redact($context['request'] ?? null),
            'response_payload' => $this->redact($context['response'] ?? null),
            'attempted_at' => now(),
            'metadata' => $context['metadata'] ?? null,
        ]);
    }

    public function reconcileCollection(Transaction $transaction): Transaction
    {
        $locked = DB::transaction(function () use ($transaction): Transaction {
            $record = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if (! in_array($record->status, [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)) {
                return $record;
            }
            return $record;
        }, 3);

        if (! in_array($locked->status, [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)) {
            return $locked;
        }

        $provider = Provider::query()->findOrFail($locked->provider_id);
        $started = hrtime(true);
        try {
            $verification = $this->providers->verifyTransaction($provider, (string) $locked->provider_reference);
            $this->recordAttempt($provider, 'collection_reconciliation', 'success', [
                'reference' => $locked->provider_reference,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'response' => $verification,
            ]);
            return $this->collections->settleVerified($locked, $provider, $verification);
        } catch (Throwable $e) {
            DB::transaction(function () use ($locked, $e): void {
                $record = Transaction::query()->lockForUpdate()->findOrFail($locked->id);
                $metadata = $record->metadata ?? [];
                data_set($metadata, 'reconciliation.attempts', (int) data_get($metadata, 'reconciliation.attempts', 0) + 1);
                data_set($metadata, 'reconciliation.last_attempt_at', now()->toIso8601String());
                data_set($metadata, 'reconciliation.last_error', $e->getMessage());
                $record->forceFill(['status' => Transaction::STATUS_PROCESSING, 'metadata' => $metadata])->save();
            }, 3);
            $this->recordAttempt($provider, 'collection_reconciliation', 'failed', [
                'reference' => $locked->provider_reference,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function openIncident(Provider $provider, string $title, ?string $description = null, string $severity = 'warning'): ProviderIncident
    {
        $incident = ProviderIncident::query()->firstOrCreate(
            ['provider_id' => $provider->id, 'status' => ProviderIncident::STATUS_OPEN, 'title' => $title],
            ['severity' => $severity, 'description' => $description, 'opened_at' => now()]
        );
        $this->notifications->notifyAdmin('provider_incident', $title, $description ?? 'A provider incident requires attention.', ['provider_id' => $provider->id]);
        return $incident;
    }

    public function resolveOpenIncidents(Provider $provider): int
    {
        return ProviderIncident::query()->where('provider_id', $provider->id)->where('status', ProviderIncident::STATUS_OPEN)
            ->update(['status' => ProviderIncident::STATUS_RESOLVED, 'resolved_at' => now(), 'updated_at' => now()]);
    }

    private function redact(mixed $payload): mixed
    {
        if (! is_array($payload)) return $payload;
        foreach (['api_key','secret_key','password','token','authorization','webhook_secret'] as $key) {
            if (array_key_exists($key, $payload)) $payload[$key] = '[REDACTED]';
        }
        return $payload;
    }
}
