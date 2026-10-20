<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Merchant;
use App\Models\Payout;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Support\Money;
use Brick\Math\RoundingMode;

class PlatformReportService
{
    public function build(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        [$startDate, $endDate] = $this->resolvePeriod($period, $customStart, $customEnd);

        $transactions = $this->dateRange(Transaction::query(), $startDate, $endDate);
        $payouts = $this->dateRange(Payout::query(), $startDate, $endDate);
        $merchants = $this->dateRange(Merchant::query(), $startDate, $endDate);
        $businesses = $this->dateRange(Business::query(), $startDate, $endDate);

        $totalTransactions = (clone $transactions)->count();
        $successfulTransactions = (clone $transactions)->successful()->count();
        $pendingTransactions = (clone $transactions)->pending()->count();
        $failedTransactions = (clone $transactions)->failed()->count();
        $totalVolume = Money::format((string) (clone $transactions)->successful()->sum('amount'));
        $totalFees = Money::format((string) (clone $transactions)->successful()->sum('fee'));
        $totalMerchantAmount = Money::format((string) (clone $transactions)->successful()->sum('merchant_amount'));

        $revenue = \App\Models\Revenue::query()->whereHas('transaction', function ($q) use ($startDate,$endDate) { $q->successful(); $this->dateRange($q,$startDate,$endDate); });
        $recordedRevenue = (clone $revenue)->count();
        $costedRevenue = (clone $revenue)->whereNotNull('provider_cost')->count();
        $knownCosts = Money::format((string)(clone $revenue)->whereNotNull('provider_cost')->sum('provider_cost'));

        return [
            'period' => [
                'key' => $period,
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
            ],
            'overview' => [
                'total_merchants' => (clone $merchants)->count(),
                'total_businesses' => (clone $businesses)->count(),
                'approved_businesses' => (clone $businesses)->where('verification_status', Business::STATUS_APPROVED)->count(),
                'pending_businesses' => (clone $businesses)->whereIn('verification_status', [Business::STATUS_PENDING, Business::STATUS_UNDER_REVIEW])->count(),
                'suspended_businesses' => (clone $businesses)->where('verification_status', Business::STATUS_SUSPENDED)->count(),
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'pending_transactions' => $pendingTransactions,
                'failed_transactions' => $failedTransactions,
                'success_rate' => $totalTransactions > 0 ? round(($successfulTransactions / $totalTransactions) * 100, 2) : 0.0,
                'average_transaction' => $successfulTransactions > 0 ? (string) Money::decimal($totalVolume)->dividedBy($successfulTransactions, 2, RoundingMode::HalfUp) : '0.00',
                'total_volume' => $totalVolume,
                'total_fees' => $totalFees,
                'merchant_settlement_value' => $totalMerchantAmount,
                'total_profit' => $successfulTransactions > 0 && $recordedRevenue === $successfulTransactions && $costedRevenue === $successfulTransactions ? (string)Money::decimal($totalFees)->minus($knownCosts) : null,
                'profit_status' => $successfulTransactions > 0 && $costedRevenue === $successfulTransactions ? 'collection_costs_accounted' : 'provider_costs_not_accounted',
                'known_provider_cost' => $knownCosts, 'revenue_records' => $recordedRevenue, 'costed_revenue_records' => $costedRevenue,
                'total_payout_value' => Money::format((string) (clone $payouts)->sum('amount')),
                'successful_payout_value' => Money::format((string) (clone $payouts)->whereIn('status', Payout::SUCCESSFUL_STATUSES)->sum('amount')),
                'pending_payout_value' => Money::format((string) (clone $payouts)->whereIn('status', Payout::PENDING_STATUSES)->sum('amount')),
            ],
            'transaction_types' => $this->transactionBreakdown('type', $startDate, $endDate),
            'transaction_statuses' => $this->transactionBreakdown('status', $startDate, $endDate),
            'daily_trend' => $this->dailyTrend($startDate, $endDate, $period),
            'top_businesses' => $this->topBusinesses($startDate, $endDate),
            'recent_activity' => $this->recentActivity($startDate, $endDate),
        ];
    }

    public function resolvePeriod(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [Carbon::parse($customStart)->startOfDay(), Carbon::parse($customEnd)->endOfDay()],
            'all' => [null, null],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    private function dateRange(Builder $query, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        return $query
            ->when($startDate, fn (Builder $builder) => $builder->where('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $builder) => $builder->where('created_at', '<=', $endDate));
    }

    private function transactionBreakdown(string $column, ?Carbon $startDate, ?Carbon $endDate): array
    {
        return $this->dateRange(
            Transaction::query()
                ->select([$column, DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(amount), 0) as amount')])
                ->groupBy($column)
                ->orderByDesc('amount'),
            $startDate,
            $endDate
        )->get()->map(fn (Transaction $item) => [
            $column => $item->{$column} ?: 'unknown',
            'total' => (int) $item->total,
            'amount' => Money::format((string) $item->amount),
        ])->values()->all();
    }

    private function dailyTrend(?Carbon $startDate, ?Carbon $endDate, string $period): array
    {
        $query = $this->dateRange(
            Transaction::query()
                ->select([
                    DB::raw('DATE(created_at) as report_date'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COALESCE(SUM(amount), 0) as amount'),
                    DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful"),
                ])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('report_date'),
            $startDate,
            $endDate
        );

        if ($period === 'all') {
            $query->limit(365);
        }

        return $query->get()->map(fn (Transaction $item) => [
            'date' => $item->report_date,
            'total' => (int) $item->total,
            'successful' => (int) $item->successful,
            'amount' => Money::format((string) $item->amount),
        ])->values()->all();
    }

    private function topBusinesses(?Carbon $startDate, ?Carbon $endDate): array
    {
        return $this->dateRange(
            Transaction::query()
                ->select([
                    'business_id',
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('COALESCE(SUM(amount), 0) as total_volume'),
                    DB::raw('COALESCE(SUM(fee), 0) as total_fees'),
                ])
                ->whereNotNull('business_id')
                ->successful()
                ->with('business.merchant.user')
                ->groupBy('business_id')
                ->orderByDesc('total_volume')
                ->limit(10),
            $startDate,
            $endDate
        )->get()->map(fn (Transaction $item) => [
            'business_id' => $item->business_id,
            'business_name' => $item->business?->name ?: 'Unnamed Business',
            'merchant_id' => $item->business?->merchant_id,
            'owner_name' => $item->business?->merchant?->user?->name,
            'total_transactions' => (int) $item->total_transactions,
            'total_volume' => Money::format((string) $item->total_volume),
            'total_fees' => Money::format((string) $item->total_fees),
        ])->values()->all();
    }

    private function recentActivity(?Carbon $startDate, ?Carbon $endDate): array
    {
        return $this->dateRange(
            Transaction::query()->with(['business:id,name,merchant_id', 'merchant.user:id,name,email', 'customer:id,first_name,last_name,email']),
            $startDate,
            $endDate
        )->latest('id')->limit(10)->get()->toArray();
    }
}
