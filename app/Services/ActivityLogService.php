<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogService
{
    public function log(
        string $module,
        string $action,
        string $description,
        array $properties = [],
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        try {
            return ActivityLog::query()->create([
                'user_id' => request()->user() instanceof \App\Models\Admin ? null : ($userId ?? $request?->user()?->id),
                'admin_id' => request()->user() instanceof \App\Models\Admin ? request()->user()->id : null,
                'module' => $module,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'properties' => $properties,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function customerRegistered(
        int $customerId,
        int $merchantId,
        int $businessId,
        string $customerName,
        string $businessName,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'customers',
            'created',
            "{$customerName} was registered under {$businessName}.",
            [
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'customer_name' => $customerName,
                'business_name' => $businessName,
            ],
            $userId,
            $request
        );
    }

    public function customerUpdated(
        int $customerId,
        int $merchantId,
        int $businessId,
        string $customerName,
        array $changes = [],
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'customers',
            'updated',
            "Customer {$customerName} was updated.",
            [
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'changes' => $changes,
            ],
            $userId,
            $request
        );
    }

    public function kycVerified(
        int $customerId,
        int $merchantId,
        int $businessId,
        string $customerName,
        string $verificationType,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'kyc',
            'verified',
            "{$customerName} completed " . strtoupper($verificationType) . " verification.",
            [
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'verification_type' => $verificationType,
            ],
            $userId,
            $request
        );
    }

    public function virtualAccountCreated(
        int $virtualAccountId,
        int $customerId,
        int $merchantId,
        int $businessId,
        int $providerId,
        string $accountNumber,
        string $bankName,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'virtual_accounts',
            'created',
            "A {$bankName} virtual account was created.",
            [
                'virtual_account_id' => $virtualAccountId,
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'provider_id' => $providerId,
                'account_number' => $this->maskAccountNumber($accountNumber),
                'bank_name' => $bankName,
            ],
            $userId,
            $request
        );
    }

    public function virtualAccountFailed(
        int $customerId,
        int $merchantId,
        int $businessId,
        string $reason,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'virtual_accounts',
            'failed',
            'Virtual account generation failed.',
            [
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'reason' => $reason,
            ],
            $userId,
            $request
        );
    }

    public function businessApproved(
        int $activationId,
        int $merchantId,
        int $businessId,
        string $businessName,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'business_activations',
            'approved',
            "{$businessName} was approved.",
            [
                'activation_id' => $activationId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'business_name' => $businessName,
            ],
            $userId,
            $request
        );
    }

    public function businessRejected(
        int $activationId,
        int $merchantId,
        ?int $businessId,
        string $businessName,
        string $reason,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'business_activations',
            'rejected',
            "{$businessName} was rejected.",
            [
                'activation_id' => $activationId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'business_name' => $businessName,
                'reason' => $reason,
            ],
            $userId,
            $request
        );
    }

    public function payoutApproved(
        int $payoutId,
        int $merchantId,
        ?int $businessId,
        string $reference,
        float $amount,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'payouts',
            'approved',
            "Payout {$reference} was approved.",
            [
                'payout_id' => $payoutId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'reference' => $reference,
                'amount' => round($amount, 2),
            ],
            $userId,
            $request
        );
    }

    public function payoutRejected(
        int $payoutId,
        int $merchantId,
        ?int $businessId,
        string $reference,
        float $amount,
        string $reason,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'payouts',
            'rejected',
            "Payout {$reference} was rejected.",
            [
                'payout_id' => $payoutId,
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'reference' => $reference,
                'amount' => round($amount, 2),
                'reason' => $reason,
            ],
            $userId,
            $request
        );
    }

    public function providerSwitched(
        int $oldProviderId,
        string $oldProviderName,
        int $newProviderId,
        string $newProviderName,
        string $providerType,
        ?int $customerId = null
    ): ?ActivityLog {
        return $this->log(
            'providers',
            'switched',
            "Provider switched from {$oldProviderName} to {$newProviderName}.",
            [
                'old_provider_id' => $oldProviderId,
                'old_provider_name' => $oldProviderName,
                'new_provider_id' => $newProviderId,
                'new_provider_name' => $newProviderName,
                'provider_type' => $providerType,
                'customer_id' => $customerId,
            ]
        );
    }

    public function systemSettingChanged(
        string $key,
        mixed $oldValue,
        mixed $newValue,
        ?int $userId = null,
        ?Request $request = null
    ): ?ActivityLog {
        return $this->log(
            'settings',
            'updated',
            "System setting {$key} was updated.",
            [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
            $userId,
            $request
        );
    }

    private function maskAccountNumber(string $accountNumber): string
    {
        $length = strlen($accountNumber);

        if ($length <= 4) {
            return $accountNumber;
        }

        return str_repeat('*', $length - 4) . substr($accountNumber, -4);
    }
}