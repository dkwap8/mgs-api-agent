<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $currentBusiness = $request->activeBusiness();

        if (! $merchant) {
            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found.',
            ], 404);
        }

        $wallet = $currentBusiness
            ? $currentBusiness->wallet()->firstOrCreate(
                ['currency' => 'NGN'],
                [
                    'merchant_id' => $merchant->id,
                    'available_balance' => 0,
                    'ledger_balance' => 0,
                    'reserved_balance' => 0,
                    'status' => 'active',
                ]
            )
            : null;

        /*
         * Load the full activation relationship.
         *
         * Business::activation() uses latestOfMany(), so selecting only an
         * unqualified "id" can create an ambiguous-column SQL query.
         */
        $businesses = $merchant->businesses()
            ->with('activation')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'current_merchant' => $merchant,
                'current_business' => $currentBusiness,
                'businesses' => $businesses,
                'wallet' => $wallet,
                'badge' => $currentBusiness?->is_verified
                    ? 'verified_business'
                    : 'unverified_business',
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant) {
            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found.',
            ], 404);
        }

        if (! $business) {
            return response()->json([
                'status' => false,
                'message' => 'Please create or select a business first.',
            ], 404);
        }

        $data = $request->validate([
            'business_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'business_email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],
            'business_phone' => [
                'sometimes',
                'nullable',
                'regex:/^\d{11}$/',
            ],
            'business_address' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'cac_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
        ], [
            'business_phone.regex' => 'Your phone number must contain exactly 11 digits.',
        ]);

        if ($business->isApproved() && (
            (isset($data['business_name']) && trim($data['business_name']) !== $business->name)
            || array_key_exists('cac_number', $data)
        )) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'business_name' => ['Contact support to change verified business identity details.'],
            ]);
        }

        $contactData = collect($data)->except('business_name')->all();
        $updates = ['metadata' => array_merge($business->metadata ?? [], $contactData)];
        if (array_key_exists('business_name', $data)) {
            $updates['name'] = trim($data['business_name']);
        }
        $business->update($updates);

        return response()->json([
            'status' => true,
            'message' => 'Business profile updated successfully.',
            'data' => [
                'merchant' => $merchant->fresh(),
                'wallet' => $business->wallet()->first(),
                'business' => $business->fresh('activation'),
            ],
        ]);
    }
}