<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerKYCVerification;
use App\Models\Provider;
use App\Models\SystemSetting;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class VirtualAccountService
{
    public const REQUIREMENT_NONE = 'none';
    public const REQUIREMENT_BVN = 'bvn';
    public const REQUIREMENT_NIN = 'nin';
    public const REQUIREMENT_BVN_OR_NIN = 'bvn_or_nin';
    public const REQUIREMENT_BVN_AND_NIN = 'bvn_and_nin';

    public function __construct(
        protected ProviderManager $providerManager,
        protected NotificationService $notificationService,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function resolvedPolicy(Business $business): array
    {
        $requirement = SystemSetting::getValue(
            'virtual_account_requirement',
            self::REQUIREMENT_BVN_OR_NIN
        );

        /*
         * BVN or NIN is the minimum requirement.
         * Old or invalid values are converted automatically.
         */
        if (
            $requirement === self::REQUIREMENT_NONE
            || ! in_array(
                $requirement,
                [
                    self::REQUIREMENT_BVN,
                    self::REQUIREMENT_NIN,
                    self::REQUIREMENT_BVN_OR_NIN,
                    self::REQUIREMENT_BVN_AND_NIN,
                ],
                true
            )
        ) {
            $requirement = self::REQUIREMENT_BVN_OR_NIN;
        }

        return [
            'enabled' => SystemSetting::getBoolean(
                'virtual_accounts_enabled',
                true
            ),

            'system_enforced' => true,

            'requirement' => $requirement,

            'auto_generate' => SystemSetting::getBoolean(
                'default_auto_generate_virtual_account',
                true
            ),

            'allow_multiple' => SystemSetting::getBoolean(
                'default_allow_multiple_virtual_accounts',
                false
            ),

            'maximum_per_customer' =>
                SystemSetting::getInteger(
                    'maximum_virtual_accounts_per_customer'
                ),

            /*
             * Provider choice is controlled centrally by ProviderManager
             * through priority and health status.
             */
            'preferred_provider_id' => null,
        ];
    }

    public function requirementStatus(
        Customer $customer,
        ?Business $business = null
    ): array {
        $business ??= $customer->business;

        if (! $business) {
            return [
                'satisfied' => false,
                'requirement' => self::REQUIREMENT_BVN_OR_NIN,
                'message' => 'Customer is not linked to a business.',

                'verified' => [
                    'bvn' => false,
                    'nin' => false,
                ],

                'policy' => [
                    'enabled' => false,
                    'system_enforced' => true,
                    'requirement' =>
                        self::REQUIREMENT_BVN_OR_NIN,
                    'auto_generate' => false,
                    'allow_multiple' => false,
                    'maximum_per_customer' => null,
                    'preferred_provider_id' => null,
                ],
            ];
        }

        $policy = $this->resolvedPolicy($business);

        $verifiedTypes = CustomerKYCVerification::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'verified')
            ->whereIn('type', [
                'bvn',
                'nin',
            ])
            ->pluck('type')
            ->unique()
            ->values();

        $hasBvn = $verifiedTypes->contains('bvn');
        $hasNin = $verifiedTypes->contains('nin');

        $satisfied = match ($policy['requirement']) {
            self::REQUIREMENT_BVN =>
                $hasBvn,

            self::REQUIREMENT_NIN =>
                $hasNin,

            self::REQUIREMENT_BVN_OR_NIN =>
                $hasBvn || $hasNin,

            self::REQUIREMENT_BVN_AND_NIN =>
                $hasBvn && $hasNin,

            default => false,
        };

        return [
            'satisfied' => $satisfied,

            'requirement' =>
                $policy['requirement'],

            'message' => $satisfied
                ? 'Virtual account KYC requirement is satisfied.'
                : $this->requirementMessage(
                    $policy['requirement']
                ),

            'verified' => [
                'bvn' => $hasBvn,
                'nin' => $hasNin,
            ],

            'policy' => $policy,
        ];
    }

    public function tryAutoGenerate(
        Customer $customer,
        array $metadata = [],
        ?int $userId = null
    ): array {
        $customer->loadMissing([
            'business',
            'merchant',
            'virtualAccounts',
        ]);

        $business = $customer->business;

        if (! $business) {
            return [
                'created' => false,
                'skipped' => true,
                'reason' =>
                    'Customer is not linked to a business.',
                'account' => null,
            ];
        }

        $policy = $this->resolvedPolicy($business);

        if (! $policy['enabled']) {
            return [
                'created' => false,
                'skipped' => true,
                'reason' =>
                    'Virtual account generation is temporarily disabled by the platform administrator.',
                'account' => null,
            ];
        }

        if (! $policy['auto_generate']) {
            return [
                'created' => false,
                'skipped' => true,
                'reason' =>
                    'Automatic virtual account generation is disabled by the platform administrator.',
                'account' => null,
            ];
        }

        $requirement = $this->requirementStatus(
            $customer,
            $business
        );

        if (! $requirement['satisfied']) {
            return [
                'created' => false,
                'skipped' => true,
                'reason' => $requirement['message'],
                'requirement' => $requirement,
                'account' => null,
            ];
        }

        try {
            $account = $this->createForCustomer(
                $customer,
                $metadata,
                $userId
            );

            if ($account->wasRecentlyCreated) {
                $this->notificationService
                    ->virtualAccountCreated(
                        $customer->id,
                        $customer->merchant_id,
                        $business->id,
                        $account->id,
                        $account->account_number,
                        $account->bank_name
                    );

                $this->activityLogService
                    ->virtualAccountCreated(
                        $account->id,
                        $customer->id,
                        $customer->merchant_id,
                        $business->id,
                        $account->provider_id,
                        $account->account_number,
                        $account->bank_name,
                        $userId
                    );
            }

            return [
                'created' =>
                    $account->wasRecentlyCreated,

                'skipped' => false,

                'reason' =>
                    $account->wasRecentlyCreated
                        ? 'Virtual account generated successfully.'
                        : 'Customer already has an active virtual account.',

                'account' => $account,

                'requirement' => $requirement,
            ];
        } catch (Throwable $exception) {
            report($exception);

            Log::error(
                'Automatic virtual account generation failed',
                [
                    'customer_id' =>
                        $customer->id,

                    'merchant_id' =>
                        $customer->merchant_id,

                    'business_id' =>
                        $business->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            $this->notificationService
                ->virtualAccountFailed(
                    $customer->id,
                    $customer->merchant_id,
                    $business->id,
                    $exception->getMessage()
                );

            $this->activityLogService
                ->virtualAccountFailed(
                    $customer->id,
                    $customer->merchant_id,
                    $business->id,
                    $exception->getMessage(),
                    $userId
                );

            return [
                'created' => false,
                'skipped' => false,
                'reason' =>
                    $exception->getMessage(),
                'account' => null,
                'requirement' => $requirement,
            ];
        }
    }

    public function createForCustomer(
        Customer $customer,
        array $metadata = [],
        ?int $userId = null,
        ?int $providerId = null,
        ?string $reference = null
    ): VirtualAccount {
        $customer->loadMissing([
            'business',
            'merchant',
        ]);

        $business = $customer->business;

        if (! $business) {
            throw ValidationException::withMessages([
                'customer_id' => [
                    'Customer is not linked to a business.',
                ],
            ]);
        }

        if (
            (int) $customer->merchant_id
            !== (int) $business->merchant_id
        ) {
            throw ValidationException::withMessages([
                'customer_id' => [
                    'Customer and business ownership do not match.',
                ],
            ]);
        }

        $requirement = $this->requirementStatus(
            $customer,
            $business
        );

        /*
         * This was the missing line that caused:
         * Undefined variable $policy
         */
        $policy = $requirement['policy'];

        if (! $policy['enabled']) {
            throw ValidationException::withMessages([
                'virtual_account' => [
                    'Virtual account generation is temporarily disabled by the platform administrator.',
                ],
            ]);
        }

        if (! $requirement['satisfied']) {
            throw ValidationException::withMessages([
                'kyc' => [
                    $requirement['message'],
                ],
            ]);
        }

        /*
         * First check for an existing account when multiple accounts
         * are disabled. This must happen before checking the global
         * maximum so returning an existing account does not fail.
         */
        if (! $policy['allow_multiple']) {
            $existing = VirtualAccount::query()
                ->where(
                    'merchant_id',
                    $customer->merchant_id
                )
                ->where(
                    'business_id',
                    $business->id
                )
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'status',
                    VirtualAccount::STATUS_ACTIVE
                )
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $globalMaximum =
            $policy['maximum_per_customer'];

        if ($globalMaximum !== null) {
            $currentCount = VirtualAccount::query()
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'status',
                    VirtualAccount::STATUS_ACTIVE
                )
                ->count();

            if (
                $currentCount
                >= (int) $globalMaximum
            ) {
                throw ValidationException::withMessages([
                    'customer_id' => [
                        "This customer has reached the platform limit of {$globalMaximum} active virtual account(s).",
                    ],
                ]);
            }
        }

        $provider = $this->resolveProvider(
            $providerId
            ?? $policy['preferred_provider_id']
        );

        $result = $this->createWithFailover(
            $provider,
            $customer,
            array_merge(
                $metadata,
                [
                    'business_id' =>
                        $business->id,

                    'business_name' =>
                        $business->name,

                    'customer_reference' =>
                        $customer->external_reference,
                ]
            )
        );

        /** @var Provider $usedProvider */
        $usedProvider = $result['provider'];

        $providerResult = $result['result'];

        foreach (
            [
                'account_number',
                'account_name',
                'bank_name',
            ] as $requiredKey
        ) {
            if (
                empty(
                    $providerResult[$requiredKey]
                )
            ) {
                throw new \RuntimeException(
                    "Provider response is missing {$requiredKey}."
                );
            }
        }

        return DB::transaction(
            function () use (
                $customer,
                $business,
                $usedProvider,
                $providerResult,
                $metadata,
                $userId,
                $reference
            ): VirtualAccount {
                $account = VirtualAccount::query()
                    ->create([
                        'merchant_id' =>
                            $customer->merchant_id,

                        'business_id' =>
                            $business->id,

                        'customer_id' =>
                            $customer->id,

                        'provider_id' =>
                            $usedProvider->id,

                        'provider_customer_id' =>
                            $providerResult[
                                'provider_customer_id'
                            ] ?? null,

                        'account_number' =>
                            $providerResult[
                                'account_number'
                            ],

                        'account_name' =>
                            $providerResult[
                                'account_name'
                            ],

                        'bank_name' =>
                            $providerResult[
                                'bank_name'
                            ],

                        'bank_code' =>
                            $providerResult[
                                'bank_code'
                            ] ?? null,

                        'reference' =>
                            $reference
                            ?? 'VA-' . Str::uuid(),

                        'status' =>
                            VirtualAccount::STATUS_ACTIVE,

                        'is_test' => ! (bool) ($customer->merchant?->live_access ?? false)
                            || (bool) ($usedProvider->config['sandbox'] ?? false)
                            || in_array(strtolower((string) ($usedProvider->metadata['driver'] ?? $usedProvider->slug)), ['local','local-virtual-account','local_virtual_account'], true),

                        'metadata' =>
                            array_merge(
                                $metadata,
                                [
                                    'policy_requirement' =>
                                        $this
                                            ->resolvedPolicy(
                                                $business
                                            )[
                                                'requirement'
                                            ],
                                ]
                            ),

                        'expires_at' =>
                            $providerResult[
                                'expires_at'
                            ] ?? null,
                    ]);

                if ($userId) {
                    AuditLog::query()->create([
                        'user_id' => $userId,

                        'auditable_type' =>
                            VirtualAccount::class,

                        'auditable_id' =>
                            $account->id,

                        'event' =>
                            'virtual_account_created',

                        'new_values' =>
                            $account->toArray(),
                    ]);
                }

                app(MerchantWebhookService::class)->sendVirtualAccount($account);
                return $account->load([
                    'customer',
                    'business',
                    'provider',
                ]);
            }
        );
    }

    public function activate(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return app(VirtualAccountLifecycleService::class)->activate($account, $userId);
    }

    public function suspend(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return app(VirtualAccountLifecycleService::class)->suspend($account, $userId);
    }

    public function close(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return app(VirtualAccountLifecycleService::class)->close($account, $userId);
    }

    public function regenerate(VirtualAccount $account, ?int $userId = null): VirtualAccount
    {
        return app(VirtualAccountLifecycleService::class)->regenerate($account, $userId);
    }

    private function resolveProvider(
        ?int $providerId
    ): Provider {
        if ($providerId) {
            $provider = Provider::query()
                ->whereKey($providerId)
                ->where(
                    'type',
                    'virtual_account'
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereIn('health_status', [
                    Provider::HEALTH_HEALTHY,
                    Provider::HEALTH_DEGRADED,
                ])
                ->first();

            if ($provider && $this->providerManager->supportsVirtualAccounts($provider)) {
                return $provider;
            }
        }

        return $this->providerManager
            ->getBestProvider(
                'virtual_account'
            );
    }

    private function createWithFailover(
        Provider $provider,
        Customer $customer,
        array $metadata
    ): array {
        try {
            $service = $this
                ->providerManager
                ->getProviderService(
                    $provider
                );

            return [
                'provider' => $provider,

                'result' =>
                    $service->createVirtualAccount(
                        $customer,
                        $metadata
                    ),
            ];
        } catch (Throwable $primaryException) {
            Log::warning(
                'Primary virtual account provider failed',
                [
                    'provider_id' =>
                        $provider->id,

                    'provider' =>
                        $provider->name,

                    'customer_id' =>
                        $customer->id,

                    'error' =>
                        $primaryException
                            ->getMessage(),
                ]
            );

            $fallback = $this
                ->providerManager
                ->switchProvider(
                    $provider,
                    'virtual_account'
                );

            $fallbackService = $this
                ->providerManager
                ->getProviderService(
                    $fallback
                );

            return [
                'provider' => $fallback,

                'result' =>
                    $fallbackService
                        ->createVirtualAccount(
                            $customer,
                            $metadata
                        ),
            ];
        }
    }

    private function requirementMessage(
        string $requirement
    ): string {
        return match ($requirement) {
            self::REQUIREMENT_BVN =>
                'BVN verification is required before a virtual account can be created.',

            self::REQUIREMENT_NIN =>
                'NIN verification is required before a virtual account can be created.',

            self::REQUIREMENT_BVN_OR_NIN =>
                'BVN or NIN verification is required before a virtual account can be created.',

            self::REQUIREMENT_BVN_AND_NIN =>
                'Both BVN and NIN verification are required before a virtual account can be created.',

            default =>
                'The virtual account requirement has not been satisfied.',
        };
    }
}