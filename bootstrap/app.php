<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidKYCException;
use App\Exceptions\MerchantNotVerifiedException;
use App\Exceptions\PayoutLimitExceededException;
use App\Exceptions\ProviderException;
use App\Exceptions\SignatureVerificationException;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\PlatformMaintenance::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\BrowserAuthentication::class);
        $middleware->alias([
            'merchant.access' => \App\Http\Middleware\AuthenticateMerchantRequest::class,
            'api.auth' => \App\Http\Middleware\ApiKeyAuth::class,
            'merchant.context' => \App\Http\Middleware\EnsureActiveMerchant::class,
            'payout.security' => \App\Http\Middleware\CheckPayoutSecurity::class,
            'api.rate' => \App\Http\Middleware\RateLimitApi::class,
            'two.factor' => \App\Http\Middleware\TwoFactorAuth::class,
            'verify.signature' => \App\Http\Middleware\VerifySignature::class,
            'admin' => \App\Http\Middleware\EnsureAdministrator::class,
            'user' => \App\Http\Middleware\EnsureUser::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
            'business.active' => \App\Http\Middleware\EnsureActiveBusiness::class,
            'idempotency' => \App\Http\Middleware\EnsureIdempotency::class,
        ]);

        $middleware->append(\App\Http\Middleware\RequestId::class);
        $middleware->append(\App\Http\Middleware\NormalizeApiResponse::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The submitted data is invalid.', 422, $exception->errors(), 'VALIDATION_FAILED')
                : null;
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('Authentication is required.', 401, code: 'UNAUTHENTICATED')
                : null;
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The requested resource was not found.', 404, code: 'RESOURCE_NOT_FOUND')
                : null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('You are not authorised to perform this action.', 403, code: 'FORBIDDEN')
                : null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('Too many requests. Please try again later.', 429, code: 'RATE_LIMITED')
                : null;
        });

        $exceptions->render(function (InsufficientBalanceException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error($exception->getMessage() ?: 'Insufficient wallet balance.', 422, code: 'INSUFFICIENT_BALANCE')
                : null;
        });

        $exceptions->render(function (InvalidKYCException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error($exception->getMessage() ?: 'KYC verification is incomplete or invalid.', 422, code: 'INVALID_KYC')
                : null;
        });

        $exceptions->render(function (MerchantNotVerifiedException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error($exception->getMessage() ?: 'The merchant account is not verified.', 403, code: 'MERCHANT_NOT_VERIFIED')
                : null;
        });

        $exceptions->render(function (PayoutLimitExceededException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error($exception->getMessage() ?: 'The payout limit has been exceeded.', 422, code: 'PAYOUT_LIMIT_EXCEEDED')
                : null;
        });

        $exceptions->render(function (SignatureVerificationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error($exception->getMessage() ?: 'The request signature is invalid.', 401, code: 'INVALID_SIGNATURE')
                : null;
        });

        $exceptions->render(function (ProviderException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The upstream provider could not complete the request.', 503, code: 'PROVIDER_UNAVAILABLE')
                : null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'The request could not be completed.',
                $exception->getStatusCode(),
                code: 'HTTP_ERROR'
            );
        });
    })->create();
