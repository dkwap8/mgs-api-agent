<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessActivation;
use App\Services\BusinessLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBusinessActivationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'under_review', 'approved', 'rejected'])],
            'business_type' => ['nullable', Rule::in(['starter', 'registered'])],
            'period' => ['nullable', Rule::in(['today', 'week', 'month'])],
            'per_page' => ['nullable', 'integer', 'min:6', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = BusinessActivation::query()
            ->with(['merchant.user', 'business', 'user'])
            ->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['business_type'])) {
            $query->where('business_type', $validated['business_type']);
        }
        if (! empty($validated['period'])) {
            match ($validated['period']) {
                'today' => $query->whereDate('created_at', today()),
                'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month),
                default => null,
            };
        }
        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('business_name', 'like', "%{$search}%")
                    ->orWhere('business_email', 'like', "%{$search}%")
                    ->orWhere('business_phone', 'like', "%{$search}%")
                    ->orWhere('website_url', 'like', "%{$search}%")
                    ->orWhere('cac_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) ($validated['per_page'] ?? 12))->withQueryString(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
    $activation = BusinessActivation::query()
        ->with([
            'merchant.user',
            'business',
            'user',
        ])
        ->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => array_merge(
            $activation->toArray(),
            [
                'nin' => $activation->nin,
                'bvn' => $activation->bvn,
            ]
        ),
    ]);
    }

    public function approve(Request $request, int $id, BusinessLifecycleService $service): JsonResponse
    {
        $activation = $service->approve(
            BusinessActivation::query()->findOrFail($id),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business approved successfully.',
            'data' => $activation,
        ]);
    }

    public function reject(Request $request, int $id, BusinessLifecycleService $service): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $activation = $service->reject(
            BusinessActivation::query()->findOrFail($id),
            $request->user(),
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Business activation rejected successfully.',
            'data' => $activation,
        ]);
    }
}
