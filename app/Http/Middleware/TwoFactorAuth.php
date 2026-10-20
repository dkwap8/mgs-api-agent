<?php

namespace App\Http\Middleware;

use App\Services\TotpService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuth
{
    public function __construct(private readonly TotpService $totp) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (! $user->two_factor_secret || ! $user->two_factor_confirmed_at) {
            return new JsonResponse(['success' => false, 'message' => 'Two-factor authentication is not enabled.'], 403);
        }

        $code = trim((string) $request->header('X-MGS-2FA-Code'));
        if (! $this->totp->verify((string) $user->two_factor_secret, $code)) {
            return new JsonResponse(['success' => false, 'message' => 'A valid two-factor authentication code is required.'], 403);
        }

        return $next($request);
    }
}
