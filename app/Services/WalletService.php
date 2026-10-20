<?php

namespace App\Services;

use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public function createWallet(int $merchantId, int $businessId, string $currency = 'NGN'): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'currency' => strtoupper($currency),
            ],
            [
                'available_balance' => 0,
                'ledger_balance' => 0,
                'reserved_balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]
        );
    }

    public function credit(
        int $merchantId,
        int $businessId,
        float|int|string $amount,
        string $sourceType,
        int|string|null $sourceId,
        string $description = '',
        array $metadata = []
    ): WalletTransaction {
        $amount = $this->normaliseAmount($amount);

        $transaction = DB::transaction(function () use (
            $merchantId,
            $businessId,
            $amount,
            $sourceType,
            $sourceId,
            $description,
            $metadata
        ): WalletTransaction {
            $wallet = $this->lockWallet($merchantId, $businessId);
            $this->ensureWalletCanTransact($wallet);

            $existing = $this->findSourceTransaction(
                $wallet,
                $sourceType,
                $sourceId,
                ['credit', 'refund', 'adjustment'],
                [WalletTransaction::STATUS_COMPLETED]
            );

            if ($existing) {
                return $existing;
            }

            $balanceBefore = $this->money($wallet->available_balance);
            $balanceAfter = $this->add($balanceBefore, $amount);
            $ledgerAfter = $this->add($wallet->ledger_balance, $amount);

            $wallet->forceFill([
                'available_balance' => $balanceAfter,
                'ledger_balance' => $ledgerAfter,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'reference' => $this->reference('WLT'),
                'type' => 'credit',
                'status' => WalletTransaction::STATUS_COMPLETED,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'metadata' => $metadata,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        }, 5);

        WalletCredited::dispatch($transaction);

        Log::info('Wallet credited.', [
            'wallet_transaction_id' => $transaction->id,
            'merchant_id' => $merchantId,
            'business_id' => $businessId,
            'amount' => (string) $transaction->amount,
            'reference' => $transaction->reference,
        ]);

        return $transaction;
    }

    public function debit(
        int $merchantId,
        int $businessId,
        float|int|string $amount,
        string $sourceType,
        int|string|null $sourceId,
        string $description = '',
        array $metadata = []
    ): WalletTransaction {
        $amount = $this->normaliseAmount($amount);

        $transaction = DB::transaction(function () use (
            $merchantId,
            $businessId,
            $amount,
            $sourceType,
            $sourceId,
            $description,
            $metadata
        ): WalletTransaction {
            $wallet = $this->lockWallet($merchantId, $businessId);
            $this->ensureWalletCanTransact($wallet);

            $existing = $this->findSourceTransaction(
                $wallet,
                $sourceType,
                $sourceId,
                ['debit', 'fee'],
                [WalletTransaction::STATUS_COMPLETED]
            );

            if ($existing) {
                return $existing;
            }

            $this->ensureAvailableBalance($wallet, $amount);

            $balanceBefore = $this->money($wallet->available_balance);
            $balanceAfter = $this->subtract($balanceBefore, $amount);
            $ledgerAfter = $this->subtract($wallet->ledger_balance, $amount);

            $wallet->forceFill([
                'available_balance' => $balanceAfter,
                'ledger_balance' => $ledgerAfter,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'reference' => $this->reference('WLT'),
                'type' => 'debit',
                'status' => WalletTransaction::STATUS_COMPLETED,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'metadata' => $metadata,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        }, 5);

        WalletDebited::dispatch($transaction);

        return $transaction;
    }

    public function reserveFunds(
        int $merchantId,
        int $businessId,
        float|int|string $amount,
        string $sourceType,
        int|string $sourceId,
        array $metadata = []
    ): WalletTransaction {
        $amount = $this->normaliseAmount($amount);

        return DB::transaction(function () use (
            $merchantId,
            $businessId,
            $amount,
            $sourceType,
            $sourceId,
            $metadata
        ): WalletTransaction {
            $wallet = $this->lockWallet($merchantId, $businessId);
            $this->ensureWalletCanTransact($wallet);

            $existing = $this->findSourceTransaction(
                $wallet,
                $sourceType,
                $sourceId,
                ['debit'],
                [
                    WalletTransaction::STATUS_PENDING,
                    WalletTransaction::STATUS_COMPLETED,
                    WalletTransaction::STATUS_REVERSED,
                ]
            );

            if ($existing) {
                if ($this->money($existing->amount) !== $amount) {
                    throw new RuntimeException(
                        'A wallet reservation already exists for this source with a different amount.'
                    );
                }

                return $existing;
            }

            $this->ensureAvailableBalance($wallet, $amount);

            $balanceBefore = $this->money($wallet->available_balance);
            $balanceAfter = $this->subtract($balanceBefore, $amount);

            $wallet->forceFill([
                'available_balance' => $balanceAfter,
                'reserved_balance' => $this->add(
                    $wallet->reserved_balance,
                    $amount
                ),
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'reference' => $this->reference('RES'),
                'type' => 'debit',
                'status' => WalletTransaction::STATUS_PENDING,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Funds reserved for ' . class_basename($sourceType),
                'metadata' => $metadata,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        }, 5);
    }

    public function captureReservedFunds(
        int $merchantId,
        int $businessId,
        string $sourceType,
        int|string $sourceId,
        string $description = ''
    ): WalletTransaction {
        $transaction = DB::transaction(function () use (
            $merchantId,
            $businessId,
            $sourceType,
            $sourceId,
            $description
        ): WalletTransaction {
            $wallet = $this->lockWallet($merchantId, $businessId);
            $reservation = $this->lockReservation(
                $wallet,
                $sourceType,
                $sourceId
            );

            if ($reservation->status === WalletTransaction::STATUS_COMPLETED) {
                return $reservation;
            }

            if ($reservation->status === WalletTransaction::STATUS_REVERSED) {
                throw new RuntimeException(
                    'The wallet reservation has already been released.'
                );
            }

            $amount = $this->money($reservation->amount);

            if ($this->compare($wallet->reserved_balance, $amount) < 0) {
                throw new RuntimeException(
                    'The wallet reserved balance is inconsistent with this transaction.'
                );
            }

            $wallet->forceFill([
                'reserved_balance' => $this->subtract(
                    $wallet->reserved_balance,
                    $amount
                ),
                'ledger_balance' => $this->subtract(
                    $wallet->ledger_balance,
                    $amount
                ),
            ])->save();

            $reservation->forceFill([
                'status' => WalletTransaction::STATUS_COMPLETED,
                'description' => $description ?: $reservation->description,
                'metadata' => array_merge(
                    $reservation->metadata ?? [],
                    ['captured_at' => now()->toIso8601String()]
                ),
            ])->save();

            return $reservation->refresh();
        }, 5);

        WalletDebited::dispatch($transaction);

        return $transaction;
    }

    public function releaseReservedFunds(
        int $merchantId,
        int $businessId,
        string $sourceType,
        int|string $sourceId,
        string $description = 'Reserved funds released'
    ): WalletTransaction {
        return DB::transaction(function () use (
            $merchantId,
            $businessId,
            $sourceType,
            $sourceId,
            $description
        ): WalletTransaction {
            $wallet = $this->lockWallet($merchantId, $businessId);
            $reservation = $this->lockReservation(
                $wallet,
                $sourceType,
                $sourceId
            );

            if ($reservation->status === WalletTransaction::STATUS_REVERSED) {
                return $reservation;
            }

            if ($reservation->status === WalletTransaction::STATUS_COMPLETED) {
                throw new RuntimeException(
                    'A completed wallet reservation cannot be released.'
                );
            }

            $amount = $this->money($reservation->amount);

            if ($this->compare($wallet->reserved_balance, $amount) < 0) {
                throw new RuntimeException(
                    'The wallet reserved balance is inconsistent with this transaction.'
                );
            }

            $balanceBefore = $this->money($wallet->available_balance);
            $balanceAfter = $this->add($balanceBefore, $amount);

            $wallet->forceFill([
                'available_balance' => $balanceAfter,
                'reserved_balance' => $this->subtract(
                    $wallet->reserved_balance,
                    $amount
                ),
            ])->save();

            $reservation->forceFill([
                'status' => WalletTransaction::STATUS_REVERSED,
                'metadata' => array_merge(
                    $reservation->metadata ?? [],
                    ['released_at' => now()->toIso8601String()]
                ),
            ])->save();

            WalletTransaction::query()->firstOrCreate(
                [
                    'wallet_id' => $wallet->id,
                    'source_type' => WalletTransaction::class,
                    'source_id' => $reservation->id,
                    'type' => 'refund',
                ],
                [
                    'reference' => $this->reference('REL'),
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'amount' => $amount,
                    'fee' => 0,
                    'net_amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => $description,
                    'metadata' => [
                        'reservation_reference' => $reservation->reference,
                    ],
                ]
            );

            return $reservation->refresh();
        }, 5);
    }

    public function debitWallet(
        int $merchantId,
        int $businessId,
        float|int|string $amount,
        string $sourceType,
        int|string|null $sourceId,
        string $description = '',
        array $metadata = []
    ): WalletTransaction {
        return $this->debit(
            $merchantId,
            $businessId,
            $amount,
            $sourceType,
            $sourceId,
            $description,
            $metadata
        );
    }

    public function getWalletBalance(int $merchantId, int $businessId): array
    {
        return $this->getBalance($merchantId, $businessId);
    }

    public function getBalance(int $merchantId, int $businessId): array
    {
        $wallet = Wallet::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('currency', 'NGN')
            ->first();

        return [
            'available_balance' => $wallet?->available_balance ?? '0.00',
            'ledger_balance' => $wallet?->ledger_balance ?? '0.00',
            'reserved_balance' => $wallet?->reserved_balance ?? '0.00',
            'currency' => $wallet?->currency ?? 'NGN',
            'status' => $wallet?->status ?? 'inactive',
        ];
    }

    public function getTransactions(int $merchantId, int $businessId, int $limit = 50): Collection
    {
        $wallet = Wallet::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('currency', 'NGN')
            ->first();

        if (! $wallet) {
            return collect();
        }

        return $wallet->transactions()
            ->latest('id')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    private function lockWallet(int $merchantId, int $businessId): Wallet
    {
        return Wallet::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('currency', 'NGN')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockReservation(
        Wallet $wallet,
        string $sourceType,
        int|string $sourceId
    ): WalletTransaction {
        return WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('type', 'debit')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function findSourceTransaction(
        Wallet $wallet,
        string $sourceType,
        int|string|null $sourceId,
        array $types,
        array $statuses
    ): ?WalletTransaction {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('type', $types)
            ->whereIn('status', $statuses)
            ->lockForUpdate()
            ->first();
    }

    private function ensureWalletCanTransact(Wallet $wallet): void
    {
        if ($wallet->status !== Wallet::STATUS_ACTIVE) {
            throw new RuntimeException(
                'Wallet transactions are not allowed while the wallet is ' . $wallet->status . '.'
            );
        }
    }

    private function ensureAvailableBalance(Wallet $wallet, string $amount): void
    {
        if ($this->compare($wallet->available_balance, $amount) < 0) {
            throw new InsufficientBalanceException(
                'The wallet does not have sufficient available balance.'
            );
        }
    }

    private function normaliseAmount(float|int|string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Wallet amount must be numeric.');
        }

        $normalised = $this->money($amount);

        if ($this->compare($normalised, '0.00') <= 0) {
            throw new InvalidArgumentException(
                'Wallet amount must be greater than zero.'
            );
        }

        return $normalised;
    }

    private function money(float|int|string|null $amount): string
    {
        return (string) BigDecimal::of((string) ($amount ?? '0'))->toScale(2, RoundingMode::HalfUp);
    }

    private function add(float|int|string $left, float|int|string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    private function subtract(float|int|string $left, float|int|string $right): string
    {
        return $this->fromCents($this->toCents($left) - $this->toCents($right));
    }

    private function compare(float|int|string $left, float|int|string $right): int
    {
        return $this->toCents($left) <=> $this->toCents($right);
    }

    private function toCents(float|int|string $amount): int
    {
        return BigDecimal::of($this->money($amount))->getUnscaledValue()->toInt();
    }

    private function fromCents(int $amount): string
    {
        $absolute = abs($amount);
        return ($amount < 0 ? '-' : '').intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function reference(string $prefix): string
    {
        return $prefix . '-' . Str::upper((string) Str::ulid());
    }
}
