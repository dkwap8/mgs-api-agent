<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (! $user) {
            return $this->deny('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if ($permissions === ['auto'] || $permissions === []) {
            $permissions = [$this->permissionFor($request)];
        }

        foreach (array_filter($permissions) as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        return $this->deny('You do not have permission to perform this action.', Response::HTTP_FORBIDDEN);
    }

    private function permissionFor(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');
        $read = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        return match (true) {
            str_contains($path, '/admin/kyc-requests') => $read ? 'businesses.view' : 'businesses.manage',
            str_contains($path, '/admin/audit-logs') => 'audit.view',
            str_contains($path, '/admin/webhook-logs') => 'providers.view',
            str_contains($path, '/admin/pricing') => $read ? 'pricing.view' : 'pricing.manage',
            str_contains($path, '/admin/reports') => 'reports.view',
            str_contains($path, '/admin/providers') => $read ? 'providers.view' : 'providers.manage',
            str_contains($path, '/admin/payouts') => $read ? 'payouts.view' : 'payouts.review',
            str_contains($path, '/admin/provider-transactions') => $read ? 'transactions.view' : 'providers.manage',
            str_contains($path, '/admin/transactions') => 'transactions.view',
            str_contains($path, '/admin/support') => $read ? 'support.view' : 'support.manage',
            str_contains($path, '/admin/settings'), str_contains($path, '/admin/withdrawal-settings') => $read ? 'settings.view' : 'settings.manage',
            str_contains($path, '/admin/activity-logs') => 'audit.view',
            str_contains($path, '/admin/business') => $read ? 'businesses.view' : 'businesses.manage',
            str_contains($path, '/admin/merchants'), str_contains($path, '/admin/customers') => $read ? 'merchants.view' : 'merchants.manage',
            str_contains($path, '/admin/virtual-accounts') => $read ? 'businesses.view' : 'businesses.manage',
            str_contains($path, '/admin/queue') => 'users.manage',
            default => 'dashboard.view',
        };
    }

    private function deny(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
