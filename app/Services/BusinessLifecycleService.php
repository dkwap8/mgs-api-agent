<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\BusinessActivation;
use App\Models\Merchant;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessLifecycleService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function approve(BusinessActivation $activation, Admin $administrator): BusinessActivation
    {
        $activation = DB::transaction(function () use ($activation, $administrator): BusinessActivation {
            $activation = BusinessActivation::query()
                ->with(['business', 'merchant'])
                ->lockForUpdate()
                ->findOrFail($activation->id);

            if (! $activation->business || ! $activation->merchant) {
                throw ValidationException::withMessages([
                    'activation' => ['The activation is not linked to a valid business and merchant.'],
                ]);
            }

            if ($activation->business->verification_status === Business::STATUS_SUSPENDED) {
                throw ValidationException::withMessages(['activation' => ['Reactivate a suspended business through the dedicated business operation.']]);
            }
            if ($activation->kyc_status !== 'verified' || ! $activation->kyc_verified_at) {
                throw ValidationException::withMessages(['activation' => ['Identity verification must complete before business approval.']]);
            }

            if ($activation->status !== 'approved') {
                $activation->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by_admin_id' => $administrator->id,
                    'rejected_at' => null,
                    'rejected_by_admin_id' => null,
                    'rejection_reason' => null,
                ]);
            }

            $metadata = array_filter([
                'business_type' => $activation->business_type,
                'first_name' => $activation->first_name,
                'last_name' => $activation->last_name,
                'middle_name' => $activation->middle_name,
                'business_email' => $activation->business_email,
                'business_phone' => $activation->business_phone,
                'website_url' => $activation->website_url,
                'business_category' => $activation->business_category,
                'country' => $activation->country,
                'state' => $activation->state,
                'city' => $activation->city,
            ], static fn ($value): bool => $value !== null && $value !== '');

            $activation->business->update([
                'name' => $activation->business_name,
                'verification_status' => Business::STATUS_APPROVED,
                'is_verified' => true,
                'verified_at' => now(),
                'activation_id' => $activation->id,
                'metadata' => array_merge($activation->business->metadata ?? [], $metadata),
            ]);

            $this->refreshMerchantApproval($activation->merchant);
            $this->syncSettlementBankAccount($activation);

            $this->auditService->log(
                $administrator->id,
                'business.approved',
                $activation->business,
                null,
                ['activation_id' => $activation->id]
            );

            return $activation->fresh(['merchant.user', 'business', 'user']);
        }, 3);

        $name = $activation->business_name ?? $activation->business?->name ?? 'Business';

        $this->notificationService->businessApproved(
            $activation->id,
            $activation->merchant_id,
            $activation->business_id,
            $name
        );

        $this->activityLogService->businessApproved(
            $activation->id,
            $activation->merchant_id,
            $activation->business_id,
            $name,
            $administrator->id
        );

        return $activation;
    }

    public function reject(BusinessActivation $activation, Admin $administrator, string $reason): BusinessActivation
    {
        $activation = DB::transaction(function () use ($activation, $administrator, $reason): BusinessActivation {
            $activation = BusinessActivation::query()
                ->with(['business', 'merchant'])
                ->lockForUpdate()
                ->findOrFail($activation->id);

            if ($activation->status === 'approved') {
                throw ValidationException::withMessages([
                    'activation' => ['An approved activation cannot be rejected. Suspend the business instead.'],
                ]);
            }

            $activation->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by_admin_id' => $administrator->id,
                'rejection_reason' => trim($reason),
                'approved_at' => null,
                'approved_by_admin_id' => null,
            ]);

            $activation->business?->update([
                'verification_status' => Business::STATUS_REJECTED,
                'is_verified' => false,
                'verified_at' => null,
                'activation_id' => $activation->id,
            ]);

            if ($activation->merchant) {
                $this->refreshMerchantApproval($activation->merchant);
            }

            $this->auditService->log(
                $administrator->id,
                'business.rejected',
                $activation->business,
                null,
                ['activation_id' => $activation->id, 'reason' => trim($reason)]
            );

            return $activation->fresh(['merchant.user', 'business', 'user']);
        }, 3);

        $name = $activation->business_name ?? $activation->business?->name ?? 'Business';

        $this->notificationService->businessRejected(
            $activation->id,
            $activation->merchant_id,
            $activation->business_id,
            $name,
            (string) $activation->rejection_reason
        );

        $this->activityLogService->businessRejected(
            $activation->id,
            $activation->merchant_id,
            $activation->business_id,
            $name,
            (string) $activation->rejection_reason,
            $administrator->id
        );

        return $activation;
    }

    public function suspend(Business $business, Admin $administrator, string $reason): Business
    {
        return DB::transaction(function () use ($business, $administrator, $reason): Business {
            $business = Business::query()->with('merchant')->lockForUpdate()->findOrFail($business->id);
            $old = $business->only(['verification_status', 'is_verified', 'verified_at', 'metadata']);
            $metadata = $business->metadata ?? [];
            $metadata['suspension'] = [
                'reason' => trim($reason),
                'suspended_at' => now()->toIso8601String(),
                'suspended_by' => $administrator->id,
            ];

            $business->update([
                'verification_status' => Business::STATUS_SUSPENDED,
                'is_verified' => false,
                'metadata' => $metadata,
            ]);

            $this->refreshMerchantApproval($business->merchant);
            $this->auditService->log($administrator->id, 'business.suspended', $business, $old, $business->fresh()->toArray());

            return $business->fresh('merchant');
        }, 3);
    }

    public function reactivate(Business $business, Admin $administrator): Business
    {
        return DB::transaction(function () use ($business, $administrator): Business {
            $business = Business::query()->with('merchant')->lockForUpdate()->findOrFail($business->id);

            if (! $business->activation_id) {
                throw ValidationException::withMessages([
                    'business' => ['This business has no approved activation record.'],
                ]);
            }

            $activationApproved = BusinessActivation::query()
                ->whereKey($business->activation_id)
                ->where('status', 'approved')
                ->exists();

            if (! $activationApproved) {
                throw ValidationException::withMessages([
                    'business' => ['The linked activation is not approved.'],
                ]);
            }

            $metadata = $business->metadata ?? [];
            unset($metadata['suspension']);

            $business->update([
                'verification_status' => Business::STATUS_APPROVED,
                'is_verified' => true,
                'verified_at' => $business->verified_at ?? now(),
                'metadata' => $metadata,
            ]);

            $this->refreshMerchantApproval($business->merchant);
            $this->auditService->log($administrator->id, 'business.reactivated', $business);

            return $business->fresh('merchant');
        }, 3);
    }

    private function refreshMerchantApproval(Merchant $merchant): void
    {
        $hasApprovedBusiness = $merchant->businesses()
            ->where('verification_status', Business::STATUS_APPROVED)
            ->where('is_verified', true)
            ->exists();

        $merchant->update([
            'verification_status' => $hasApprovedBusiness
                ? Merchant::STATUS_APPROVED
                : Merchant::STATUS_PENDING,
            'live_access' => $hasApprovedBusiness,
            'is_verified' => $hasApprovedBusiness,
            'verified_at' => $hasApprovedBusiness ? ($merchant->verified_at ?? now()) : null,
        ]);
    }

    private function syncSettlementBankAccount(BusinessActivation $activation): void
    {
        $accountNumber = trim((string) $activation->account_number);
        $accountName = trim((string) $activation->account_name);
        $bankName = trim((string) $activation->bank_name);

        if ($accountNumber === '' || $accountName === '' || $bankName === '') {
            return;
        }

        $bankCode = trim((string) ($activation->bank_code ?? '000')) ?: '000';

        $account = BankAccount::query()->updateOrCreate(
            [
                'merchant_id' => $activation->merchant_id,
                'business_id' => $activation->business_id,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ],
            [
                'bank_name' => $bankName,
                'account_name' => $accountName,
                'is_verified' => false,
                'verified_at' => null,
                'status' => 'active',
            ]
        );

        if (! BankAccount::query()
            ->where('merchant_id', $activation->merchant_id)
            ->where('business_id', $activation->business_id)
            ->whereKeyNot($account->id)
            ->where('is_default', true)
            ->exists()) {
            $account->update(['is_default' => true]);
        }
    }
}
