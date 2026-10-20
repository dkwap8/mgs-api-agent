<?php

namespace App\Services;

use App\Jobs\ProcessPayoutJob;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Merchant;
use App\Models\Payout;
use App\Models\SupportTicket;
use App\Models\Wallet;
use App\Models\WithdrawalOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayoutService
{
    public function __construct(
        protected WalletService $walletService,
        protected ProviderManager $providerManager,
        protected WithdrawalRuleService $withdrawalRuleService
    ) {
    }

    public function preview(
        int $merchantId,
        int $businessId,
        float|int|string $amount
    ): array {
        $merchant = Merchant::query()->findOrFail($merchantId);

        $business = Business::query()
            ->where('merchant_id', $merchant->id)
            ->with('activation')
            ->findOrFail($businessId);

        $merchantType = $this->resolveMerchantType($business);

        $todayQuery = Payout::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', [
                Payout::STATUS_FAILED,
                Payout::STATUS_REJECTED,
                Payout::STATUS_CANCELLED,
            ]);

        $withdrawnToday = (string) (clone $todayQuery)->sum('amount');
        $requestsToday = (clone $todayQuery)->count();

        $result = $this->withdrawalRuleService->evaluate(
            $merchantType,
            $amount,
            $withdrawnToday,
            $requestsToday
        );

        $balance = $this->walletService->getWalletBalance($merchantId, $businessId);
        $availableBalance = \App\Support\Money::format($balance['available_balance'] ?? 0);

        return array_merge($result, [
            'business_id' => $business->id,
            'business_name' => $business->name ?? $business->business_name ?? 'Business',
            'available_balance' => $availableBalance,
            'has_sufficient_balance' =>
                \App\Support\Money::decimal($availableBalance)->isGreaterThanOrEqualTo($result['wallet_debit']),
        ]);
    }

    public function requestPayout(
        int $merchantId,
        int $businessId,
        int $bankAccountId,
        float|int|string $amount,
        ?string $pin,
        array $metadata = []
    ): Payout {
        $merchant = Merchant::query()
            ->with('user')
            ->findOrFail($merchantId);

        $user = $merchant->user;
        if (! $user || $user->status !== 'active' || ! $merchant->isApproved() || $merchant->isSuspended()) {
            throw ValidationException::withMessages(['account' => ['The merchant account is not active.']]);
        }

        $business = Business::query()
            ->where('merchant_id', $merchantId)
            ->with('activation')
            ->findOrFail($businessId);

        if (! $business->isApproved() || $business->isSuspended()) {
            throw ValidationException::withMessages(['business' => ['The business is not active.']]);
        }

        $merchantType = $this->resolveMerchantType($business);

        $bankAccount = BankAccount::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('id', $bankAccountId)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $bankAccount->is_verified) {
            throw ValidationException::withMessages(['bank_account_id' => ['Verify this settlement account before withdrawing.']]);
        }

        if ($bankAccount->withdrawals_available_at?->isFuture()) {
            throw ValidationException::withMessages(['bank_account_id'=>['This settlement account is under the 24-hour security hold until '.$bankAccount->withdrawals_available_at->toIso8601String().'.']]);
        }

        $payout = DB::transaction(function () use (
            $merchantId, $business, $merchantType, $bankAccount, $amount, $metadata, $user
        ): Payout {
            // Serialise all withdrawal-limit checks for this business before reading daily totals.
            Wallet::query()->where('merchant_id', $merchantId)
                ->where('business_id', $business->id)->where('currency', 'NGN')
                ->lockForUpdate()->firstOrFail();

            $otp = WithdrawalOtp::query()
                ->where('request_id', $metadata['withdrawal_otp_request_id'] ?? '')
                ->where('user_id', $user->id)->where('merchant_id', $merchantId)
                ->where('business_id', $business->id)->where('bank_account_id', $bankAccount->id)
                ->lockForUpdate()->first();

            if ($otp && $otp->status === WithdrawalOtp::STATUS_USED && $otp->payout_id) {
                return Payout::query()->where('merchant_id', $merchantId)
                    ->where('business_id', $business->id)->findOrFail($otp->payout_id);
            }

            if (! $otp || $otp->status !== WithdrawalOtp::STATUS_PROCESSING || ! $otp->verified_at
                || $otp->isExpired() || \App\Support\Money::format($otp->amount) !== \App\Support\Money::format($amount)) {
                throw ValidationException::withMessages(['otp' => ['A verified withdrawal OTP matching this request is required.']]);
            }

            $preview = $this->preview($merchantId, $business->id, $amount);
            if (! $preview['has_sufficient_balance']) {
                throw ValidationException::withMessages(['amount' => ['Insufficient balance including withdrawal charges.']]);
            }

            if (! empty($metadata['risk_review_reason'])) {
                $preview['automatic']=false; $preview['processing_mode']=Payout::MODE_MANUAL;
                $preview['initial_status']=Payout::STATUS_PENDING; $preview['review_reason']=$metadata['risk_review_reason'];
            }
            $walletDebit = $preview['wallet_debit'];

            $reservationMetadata = array_merge($metadata, [
                'business_id' => $business->id,
                'merchant_type' => $merchantType,
                'requested_amount' => $preview['amount'],
                'fee' => $preview['fee'],
                'wallet_debit' => $walletDebit,
                'net_amount' => $preview['net_amount'],
                'processing_mode' => $preview['processing_mode'],
            ]);

            $payout = Payout::query()->create([
                'merchant_id' => $merchantId,
                'business_id' => $business->id,
                'withdrawal_setting_id' => $preview['setting_id'],
                'bank_account_id' => $bankAccount->id,
                'reference' => 'PO-' . Str::upper(Str::random(20)),
                'amount' => $preview['amount'],
                'fee' => $preview['fee'],
                'net_amount' => $preview['net_amount'],
                'narration' =>
                    $metadata['narration']
                    ?? 'Withdrawal to ' . $bankAccount->account_name,
                'status' => $preview['initial_status'],
                'risk_level' => $preview['automatic'] ? 'low' : 'medium',
                'processing_mode' => $preview['processing_mode'],
                'review_reason' => $preview['review_reason'],
                'funds_reserved_at' => now(),
                'metadata' => $reservationMetadata,
            ]);

            $this->walletService->reserveFunds(
                $merchantId,
                $business->id,
                $walletDebit,
                Payout::class,
                $payout->id,
                $reservationMetadata
            );

            // Commit OTP consumption and reservation together, before notifications or queue dispatch.
            $otp->update(['status' => WithdrawalOtp::STATUS_USED, 'payout_id' => $payout->id, 'used_at' => now()]);
            app(AuditService::class)->log($user->id,'payout.requested',$payout,null,['reference'=>$payout->reference,'amount'=>$payout->amount,'processing_mode'=>$payout->processing_mode]);
            return $payout;
        }, 5);

        if ($payout->processing_mode === Payout::MODE_AUTOMATIC) {
            ProcessPayoutJob::dispatch($payout->id)->afterCommit();

            return $payout->fresh([
                'bankAccount',
                'business',
                'withdrawalSetting',
            ]);
        }

        $this->notifyAdminForReview($payout);

        return $payout->fresh([
            'bankAccount',
            'business',
            'withdrawalSetting',
        ]);
    }

    public function processPayout(Payout $payout): Payout
    {
        $claimed = DB::transaction(function () use ($payout): ?Payout {
            $locked = Payout::query()
                ->lockForUpdate()
                ->findOrFail($payout->id);

            if ($locked->isFinal()) {
                return null;
            }

            if ($locked->status !== Payout::STATUS_APPROVED) {
                return null;
            }

            $locked->forceFill([
                'status' => Payout::STATUS_PROCESSING,
                'processed_at' => now(),
            ])->save();

            return $locked->fresh();
        }, 5);

        if (! $claimed) {
            return $payout->fresh([
                'bankAccount',
                'business',
                'withdrawalSetting',
            ]);
        }

        try {
            $provider = $this->providerManager->getBestProvider('payout');
            $providerService = $this->providerManager->getProviderService($provider);

            $metadata = $claimed->metadata ?? [];
            $metadata['provider_id'] = $provider->id;
            $metadata['provider_slug'] = $provider->slug;
            $claimed->forceFill(['metadata' => $metadata])->save();

            $providerPayout = clone $claimed;
            $providerPayout->amount = (string) (
                $claimed->net_amount ?? $claimed->amount
            );

            $result = $providerService->sendPayout($providerPayout);

            $status = $this->normaliseProviderStatus($result['status'] ?? null);

            if ($status === 'failed') {
                return $this->failPayout($claimed, $result);
            }

            if ($status !== 'successful') {
                return $this->markForReconciliation($claimed, $result);
            }

            return $this->completePayout($claimed, $result);
        } catch (Throwable $exception) {
            report($exception);

            return $this->markForReconciliation($claimed, [
                'status' => 'unknown',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function settleFromProviderWebhook(
        int $providerId,
        array $payload
    ): Payout {
        $reference = trim((string) ($payload['reference'] ?? ''));

        if ($reference === '') {
            throw new \RuntimeException('Payout webhook reference is missing.');
        }

        $payout = Payout::query()
            ->where(function ($query) use ($reference): void {
                $query->where('reference', $reference)
                    ->orWhere('provider_reference', $reference);
            })
            ->firstOrFail();

        $metadata = $payout->metadata ?? [];
        $expectedProviderId = isset($metadata['provider_id'])
            ? (int) $metadata['provider_id']
            : null;

        if ($expectedProviderId === null || $expectedProviderId !== $providerId) {
            throw new \RuntimeException('The payout does not belong to this provider.');
        }

        $result = [
            'status' => $payload['status'] ?? 'unknown',
            'reference' => $payout->reference,
            'provider_reference' => $payload['provider_reference']
                ?? $payout->provider_reference
                ?? $reference,
            'message' => $payload['message'] ?? null,
            'response' => $payload['raw_payload'] ?? $payload,
            'source' => 'provider_webhook',
        ];

        return match ($this->normaliseProviderStatus($result['status'])) {
            'successful' => $this->completePayout($payout, $result),
            'failed' => $this->failPayout($payout, $result),
            default => $this->markForReconciliation($payout, $result),
        };
    }

    public function reconcilePayout(Payout $payout): Payout
    {
        $payout = Payout::query()->findOrFail($payout->id);

        if ($payout->isFinal()) {
            return $payout;
        }

        try {
            $provider = $this->resolvePayoutProvider($payout);
            $result = $this->providerManager
                ->getProviderService($provider)
                ->verifyPayout($payout);

            return match ($this->normaliseProviderStatus($result['status'] ?? null)) {
                'successful' => $this->completePayout($payout, $result),
                'failed' => $this->failPayout($payout, $result),
                default => $this->markForReconciliation($payout, $result),
            };
        } catch (Throwable $exception) {
            report($exception);

            return $this->markForReconciliation($payout, [
                'status' => 'unknown',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function completePayout(Payout $payout, array $result): Payout
    {
        return DB::transaction(function () use ($payout, $result): Payout {
                $locked = Payout::query()
                    ->lockForUpdate()
                    ->findOrFail($payout->id);

                if ($locked->status === Payout::STATUS_SUCCESSFUL) {
                    return $locked;
                }

                if ($locked->isFinal()) {
                    throw new \RuntimeException(
                        'The payout was finalised before provider settlement completed.'
                    );
                }

                $this->walletService->captureReservedFunds(
                    $locked->merchant_id,
                    $locked->business_id,
                    Payout::class,
                    $locked->id,
                    'Withdrawal completed: ' . $locked->reference
                );

                $locked->forceFill([
                    'status' => Payout::STATUS_SUCCESSFUL,
                    'provider_reference' =>
                        $result['provider_reference']
                        ?? $locked->provider_reference
                        ?? $result['reference']
                        ?? null,
                    'provider_response' => $result,
                    'failure_reason' => null,
                    'processed_at' => now(),
                ])->save();

                app(AuditService::class)->log($locked->merchant->user_id,'payout.success',$locked,null,['reference'=>$locked->reference,'status'=>$locked->status]);
                app(MerchantWebhookService::class)->sendPayout($locked);
                app(NotificationService::class)->notifyUser($locked->merchant->user_id, 'payout_success', 'Payout completed', 'Your payout '.$locked->reference.' completed successfully.');
                return $locked->fresh([
                    'bankAccount',
                    'business',
                    'withdrawalSetting',
                ]);
        }, 5);
    }

    private function markForReconciliation(Payout $payout, array $providerResponse): Payout
    {
        return DB::transaction(function () use ($payout, $providerResponse): Payout {
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->id);

            if ($locked->isFinal()) {
                return $locked;
            }

            $metadata = $locked->metadata ?? [];
            $metadata['reconciliation_attempts'] = (int) ($metadata['reconciliation_attempts'] ?? 0) + 1;
            $metadata['last_reconciliation_at'] = now()->toIso8601String();

            $locked->forceFill([
                'status' => Payout::STATUS_PROCESSING,
                'failure_reason' => 'Payout outcome requires reconciliation.',
                'provider_reference' => $providerResponse['provider_reference']
                    ?? $providerResponse['reference']
                    ?? $locked->provider_reference,
                'provider_response' => $providerResponse,
                'metadata' => $metadata,
                'processed_at' => now(),
            ])->save();

            return $locked->fresh(['bankAccount', 'business', 'withdrawalSetting']);
        }, 5);
    }

    private function normaliseProviderStatus(mixed $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'success', 'successful', 'completed', 'complete', 'paid' => 'successful',
            'failed', 'failure', 'rejected', 'declined', 'cancelled', 'canceled', 'reversed' => 'failed',
            default => 'pending',
        };
    }

    private function failPayout(
        Payout $payout,
        array $providerResponse
    ): Payout {
        return DB::transaction(function () use (
            $payout,
            $providerResponse
        ): Payout {
            $locked = Payout::query()
                ->lockForUpdate()
                ->findOrFail($payout->id);

            if ($locked->isFinal()) {
                return $locked;
            }

            if (! $locked->refunded_at) {
                $this->walletService->releaseReservedFunds(
                    $locked->merchant_id,
                    $locked->business_id,
                    Payout::class,
                    $locked->id,
                    'Payout reservation released: ' . $locked->reference
                );
            }

            $locked->forceFill([
                'status' => Payout::STATUS_FAILED,
                'failure_reason' =>
                    $providerResponse['message']
                    ?? 'The withdrawal provider could not process the request.',
                'provider_response' => $providerResponse,
                'refunded_at' => $locked->refunded_at ?? now(),
            ])->save();

            app(AuditService::class)->log($locked->merchant->user_id,'payout.failed',$locked,null,['reference'=>$locked->reference,'status'=>$locked->status]);
            app(MerchantWebhookService::class)->sendPayout($locked);
            app(NotificationService::class)->notifyUser($locked->merchant->user_id, 'payout_failed', 'Payout failed', 'Your payout '.$locked->reference.' failed. Reserved funds have been released.');
            return $locked->fresh([
                'bankAccount',
                'business',
                'withdrawalSetting',
            ]);
        }, 5);
    }

    private function resolvePayoutProvider(Payout $payout): \App\Models\Provider
    {
        $providerId = (int) (($payout->metadata ?? [])['provider_id'] ?? 0);

        if ($providerId > 0) {
            $provider = $this->providerManager->findProvider($providerId);

            if ($provider) {
                return $provider;
            }
        }

        throw new \RuntimeException('The original payout provider is unavailable; manual reconciliation is required.');
    }

    private function resolveMerchantType(Business $business): string
    {
        $activation = $business->activation;

        if (! $activation) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Business activation information was not found.',
                ],
            ]);
        }

        if (
            strtolower((string) $activation->status)
            !== 'approved'
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Business activation must be approved before withdrawal.',
                ],
            ]);
        }

        $type = strtolower((string) $activation->business_type);

        if (! in_array($type, ['starter', 'registered'], true)) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Withdrawal settings are not configured for this business type.',
                ],
            ]);
        }

        return $type;
    }

    private function notifyAdminForReview(Payout $payout): void
    {
        /*
         * A support-ticket notification must never make an otherwise valid
         * withdrawal fail. The support_tickets table requires ticket_number,
         * so insert it explicitly and report any notification error without
         * rolling back or reopening the OTP request.
         */
        try {
            DB::table('support_tickets')->insert([
                'ticket_number' => 'TKT-' . Str::upper(Str::random(12)),
                'merchant_id' => $payout->merchant_id,
                'subject' => 'Withdrawal Review Required',
                'message' => sprintf(
                    "Withdrawal %s of â‚¦%s requires review.\n\nReason: %s",
                    $payout->reference,
                    number_format((float) $payout->amount, 2),
                    $payout->review_reason
                        ?: 'Administrator review required.'
                ),
                'priority' => 'high',
                'category' => 'payout',
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function verifyBankAccount(
        string $accountNumber,
        string $bankCode
    ): array {
        $provider = $this->providerManager->getBestProvider('payout');

        return $this->providerManager
            ->getProviderService($provider)
            ->verifyBankAccount(
                $accountNumber,
                $bankCode
            );
    }

    public function getPayoutStatus(string $reference): Payout
    {
        return Payout::query()
            ->where('reference', $reference)
            ->with([
                'merchant',
                'business',
                'bankAccount',
                'withdrawalSetting',
            ])
            ->firstOrFail();
    }
}