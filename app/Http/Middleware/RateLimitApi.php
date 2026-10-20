<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApi
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        $identity = $request->attributes->get('api_key')?->id
            ?? $request->user()?->id
            ?? $request->ip();
        $key = 'api:'.sha1($request->route()?->getName().'|'.$identity);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            return new JsonResponse([
                'success' => false,
                'message' => 'Too many requests.',
                'retry_after' => $retryAfter,
            ], 429, ['Retry-After' => (string) $retryAfter]);
        }

        $this->limiter->hit($key, $decaySeconds);
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $maxAttempts));
        return $response;
    }
}
