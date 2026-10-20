<?php

namespace App\Services;

use App\Jobs\VerifyCollectionTransactionJob;
use App\Models\Provider;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CollectionService
{
    private const SUCCESS_STATUSES = ['success', 'successful', 'completed', 'paid', 'processed', 'approved'];
    private const FAILURE_STATUSES = ['failed', 'failure', 'declined', 'rejected', 'cancelled', 'canceled', 'reversed'];

    public function __construct(
        protected WalletService $walletService,
        protected FundingChargeService $fundingChargeService,
        protected NotificationService $notificationService,
        protected ActivityLogService $activityLogService,
        protected MerchantWebhookService $merchantWebhookService
    ) {}

    public function process(Provider $provider, array $payload): array
    {
        $providerReference = trim((string) ($payload['provider_reference'] ?? ''));

        if ($providerReference === '') {
            throw new RuntimeException('Provider transaction reference is required.');
        }

        $existing = Transaction::query()
            ->where('provider_id', $provider->id)
            ->where('provider_reference', $providerReference)
            ->first();

        if ($existing) {
            return ['duplicate' => true, 'transaction' => $existing, 'message' => 'Transaction already received.'];
        }

        $virtualAccount = VirtualAccount::query()
            ->with(['merchant', 'business', 'customer', 'provider'])
            ->where('provider_id', $provider->id)
            ->where('account_number', $payload['account_number'])
            ->where('status', VirtualAccount::STATUS_ACTIVE)
            ->where('is_test', false)
            ->first();

        if (! $virtualAccount) {
            throw new RuntimeException('The destination virtual account could not be found.');
        }

        if ((int) $virtualAccount->provider_id !== (int) $provider->id) {
            throw new RuntimeException('The virtual account does not belong to this provider.');
        }

        $charge = app(CollectionPricingService::class)->calculate(\App\Support\Money::positive($payload['amount']))
            ?? $this->fundingChargeService->calculate($provider, $payload['amount']);

        $transaction = DB::transaction(function () use ($provider, $payload, $virtualAccount, $charge): Transaction {
            $duplicate = Transaction::query()
                ->where('provider_id', $provider->id)
                ->where('provider_reference', $payload['provider_reference'])
                ->lockForUpdate()
                ->first();

            if ($duplicate) {
                return $duplicate;
            }

            return Transaction::query()->firstOrCreate([
                'provider_id' => $provider->id,
                'provider_reference' => $payload['provider_reference'],
            ], [
                'merchant_id' => $virtualAccount->merchant_id,
                'business_id' => $virtualAccount->business_id,
                'customer_id' => $virtualAccount->customer_id,
                'virtual_account_id' => $virtualAccount->id,
                'provider_id' => $provider->id,
                'reference' => 'COL-' . Str::upper((string) Str::ulid()),
                'provider_reference' => $payload['provider_reference'],
                'amount' => $charge['amount'],
                'fee' => $charge['charge'],
                'merchant_amount' => $charge['wallet_credit'],
                'type' => Transaction::TYPE_COLLECTION,
                'status' => Transaction::STATUS_PENDING,
                'description' => $payload['description'] ?? 'Virtual account funding',
                'provider_response' => $payload['raw_payload'],
                'metadata' => [
                    'business_id' => $virtualAccount->business_id,
                    'collection_mode' => $charge['collection_mode'],
                    'pricing_rule_id' => $charge['pricing_rule_id'] ?? null,
                    'total_payable' => $charge['total_payable'],
                    'sender_name' => $payload['sender_name'] ?? null,
                    'sender_account_number' => $payload['sender_account_number'] ?? null,
                    'sender_bank_name' => $payload['sender_bank_name'] ?? null,
                    'webhook_received_at' => now()->toIso8601String(),
                    'verification_status' => 'queued',
                    'webhook_status' => strtolower((string)($payload['status'] ?? 'unknown')),
                ],
            ]);
        }, 3);

        if (! $transaction->wasRecentlyCreated) {
            return ['duplicate' => true, 'transaction' => $transaction, 'message' => 'Transaction already received.'];
        }

        VerifyCollectionTransactionJob::dispatch($transaction->id)->afterCommit();

        return [
            'duplicate' => false,
            'transaction' => $transaction,
            'message' => 'Collection accepted for provider verification.',
        ];
    }

    public function settleVerified(Transaction $transaction, Provider $provider, array $verification): Transaction
    {
        $status = strtolower((string) ($verification['status'] ?? 'unknown'));

        return DB::transaction(function () use ($transaction, $provider, $verification, $status): Transaction {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (in_array($locked->status, [Transaction::STATUS_SUCCESS, Transaction::STATUS_FAILED], true)) {
                return $locked;
            }

            if ((int) $locked->provider_id !== (int) $provider->id) {
                throw new RuntimeException('Provider verification does not match the transaction provider.');
            }

            if (in_array($status, self::SUCCESS_STATUSES, true) && (
                empty($verification['provider_reference'] ?? $verification['reference'] ?? null)
                || ! isset($verification['amount']) || ! is_numeric($verification['amount'])
            )) {
                throw new RuntimeException('Successful verification requires an explicit reference and amount.');
            }

            $verifiedReference = (string) ($verification['provider_reference'] ?? $verification['reference'] ?? $locked->provider_reference);
            if ($verifiedReference !== '' && $verifiedReference !== (string) $locked->provider_reference) {
                throw new RuntimeException('Provider verification reference mismatch.');
            }

            $verifiedAmount = isset($verification['amount']) ? \App\Support\Money::format($verification['amount']) : null;
            if ($verifiedAmount !== null && \App\Support\Money::format($verifiedAmount) !== \App\Support\Money::format($locked->amount)) {
                throw new RuntimeException('Provider verification amount mismatch.');
            }

            $metadata = array_merge($locked->metadata ?? [], [
                'verification_status' => $status,
                'verified_at' => now()->toIso8601String(),
                'verification_response' => $verification,
            ]);

            if (in_array($status, self::FAILURE_STATUSES, true)) {
                $locked->update([
                    'status' => Transaction::STATUS_FAILED,
                    'provider_response' => $verification['response'] ?? $verification,
                    'metadata' => $metadata,
                ]);
                $this->merchantWebhookService->sendPaymentFailure($locked);
                return $locked->fresh();
            }

            if (! in_array($status, self::SUCCESS_STATUSES, true)) {
                $locked->update([
                    'status' => Transaction::STATUS_PROCESSING,
                    'provider_response' => $verification['response'] ?? $verification,
                    'metadata' => $metadata,
                ]);
                return $locked->fresh();
            }

            if (in_array($locked->metadata['webhook_status'] ?? '', self::FAILURE_STATUSES, true)) {
                throw new RuntimeException('A failed provider event cannot credit a wallet. The inconsistent verification outcome requires reconciliation.');
            }

            $locked->update([
                'status' => Transaction::STATUS_SUCCESS,
                'provider_response' => $verification['response'] ?? $verification,
                'confirmed_at' => now(),
                'metadata' => $metadata,
            ]);

            $this->walletService->credit(
                $locked->merchant_id,
                $locked->business_id,
                (string) $locked->merchant_amount,
                Transaction::class,
                $locked->id,
                "Virtual account funding {$locked->reference}",
                [
                    'transaction_reference' => $locked->reference,
                    'provider_reference' => $locked->provider_reference,
                    'virtual_account_id' => $locked->virtual_account_id,
                    'business_id' => $metadata['business_id'] ?? null,
                    'gross_amount' => (string) $locked->amount,
                    'funding_charge' => (string) $locked->fee,
                ]
            );

            \App\Models\Revenue::query()->firstOrCreate(['transaction_id' => $locked->id], [
                'merchant_id' => $locked->merchant_id, 'business_id' => $locked->business_id,
                'fee' => $locked->fee, 'provider_cost' => null, 'currency' => 'NGN',
            ]);

            // Persist the outgoing delivery with the credit, so a queue outage cannot lose it.
            $this->merchantWebhookService->sendCollection($locked, $locked->virtualAccount);

            DB::afterCommit(function () use ($locked, $provider): void {
                try {
                $fresh = $locked->fresh(['merchant', 'customer', 'virtualAccount', 'provider']);
                $this->notificationService->notifyAdmin(
                    'collection_received',
                    'Virtual account funded',
                    'A verified payment was received.',
                    ['transaction_id' => $fresh->id, 'merchant_id' => $fresh->merchant_id, 'amount' => (string) $fresh->amount]
                );
                $this->activityLogService->log(
                    'collections',
                    'credited',
                    "Merchant wallet credited from {$provider->name} collection.",
                    ['transaction_id' => $fresh->id, 'reference' => $fresh->reference]
                );
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

            return $locked->fresh(['merchant', 'customer', 'virtualAccount', 'provider']);
        }, 3);
    }
}
