<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $transactions = $this->filteredQuery($validated)
            ->with([
                'business:id,name,merchant_id,verification_status',
                'merchant.user:id,name,email',
                'customer:id,first_name,last_name,email,phone',
                'provider:id,name,slug',
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'links' => [
                'first' => $transactions->url(1),
                'last' => $transactions->url($transactions->lastPage()),
                'previous' => $transactions->previousPageUrl(),
                'next' => $transactions->nextPageUrl(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request, pagination: false);
        $base = $this->filteredQuery($validated, applyStatus: false);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (clone $base)->count(),
                'successful' => (clone $base)->successful()->count(),
                'pending' => (clone $base)->pending()->count(),
                'failed' => (clone $base)->failed()->count(),
                'total_volume' => round((float) (clone $base)->successful()->sum('amount'), 2),
                'total_fees' => round((float) (clone $base)->successful()->sum('fee'), 2),
                'merchant_settlement_value' => round((float) (clone $base)->successful()->sum('merchant_amount'), 2),
                'credit_volume' => round((float) (clone $base)->successful()->whereIn('type', ['credit', 'collection', 'deposit'])->sum('amount'), 2),
                'debit_volume' => round((float) (clone $base)->successful()->whereIn('type', ['debit', 'payout', 'withdrawal'])->sum('amount'), 2),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::query()
            ->with([
                'business.merchant.user:id,name,email',
                'customer',
                'virtualAccount',
                'provider:id,name,slug,status',
                'walletTransaction',
            ])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    private function validateFilters(Request $request, bool $pagination = true): array
    {
        $rules = [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_merge(Transaction::SUCCESSFUL_STATUSES, Transaction::PENDING_STATUSES, Transaction::FAILED_STATUSES))],
            'type' => ['nullable', 'string', 'max:50'],
            'business_id' => ['nullable', 'integer', 'exists:businesses,id'],
            'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];

        if ($pagination) {
            $rules['per_page'] = ['nullable', 'integer', 'min:5', 'max:100'];
            $rules['page'] = ['nullable', 'integer', 'min:1'];
        }

        return $request->validate($rules);
    }

    private function filteredQuery(array $filters, bool $applyStatus = true): Builder
    {
        return Transaction::query()
            ->when($applyStatus && ! empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['business_id']), fn (Builder $query) => $query->where('business_id', $filters['business_id']))
            ->when(! empty($filters['merchant_id']), fn (Builder $query) => $query->where('merchant_id', $filters['merchant_id']))
            ->when(! empty($filters['provider_id']), fn (Builder $query) => $query->where('provider_id', $filters['provider_id']))
            ->when(! empty($filters['customer_id']), fn (Builder $query) => $query->where('customer_id', $filters['customer_id']))
            ->when(! empty($filters['start_date']), fn (Builder $query) => $query->where('created_at', '>=', now()->parse($filters['start_date'])->startOfDay()))
            ->when(! empty($filters['end_date']), fn (Builder $query) => $query->where('created_at', '<=', now()->parse($filters['end_date'])->endOfDay()))
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('reference', 'like', "%{$search}%")
                        ->orWhere('provider_reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('business', fn (Builder $business) => $business->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('merchant.user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            });
    }
}
