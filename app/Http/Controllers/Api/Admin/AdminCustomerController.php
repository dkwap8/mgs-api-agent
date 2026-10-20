<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCustomerController extends Controller
{
    /**
     * List customers with search, filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'blocked',
                ]),
            ],

            'kyc_status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'verified',
                    'rejected',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $query = Customer::query()
            ->with([
                'merchant',
                'virtualAccounts',
            ])
            ->withCount('virtualAccounts')
            ->latest('id');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where(
                        'first_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'last_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'phone',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'merchant',
                        function ($merchantQuery) use ($search) {
                            $merchantQuery
                                ->where(
                                    'business_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'business_email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        if (! empty($validated['status'])) {
            $query->where(
                'status',
                $validated['status']
            );
        }

        if (! empty($validated['kyc_status'])) {
            $query->where(
                'kyc_status',
                $validated['kyc_status']
            );
        }

        $perPage = (int) ($validated['per_page'] ?? 12);

        return response()->json([
            'success' => true,
            'data' => $query
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    /**
     * Return customer summary statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => Customer::query()->count(),

                'active' => Customer::query()
                    ->where('status', 'active')
                    ->count(),

                'blocked' => Customer::query()
                    ->where('status', 'blocked')
                    ->count(),

                'verified' => Customer::query()
                    ->where('kyc_status', 'verified')
                    ->count(),

                'pending_kyc' => Customer::query()
                    ->where('kyc_status', 'pending')
                    ->count(),

                'rejected_kyc' => Customer::query()
                    ->where('kyc_status', 'rejected')
                    ->count(),
            ],
        ]);
    }

    /**
     * Display one customer.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::query()
            ->with([
                'merchant',
                'virtualAccounts',
            ])
            ->withCount('virtualAccounts')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * Block a customer.
     */
    public function block(int $id): JsonResponse
    {
        $customer = Customer::query()
            ->findOrFail($id);

        if ($customer->status === 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'This customer is already blocked.',
            ], 422);
        }

        $customer->update([
            'status' => 'blocked',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer blocked successfully.',
            'data' => $customer->fresh([
                'merchant',
                'virtualAccounts',
            ]),
        ]);
    }

    /**
     * Restore a blocked customer.
     */
    public function unblock(int $id): JsonResponse
    {
        $customer = Customer::query()
            ->findOrFail($id);

        if ($customer->status !== 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'This customer is not currently blocked.',
            ], 422);
        }

        $customer->update([
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer unblocked successfully.',
            'data' => $customer->fresh([
                'merchant',
                'virtualAccounts',
            ]),
        ]);
    }
}