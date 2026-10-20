<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $admin = $request->user();

        if (! $admin) {
            return $this->deny(
                'Administrator authentication is required.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (! $admin instanceof Admin) {
            return $this->deny(
                'This login session is not an administrator session.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $admin->isActive()) {
            return $this->deny(
                'Your administrator account is not active.',
                Response::HTTP_FORBIDDEN
            );
        }

        $securityRoute = $request->is('api/v1/admin/auth/mfa/*', 'api/v1/admin/auth/me', 'api/v1/admin/auth/logout', 'api/v1/admin/auth/logout-all');
        $token = $admin->currentAccessToken();
        if (! $securityRoute && ((config('admin_security.require_mfa') && ! $admin->two_factor_confirmed_at)
            || ($token instanceof \Laravel\Sanctum\PersonalAccessToken && ! $token->can('admin:access')))) {
            return $this->deny('Complete administrator MFA setup before accessing the platform.', 403);
        }

        $allowedRoles = $roles !== []
            ? $roles
            : [
                'admin',
                'super_admin',
                'administrator',
                'support_admin',
                'finance_admin',
                'compliance_admin',
            ];

        if (! $admin->hasAnyRole($allowedRoles)) {
            return $this->deny(
                'Administrator access is required.',
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }

    private function deny(
        string $message,
        int $status
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
