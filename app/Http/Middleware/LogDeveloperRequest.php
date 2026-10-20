<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogDeveloperRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $status = 500;
        try {
            $response = $next($request);
            $status = $response->getStatusCode();
            return $response;
        } catch (\Throwable $exception) {
            $status = $exception instanceof \Illuminate\Validation\ValidationException ? 422
                : ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404
                : ($exception instanceof \Illuminate\Auth\AuthenticationException ? 401
                : ($exception instanceof \Illuminate\Auth\Access\AuthorizationException ? 403
                : ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode() : 500))));
            throw $exception;
        } finally {
            try {
                // Do not log credentials, KYC numbers, request bodies, or provider payloads.
                ApiLog::create([
                    'merchant_id' => $request->activeMerchant()?->id,
                    'business_id' => $request->activeBusiness()?->id,
                    'api_key_id' => $request->attributes->get('api_key')?->id,
                    'endpoint' => mb_substr('/'.$request->path(), 0, 255),
                    'method' => $request->method(), 'status_code' => $status,
                    'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                    'duration' => (int) round((microtime(true) - $started) * 1000),
                ]);
            } catch (\Throwable $logError) { report($logError); }
        }
    }
}
