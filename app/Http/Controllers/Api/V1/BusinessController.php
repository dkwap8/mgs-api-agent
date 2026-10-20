<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $merchant = $user->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Merchant account not found.',
            ], 404);
        }

        /*
         * Do not restrict the activation columns here.
         *
         * The Business::activation() relationship uses latestOfMany().
         * Selecting an unqualified "id" while Laravel joins the latest-of-many
         * subquery can produce an ambiguous-column SQL error.
         */
        $businesses = $merchant->businesses()
            ->with('activation')
            ->orderByDesc('id')
            ->get();

        $currentBusiness = $user->resolveActiveBusiness();

        return response()->json([
            'status' => 'success',
            'data' => $businesses,
            'current_business_id' => $currentBusiness?->id,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $merchant = $user->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Merchant account not found.',
            ], 404);
        }

        $name = trim($validated['name']);

        $alreadyExists = $merchant->businesses()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'A business with this name already exists under your merchant account.',
                'errors' => [
                    'name' => [
                        'A business with this name already exists under your merchant account.',
                    ],
                ],
            ], 422);
        }

        $business = DB::transaction(function () use ($merchant, $user, $name) {
            do {
                $businessCode = 'MGS'.str_pad(
                    (string) random_int(1, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            } while (
                Business::query()
                    ->where('business_code', $businessCode)
                    ->exists()
            );

            $business = $merchant->businesses()->create([
                'name' => $name,
                'business_code' => $businessCode,
                'verification_status' => Business::STATUS_PENDING,
                'is_verified' => false,
                'verified_at' => null,
                'activation_id' => null,
            ]);

            $user->forceFill([
                'current_business_id' => $business->id,
            ])->save();

            return $business;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Business created. Complete the activation form and wait for administrator approval.',
            'data' => $business->fresh(),
        ], 201);
    }

    public function switch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'min:1'],
        ]);

        $business = $request->user()->switchBusiness(
            (int) $validated['business_id']
        );

        if (! $business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Business not found or does not belong to your merchant account.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Switched to business: '.$business->name,
            'data' => $business->load('activation'),
        ]);
    }
}