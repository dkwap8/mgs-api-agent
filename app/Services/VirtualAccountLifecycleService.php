<?php

namespace App\Services;

use App\Events\VirtualAccountActivated;
use App\Events\VirtualAccountRegenerated;
use App\Events\VirtualAccountSuspended;
use App\Models\AuditLog;
use App\Models\Provider;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VirtualAccountLifecycleService
{
    public function __construct(
        protected ProviderManager $providerManager,
        protected VirtualAccountService $virtualAccountService
    ) {}

    public function activate(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        $this->assertProviderAvailable($account->provider);
        return $this->changeStatus($account, VirtualAccount::STATUS_ACTIVE, 'virtual_account_activated', $userId, VirtualAccountActivated::class);
    }

    public function suspend(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return $this->changeStatus($account, VirtualAccount::STATUS_SUSPENDED, 'virtual_account_suspended', $userId, VirtualAccountSuspended::class);
    }

    public function close(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return $this->changeStatus($account, VirtualAccount::STATUS_INACTIVE, 'virtual_account_closed', $userId);
    }

    public function regenerate(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return DB::transaction(function () use ($account, $userId): VirtualAccount {
            $locked = VirtualAccount::query()->lockForUpdate()->findOrFail($account->id);
            $customer = $locked->customer()->firstOrFail();
            $oldStatus = $locked->status;

            $locked->update([
                'status' => VirtualAccount::STATUS_INACTIVE,
                'metadata' => array_merge($locked->metadata ?? [], [
                    'replaced_at' => now()->toIso8601String(),
                    'replaced_by_user_id' => $userId,
                ]),
            ]);

            $replacement = $this->virtualAccountService->createForCustomer(
                $customer,
                ['source' => 'regeneration', 'replaces_virtual_account_id' => $locked->id],
                $userId,
                $locked->provider_id
            );

            AuditLog::query()->create([
                'user_id' => $userId,
                'auditable_type' => VirtualAccount::class,
                'auditable_id' => $replacement->id,
                'event' => 'virtual_account_regenerated',
                'old_values' => ['id' => $locked->id, 'status' => $oldStatus],
                'new_values' => ['id' => $replacement->id, 'status' => $replacement->status],
            ]);

            DB::afterCommit(fn () => VirtualAccountRegenerated::dispatch($replacement, $locked->id));

            return $replacement->load(['customer', 'business', 'provider']);
        }, 3);
    }

    public function verifyHealth(VirtualAccount $account): array
    {
        $provider = $account->provider;
        $this->assertProviderAvailable($provider);
        return $this->providerManager->checkProviderHealth($provider);
    }

    private function changeStatus(
        VirtualAccount $account,
        string $status,
        string $event,
        ?int $userId,
        ?string $domainEvent = null
    ): VirtualAccount {
        $updated = DB::transaction(function () use ($account, $status, $event, $userId): VirtualAccount {
            $locked = VirtualAccount::query()->lockForUpdate()->findOrFail($account->id);
            $oldStatus = $locked->status;

            if ($oldStatus === $status) {
                return $locked;
            }

            $locked->update(['status' => $status]);
            AuditLog::query()->create([
                'user_id' => $userId,
                'auditable_type' => VirtualAccount::class,
                'auditable_id' => $locked->id,
                'event' => $event,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $status],
            ]);

            return $locked->fresh(['customer', 'business', 'provider']);
        }, 3);

        if ($domainEvent) {
            DB::afterCommit(fn () => $domainEvent::dispatch($updated));
        }

        return $updated;
    }

    private function assertProviderAvailable(?Provider $provider): void
    {
        if (! $provider || ! $provider->is_active || $provider->type !== Provider::TYPE_VIRTUAL_ACCOUNT) {
            throw ValidationException::withMessages(['provider' => ['The virtual-account provider is unavailable.']]);
        }

        if (! in_array($provider->health_status, [Provider::HEALTH_HEALTHY, Provider::HEALTH_DEGRADED], true)) {
            throw new RuntimeException('The virtual-account provider is unhealthy.');
        }
    }
}
