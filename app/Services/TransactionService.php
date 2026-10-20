<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionService
{
    /**
     * Return transactions that belong to the active merchant and business.
     *
     * @param array<string, mixed> $filters
     */
    public function paginateForBusiness(
        Merchant $merchant,
        Business $business,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Transaction::query()
            ->with(['customer', 'virtualAccount', 'provider'])
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->latest('id');

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) ($filters['per_page'] ?? 20), 5), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForBusiness(
        Merchant $merchant,
        Business $business,
        string $reference
    ): Transaction {
        return Transaction::query()
            ->with([
                'customer',
                'virtualAccount.provider',
                'provider',
                'walletTransaction',
            ])
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->where('reference', $reference)
            ->firstOrFail();
    }

    /**
     * @return array<string, int|float>
     */
    public function summaryForBusiness(Merchant $merchant, Business $business): array
    {
        $query = Transaction::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id);

        return [
            'total' => (clone $query)->count(),
            'successful' => (clone $query)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->count(),
            'pending' => (clone $query)
                ->whereIn('status', [
                    Transaction::STATUS_PENDING,
                    Transaction::STATUS_PROCESSING,
                ])
                ->count(),
            'failed' => (clone $query)
                ->whereIn('status', [
                    Transaction::STATUS_FAILED,
                    Transaction::STATUS_REVERSED,
                ])
                ->count(),
            'successful_volume' => (float) (clone $query)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->sum('amount'),
            'net_settlement' => (float) (clone $query)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->sum('merchant_amount'),
            'fees' => (float) (clone $query)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->sum('fee'),
            'today_volume' => (float) (clone $query)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->whereDate('confirmed_at', today())
                ->sum('amount'),
        ];
    }

    /**
     * @param Builder<Transaction> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customer) use ($search): void {
                        $customer
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['provider_id'])) {
            $query->where('provider_id', (int) $filters['provider_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }
    }
}
