<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPayoutSecurity
{
    /**
     * Protect PIN-authenticated payout submission.
     *
     * OTP submission is handled separately by WithdrawalOtpController and is
     * deliberately not passed through this middleware.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->status !== 'active') {
            return $this->deny(
                'Your account is not permitted to submit withdrawals.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $user->withdrawal_pin) {
            return $this->deny(
                'Set a withdrawal PIN before submitting a withdrawal.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $merchant = $request->activeMerchant();

        if (! $merchant) {
            return $this->deny(
                'Merchant account not found.',
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $merchant->isApproved()) {
            return $this->deny(
                $merchant->isSuspended()
                    ? 'Merchant account is suspended.'
                    : 'Merchant account is not approved for withdrawals.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $request->activeBusiness()) {
            return $this->deny(
                'Please select an active business.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $next($request);
    }

    private function deny(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
