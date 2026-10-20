<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => [
                'nullable',
                Rule::in([
                    'today',
                    'yesterday',
                    '7days',
                    '30days',
                    'month',
                    'custom',
                ]),
            ],
            'from' => [
                'nullable',
                'required_if:period,custom',
                'date',
            ],
            'to' => [
                'nullable',
                'required_if:period,custom',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Please create or select an active business.',
            ], 422);
        }

        [$from, $to, $previousFrom, $previousTo] =
            $this->resolvePeriod(
                $validated['period'] ?? '30days',
                $validated['from'] ?? null,
                $validated['to'] ?? null
            );

        $wallet = Wallet::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->first();

        $isApproved =
            (bool) $business->is_verified ||
            $business->verification_status === 'approved';

        $collectionsQuery = $this->collectionQuery(
            $merchant->id,
            $business->id
        );

        $periodCollectionsQuery = (clone $collectionsQuery)
            ->whereBetween('created_at', [$from, $to]);

        $previousCollectionsQuery = (clone $collectionsQuery)
            ->whereBetween(
                'created_at',
                [$previousFrom, $previousTo]
            );

        $payoutsQuery = Payout::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id);

        $successfulPayoutStatuses = [
            'success',
            'successful',
            'completed',
        ];

        $periodPayoutsQuery = (clone $payoutsQuery)
            ->whereBetween('created_at', [$from, $to]);

        $previousPayoutsQuery = (clone $payoutsQuery)
            ->whereBetween(
                'created_at',
                [$previousFrom, $previousTo]
            );

        $totalCollections = $this->sumAmount(
            $periodCollectionsQuery
        );

        $previousCollections = $this->sumAmount(
            $previousCollectionsQuery
        );

        $totalPayouts = (float) (clone $periodPayoutsQuery)
            ->whereIn('status', $successfulPayoutStatuses)
            ->sum('amount');

        $previousPayouts = (float) (clone $previousPayoutsQuery)
            ->whereIn('status', $successfulPayoutStatuses)
            ->sum('amount');

        $totalFees = (float) (clone $periodPayoutsQuery)
            ->sum('fee');

        $successfulTransactions = (clone $periodCollectionsQuery)
            ->count();

        $newVirtualAccounts = VirtualAccount::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                Schema::hasColumn('virtual_accounts', 'business_id'),
                fn (Builder $query) =>
                    $query->where('business_id', $business->id)
            )
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $previousVirtualAccounts = VirtualAccount::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                Schema::hasColumn('virtual_accounts', 'business_id'),
                fn (Builder $query) =>
                    $query->where('business_id', $business->id)
            )
            ->whereBetween(
                'created_at',
                [$previousFrom, $previousTo]
            )
            ->count();

        $newCustomers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                Schema::hasColumn('customers', 'business_id'),
                fn (Builder $query) =>
                    $query->where('business_id', $business->id)
            )
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $previousCustomers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                Schema::hasColumn('customers', 'business_id'),
                fn (Builder $query) =>
                    $query->where('business_id', $business->id)
            )
            ->whereBetween(
                'created_at',
                [$previousFrom, $previousTo]
            )
            ->count();

        $allTimeCollections = $this->sumAmount(
            clone $collectionsQuery
        );

        $allTimePayouts = (float) (clone $payoutsQuery)
            ->whereIn('status', $successfulPayoutStatuses)
            ->sum('amount');

        $allTimePurchases = $this->purchaseTotal(
            $merchant->id,
            $business->id
        );

        $totalCustomers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->when(
                Schema::hasColumn('customers', 'business_id'),
                fn (Builder $query) =>
                    $query->where('business_id', $business->id)
            )
            ->count();

        $chart = $this->buildDailyChart(
            clone $collectionsQuery,
            clone $payoutsQuery,
            $from,
            $to,
            $successfulPayoutStatuses
        );

        return response()->json([
            'success' => true,

            'data' => [
                'period' => [
                    'key' => $validated['period'] ?? '30days',
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],

                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'business_code' => $business->business_code,
                    'verification_status' =>
                        $business->verification_status,
                    'is_verified' => (bool) $business->is_verified,
                    'is_approved' => $isApproved,
                ],

                'balance' => (float) (
                    $wallet?->available_balance ?? 0
                ),

                'reserved_balance' => (float) (
                    $wallet?->reserved_balance ?? 0
                ),

                'ledger_balance' => (float) (
                    $wallet?->ledger_balance ?? 0
                ),

                'collections_total' => $allTimeCollections,
                'payouts_total' => $allTimePayouts,
                'purchases_total' => $allTimePurchases,
                'customers_total' => $totalCustomers,

                'total_collections' => $totalCollections,
                'total_payouts' => $totalPayouts,
                'total_fees' => $totalFees,

                'successful_transactions' =>
                    $successfulTransactions,

                'new_virtual_accounts' => $newVirtualAccounts,
                'new_customers' => $newCustomers,

                'growth' => [
                    'virtual_accounts_percent' =>
                        $this->percentageChange(
                            $newVirtualAccounts,
                            $previousVirtualAccounts
                        ),

                    'customers_percent' =>
                        $this->percentageChange(
                            $newCustomers,
                            $previousCustomers
                        ),

                    'volume_percent' =>
                        $this->percentageChange(
                            $totalCollections,
                            $previousCollections
                        ),

                    'payouts_percent' =>
                        $this->percentageChange(
                            $totalPayouts,
                            $previousPayouts
                        ),

                    'revenue_growth_percent' =>
                        $this->percentageChange(
                            $totalCollections - $totalPayouts,
                            $previousCollections - $previousPayouts
                        ),
                ],

                'attention' => [
                    'failed_workflows' => (clone $periodPayoutsQuery)
                        ->whereIn('status', [
                            'failed',
                            'rejected',
                            'cancelled',
                        ])
                        ->count(),

                    'awaiting_settlement' => (clone $periodPayoutsQuery)
                        ->whereIn('status', [
                            'pending',
                            'processing',
                            'approved',
                        ])
                        ->count(),

                    'pending_refunds' => (clone $periodPayoutsQuery)
                        ->whereIn('status', [
                            'failed',
                            'rejected',
                            'cancelled',
                        ])
                        ->whereNull('refunded_at')
                        ->count(),

                    'open_disputes' => 0,
                ],

                'chart_labels' => $chart['labels'],
                'chart_collections' => $chart['collections'],
                'chart_payouts' => $chart['payouts'],
            ],
        ]);
    }

    private function collectionQuery(
        int $merchantId,
        int $businessId
    ): Builder {
        if (Schema::hasTable('transactions')) {
            $query = Transaction::query()
                ->where('merchant_id', $merchantId);

            if (
                Schema::hasColumn(
                    'transactions',
                    'business_id'
                )
            ) {
                $query->where('business_id', $businessId);
            }

            if (Schema::hasColumn('transactions', 'status')) {
                $query->whereIn('status', [
                    'success',
                    'successful',
                    'completed',
                    'paid',
                ]);
            }

            return $query;
        }

        $wallet = Wallet::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('currency', 'NGN')
            ->first();

        return WalletTransaction::query()
            ->where('wallet_id', $wallet?->id ?? 0)
            ->where('type', 'credit')
            ->where('status', 'completed');
    }

    private function sumAmount(Builder $query): float
    {
        if (
            Schema::hasColumn(
                $query->getModel()->getTable(),
                'amount'
            )
        ) {
            return (float) $query->sum('amount');
        }

        return 0;
    }

    private function purchaseTotal(
        int $merchantId,
        int $businessId
    ): float {
        if (! Schema::hasTable('transactions')) {
            return 0;
        }

        $query = Transaction::query()
            ->where('merchant_id', $merchantId);

        if (
            Schema::hasColumn(
                'transactions',
                'business_id'
            )
        ) {
            $query->where('business_id', $businessId);
        }

        if (Schema::hasColumn('transactions', 'type')) {
            $query->whereIn('type', [
                'purchase',
                'debit',
            ]);
        } else {
            return 0;
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('status', [
                'success',
                'successful',
                'completed',
                'paid',
            ]);
        }

        return (float) $query->sum('amount');
    }

    private function resolvePeriod(
        string $period,
        ?string $customFrom,
        ?string $customTo
    ): array {
        $now = now();

        [$from, $to] = match ($period) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],

            '7days' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfDay(),
            ],

            'custom' => [
                Carbon::parse($customFrom)->startOfDay(),
                Carbon::parse($customTo)->endOfDay(),
            ],

            default => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };

        $durationSeconds = max(
            $from->diffInSeconds($to),
            1
        );

        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo
            ->copy()
            ->subSeconds($durationSeconds);

        return [
            $from,
            $to,
            $previousFrom,
            $previousTo,
        ];
    }

    private function buildDailyChart(
        Builder $collectionsQuery,
        Builder $payoutsQuery,
        Carbon $from,
        Carbon $to,
        array $successfulPayoutStatuses
    ): array {
        $labels = [];
        $collections = [];
        $payouts = [];

        $cursor = $from->copy()->startOfDay();
        $lastDay = $to->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();

            $labels[] = $cursor->format('d M');

            $collections[] = $this->sumAmount(
                (clone $collectionsQuery)
                    ->whereBetween(
                        'created_at',
                        [$dayStart, $dayEnd]
                    )
            );

            $payouts[] = (float) (clone $payoutsQuery)
                ->whereIn('status', $successfulPayoutStatuses)
                ->whereBetween(
                    'created_at',
                    [$dayStart, $dayEnd]
                )
                ->sum('amount');

            $cursor->addDay();
        }

        return compact(
            'labels',
            'collections',
            'payouts'
        );
    }

    private function percentageChange(
        float|int $current,
        float|int $previous
    ): float {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(
            (($current - $previous) / abs($previous)) * 100,
            2
        );
    }
}