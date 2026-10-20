<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMerchant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401, [], 'UNAUTHENTICATED');
        }

        $merchant = $user->resolveActiveMerchant();

        if (! $merchant) {
            return ApiResponse::error(
                'Merchant account not found.',
                404,
                [],
                'MERCHANT_NOT_FOUND'
            );
        }

        $requestedBusinessId = $request->header('X-Business-ID');
        $business = null;

        if ($requestedBusinessId !== null && $requestedBusinessId !== '') {
            if (! ctype_digit((string) $requestedBusinessId)) {
                return ApiResponse::error(
                    'The selected business is invalid.',
                    422,
                    ['business_id' => ['Select a valid business account.']],
                    'INVALID_BUSINESS_CONTEXT'
                );
            }

            $business = $merchant->businesses()
                ->whereKey((int) $requestedBusinessId)
                ->first();

            if (! $business) {
                return ApiResponse::error(
                    'You do not have access to the selected business.',
                    403,
                    [],
                    'BUSINESS_ACCESS_DENIED'
                );
            }

            if ((int) $user->current_business_id !== (int) $business->id) {
                $user->forceFill(['current_business_id' => $business->id])->save();
            }
        } else {
            $business = $user->resolveActiveBusiness();
        }

        if (! $business) {
            return ApiResponse::error(
                'Please create or select a business account.',
                422,
                [],
                'BUSINESS_CONTEXT_REQUIRED'
            );
        }

        // Every business owns exactly one wallet per currency. Creating the
        // zero-balance wallet here prevents later code from falling back to a
        // merchant-level wallet when a newly-created business is selected.
        $business->wallet()->firstOrCreate(
            ['currency' => 'NGN'],
            [
                'merchant_id' => $merchant->id,
                'available_balance' => 0,
                'ledger_balance' => 0,
                'reserved_balance' => 0,
                'status' => 'active',
            ]
        );

        $request->attributes->set('merchant', $merchant);
        $request->attributes->set('business', $business);

        return $next($request);
    }
}
