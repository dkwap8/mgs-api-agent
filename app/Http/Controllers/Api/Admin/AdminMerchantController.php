<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\Merchant\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMerchantController extends Controller
{
    /**
     * Display a searchable and filterable list of merchants.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'submitted',
                    'under_review',
                    'approved',
                    'verified',
                    'active',
                    'rejected',
                    'suspended',
                ]),
            ],
            'live_access' => ['nullable', Rule::in(['yes', 'no'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Merchant::query()
            ->with([
                'user',
                'wallets',
                'businesses' => function ($businessQuery) {
                    $businessQuery
                        ->latest('id')
                        ->select([
                            'id',
                            'merchant_id',
                            'name',
                            'business_code',
                            'verification_status',
                            'is_verified',
                            'created_at',
                        ]);
                },
            ])
            ->withCount('businesses')
            ->latest('id');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('business_name', 'like', "%{$search}%")
                    ->orWhere('business_email', 'like', "%{$search}%")
                    ->orWhere('business_phone', 'like', "%{$search}%")
                    ->orWhere('cac_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('businesses', function ($businessQuery) use ($search) {
                        $businessQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('business_code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($validated['status'])) {
            $query->where('verification_status', $validated['status']);
        }

        if (! empty($validated['live_access'])) {
            $query->where('live_access', $validated['live_access'] === 'yes');
        }

        $perPage = (int) ($validated['per_page'] ?? 12);

        return response()->json([
            'status' => true,
            'data' => $query->paginate($perPage)->withQueryString(),
        ]);
    }

    /**
     * Display one merchant with its related information.
     */
    public function show(Merchant $merchant): JsonResponse
    {
        $merchant->load([
            'user',
            'wallets',
            'businesses' => function ($businessQuery) {
                $businessQuery
                    ->with('activation')
                    ->latest('id');
            },
        ]);

        return response()->json([
            'status' => true,
            'data' => $merchant,
        ]);
    }

    /**
     * Approve a merchant account.
     */
    public function approve(
        Merchant $merchant,
        MerchantService $merchantService
    ): JsonResponse {
        $merchantService->approve($merchant);

        return response()->json([
            'status' => true,
            'message' => 'Merchant approved successfully.',
            'data' => $merchant->fresh([
                'user',
                'wallets',
                'businesses',
            ]),
        ]);
    }

    /**
     * Reject a merchant account.
     */
    public function reject(
        Merchant $merchant,
        MerchantService $merchantService
    ): JsonResponse {
        $merchantService->reject($merchant);

        return response()->json([
            'status' => true,
            'message' => 'Merchant rejected successfully.',
            'data' => $merchant->fresh([
                'user',
                'wallets',
                'businesses',
            ]),
        ]);
    }

    /**
     * Suspend merchant live access.
     */
    public function suspend(Merchant $merchant): JsonResponse
    {
        $merchant->update([
            'verification_status' => 'suspended',
            'live_access' => false,
            'is_verified' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Merchant suspended successfully.',
            'data' => $merchant->fresh([
                'user',
                'wallets',
                'businesses',
            ]),
        ]);
    }
}