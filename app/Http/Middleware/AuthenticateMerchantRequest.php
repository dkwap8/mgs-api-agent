<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMerchantRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-MGS-Public-Key') && ! str_starts_with((string) $request->bearerToken(), 'MGS_SK_')) {
            return app(Pipeline::class)->send($request)
                ->through([\Illuminate\Auth\Middleware\Authenticate::class.':sanctum', EnsureUser::class, EnsureActiveMerchant::class])
                ->then($next);
        }

        // A merchant API key is not a dashboard session and cannot manage keys or withdraw funds.
        $action = $request->route()->getActionName();
        $allowed = [
            'CustomerController' => ['index', 'store', 'show', 'update', 'verify', 'verifyBVN', 'verifyNIN', 'kycStatus'],
            'VirtualAccountController' => ['index', 'store', 'show', 'getTransactions'],
            'TransactionController' => ['index', 'show', 'summary'],
            'WalletController' => ['balance', 'transactions', 'transactionDetails', 'summary'],
        ];
        [$controller, $method] = array_pad(explode('@', $action), 2, '');
        if (! in_array($method, $allowed[class_basename($controller)] ?? [], true)) {
            return app(LogDeveloperRequest::class)->handle($request, fn () => response()->json(['success' => false, 'message' => 'This endpoint requires a dashboard login.'], 403));
        }

        $middleware = [LogDeveloperRequest::class, ApiKeyAuth::class, RateLimitApi::class];
        if ($request->hasHeader('X-MGS-Signature') || $request->hasHeader('X-MGS-Timestamp') || $request->hasHeader('X-MGS-Nonce')) {
            $middleware[] = VerifySignature::class;
        }
        return app(Pipeline::class)->send($request)->through($middleware)->then($next);
    }
}
