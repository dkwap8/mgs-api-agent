<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->status !== 'active') {
            return $this->deny('Your account is not active.', Response::HTTP_FORBIDDEN);
        }

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->deny('Please select an active business.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $merchant->isApproved() || $merchant->isSuspended()) {
            return $this->deny('Merchant account is not active.', Response::HTTP_FORBIDDEN);
        }

        if (! $business->isApproved() || $business->isSuspended()) {
            return $this->deny('Selected business is not active.', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function deny(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
