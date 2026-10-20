<?php

namespace App\Services;

use App\Contracts\KYCProviderInterface;
use App\Jobs\ProcessKYCVerificationJob;
use App\Models\BusinessActivation;
use App\Models\Customer;
use App\Models\CustomerKYCVerification;
use App\Models\KYCIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KYCService
{
    public function __construct(
        protected VirtualAccountService $virtualAccountService,
        protected NotificationService $notificationService,
        protected ActivityLogService $activityLogService,
        protected KYCProviderInterface $verificationProvider
    ) {}

    public function queueVerification(Customer $customer, string $type, string $number, ?string $name = null): CustomerKYCVerification
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['bvn', 'nin'], true)) {
            throw new \InvalidArgumentException('Unsupported KYC verification type.');
        }

        $number = preg_replace('/\D+/', '', $number) ?? '';
        if (strlen($number) !== 11) {
            throw ValidationException::withMessages([$type => [strtoupper($type).' must contain exactly 11 digits.']]);
        }

        $hash = hash('sha256', $number);
        $this->assertIdentityAvailable($customer, $type, $hash);

        $verification = DB::transaction(function () use ($customer, $type, $number, $hash, $name): CustomerKYCVerification {
            $existing = CustomerKYCVerification::query()
                ->where('customer_id', $customer->id)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === 'verified' && hash_equals((string) $existing->identity_hash, $hash)) {
                return $existing;
            }

            $verification = $existing ?? new CustomerKYCVerification([
                'customer_id' => $customer->id,
                'type' => $type,
            ]);

            $verification->fill([
                'identity_number' => $number,
                'identity_hash' => $hash,
                'reference' => 'KYC-'.Str::upper((string) Str::ulid()),
                'provider_response' => ['queued_at' => now()->toIso8601String()],
                'status' => 'pending',
                'attempts' => 0,
                'next_retry_at' => now(),
                'processing_at' => null,
                'verified_at' => null,
                'failed_at' => null,
                'last_error' => null,
                'metadata' => array_filter(['submitted_name' => $name, 'masked_identity' => '*******'.substr($number,-4)]),
            ])->save();

            $customer->update(['kyc_status' => Customer::KYC_PENDING]);

            return $verification;
        }, 3);

        if ($verification->status !== 'verified') {
            ProcessKYCVerificationJob::dispatch($verification->id)->afterCommit();
        }

        return $verification->fresh();
    }

    public function processQueuedVerification(CustomerKYCVerification $verification): array
    {
        $verification = DB::transaction(function () use ($verification): CustomerKYCVerification {
            $locked = CustomerKYCVerification::query()->lockForUpdate()->findOrFail($verification->id);

            if ($locked->status === 'verified') {
                return $locked;
            }

            if ($locked->attempts >= (int) config('kyc.maximum_attempts', 8)) {
                $locked->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'last_error' => 'Maximum verification attempts reached.',
                ]);
                return $locked;
            }

            $locked->update([
                'status' => 'processing',
                'processing_at' => now(),
                'next_retry_at' => null,
                'attempts' => $locked->attempts + 1,
            ]);

            return $locked->fresh(['customer.business']);
        }, 3);

        if ($verification->status === 'verified') {
            return ['status' => 'verified', 'verification' => $verification];
        }
        if ($verification->status === 'failed') {
            return ['status' => 'failed', 'message' => $verification->last_error];
        }

        $customer = $verification->customer;
        $type = $verification->type;
        $number = (string) $verification->identity_number;
        $hash = (string) $verification->identity_hash;
        $name = $verification->metadata['submitted_name'] ?? null;

        $this->assertIdentityAvailable($customer, $type, $hash);

        $identity = KYCIdentity::query()
            ->where($type.'_hash', $hash)
            ->where('status', 'verified')
            ->first();

        if (! $identity) {
            $result = $this->verificationProvider->verify($type, $number, $name);
            $status = strtolower((string) ($result['status'] ?? 'failed'));

            if ($status !== 'verified') {
                return $this->recordUnsuccessfulAttempt($verification, $customer, $result);
            }

            try {
                $identity = KYCIdentity::query()->create([
                    'bvn_hash' => $type === 'bvn' ? $hash : null,
                    'nin_hash' => $type === 'nin' ? $hash : null,
                    'verified_name' => $result['name'] ?? $name,
                    'date_of_birth' => $result['dob'] ?? null,
                    'verification_provider' => config('kyc.default_provider', 'configured-http'),
                    'verification_reference' => $result['reference'] ?? $verification->reference,
                    'status' => 'verified',
                    'verified_at' => now(),
                    'provider_response' => $this->sanitiseProviderResponse($result),
                ]);
            } catch (QueryException $exception) {
                $identity = KYCIdentity::query()
                    ->where($type.'_hash', $hash)
                    ->where('status', 'verified')
                    ->first();
                if (! $identity) {
                    throw $exception;
                }
            }
        }

        return $this->linkIdentityToCustomer($customer, $identity, $type, $verification);
    }

    public function verifyBVN(int $customerId, string $bvn, ?string $name = null): array
    {
        $verification = $this->queueVerification(Customer::query()->findOrFail($customerId), 'bvn', $bvn, $name);
        return ['status' => $verification->status, 'verification' => $verification];
    }

    public function verifyNIN(int $customerId, string $nin, ?string $name = null): array
    {
        $verification = $this->queueVerification(Customer::query()->findOrFail($customerId), 'nin', $nin, $name);
        return ['status' => $verification->status, 'verification' => $verification];
    }

    protected function linkIdentityToCustomer(Customer $customer, KYCIdentity $identity, string $type, ?CustomerKYCVerification $verification = null): array
    {
        $result = DB::transaction(function () use ($customer, $identity, $type, $verification): array {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $this->assertIdentityAvailable($customer, $type, (string) ($type === 'bvn' ? $identity->bvn_hash : $identity->nin_hash));

            $customer->update(['kyc_identity_id' => $identity->id, 'kyc_status' => Customer::KYC_VERIFIED]);

            $verification ??= CustomerKYCVerification::query()->firstOrNew([
                'customer_id' => $customer->id,
                'type' => $type,
            ]);

            $verification->fill([
                'reference' => $identity->verification_reference ?: 'KYC-'.Str::upper((string) Str::ulid()),
                'provider_response' => $this->sanitiseProviderResponse($identity->provider_response ?? []),
                'identity_number' => null,
                'status' => 'verified',
                'verified_at' => now(),
                'processing_at' => null,
                'next_retry_at' => null,
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            BusinessActivation::query()
                ->where('owner_customer_id', $customer->id)
                ->whereIn('kyc_status', ['pending', 'processing', 'failed'])
                ->update([
                    'kyc_status' => 'verified',
                    'kyc_verified_at' => now(),
                    'kyc_failure_reason' => null,
                    'updated_at' => now(),
                ]);

            $customer = $customer->fresh(['business', 'merchant', 'virtualAccounts', 'kycIdentity']);
            $virtualAccount = $this->virtualAccountService->tryAutoGenerate($customer);

            return [
                'status' => 'verified',
                'verification_type' => $type,
                'customer' => $customer,
                'identity' => $identity,
                'virtual_account' => $virtualAccount,
            ];
        }, 3);

        $this->notificationService->kycVerified(
            $customer->id,
            $customer->merchant_id,
            $customer->business_id,
            $customer->fullName(),
            $type
        );
        $this->activityLogService->kycVerified(
            $customer->id,
            $customer->merchant_id,
            $customer->business_id,
            $customer->fullName(),
            $type
        );

        return $result;
    }

    protected function recordUnsuccessfulAttempt(CustomerKYCVerification $verification, Customer $customer, array $result): array
    {
        $retryable = (bool) ($result['retryable'] ?? (($result['status'] ?? null) === 'pending'));
        $attempts = (int) $verification->attempts;
        $canRetry = $retryable && $attempts < (int) config('kyc.maximum_attempts', 8);
        $status = $canRetry ? 'pending' : 'failed';
        $message = $result['message'] ?? strtoupper($verification->type).' verification failed.';

        $verification->update([
            'status' => $status,
            'provider_response' => $this->sanitiseProviderResponse($result),
            'processing_at' => null,
            'next_retry_at' => $canRetry ? now()->addMinutes((int) config('kyc.retry_delay_minutes', 10)) : null,
            'failed_at' => $canRetry ? null : now(),
            'last_error' => $message,
        ]);

        if (! $canRetry) {
            $customer->update(['kyc_status' => Customer::KYC_FAILED]);
            BusinessActivation::query()->where('owner_customer_id', $customer->id)->update([
                'kyc_status' => 'failed',
                'kyc_failure_reason' => $message,
                'updated_at' => now(),
            ]);
        }

        return ['status' => $status, 'message' => $message, 'retryable' => $canRetry];
    }

    protected function assertIdentityAvailable(Customer $customer, string $type, string $hash): void
    {
        if ($hash === '') {
            return;
        }

        $query = CustomerKYCVerification::query()
            ->where('identity_hash', $hash)
            ->where('status', 'verified')
            ->where('customer_id', '!=', $customer->id);

        if (config('kyc.duplicate_scope', 'platform') === 'business') {
            $query->whereHas('customer', fn ($q) => $q->where('business_id', $customer->business_id));
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                $type => [strtoupper($type).' is already linked to another customer.'],
            ]);
        }
    }

    protected function sanitiseProviderResponse(array $response): array
    {
        $sensitive=['nin','bvn','identity_number','identity_hash','number','nin_number','bvn_number','identitynumber','bvnnumber','ninnumber'];
        foreach ($response as $key=>$value) {
            if (in_array(strtolower((string)$key),$sensitive,true)) unset($response[$key]);
            elseif (is_array($value)) $response[$key]=$this->sanitiseProviderResponse($value);
        }
        return $response;
    }

    public function getCustomerKYCStatus(int $customerId): array
    {
        $customer = Customer::query()->with('kycIdentity')->findOrFail($customerId);
        $verifications = CustomerKYCVerification::query()
            ->where('customer_id', $customer->id)
            ->get(['id', 'type', 'reference', 'status', 'attempts', 'next_retry_at', 'verified_at', 'failed_at', 'last_error', 'created_at', 'updated_at']);

        $verifiedTypes = $verifications->where('status', 'verified')->pluck('type')->unique()->values()->all();

        return [
            'kyc_status' => $customer->kyc_status,
            'verified_types' => $verifiedTypes,
            'bvn_verified' => in_array('bvn', $verifiedTypes, true),
            'nin_verified' => in_array('nin', $verifiedTypes, true),
            'verified_name' => $customer->kycIdentity?->verified_name,
            'verified_at' => $customer->kycIdentity?->verified_at,
            'verification_provider' => $customer->kycIdentity?->verification_provider,
            'verifications' => $verifications,
        ];
    }

    public function isKYCVerified(int $customerId): bool
    {
        return Customer::query()->whereKey($customerId)->where('kyc_status', Customer::KYC_VERIFIED)->exists();
    }
}
