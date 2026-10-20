<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = trim((string) $request->header('X-MGS-Signature'));
        $timestamp = trim((string) $request->header('X-MGS-Timestamp'));
        $nonce = trim((string) $request->header('X-MGS-Nonce'));

        if ($signature === '' || $timestamp === '') {
            return $this->deny('Missing signature headers.');
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return $this->deny('Request timestamp expired.');
        }

        $apiKey = $request->attributes->get('api_key');
        if (! $apiKey || ! $apiKey->webhook_secret) {
            return $this->deny('Invalid API credentials.');
        }

        $payload = $nonce !== '' ? $timestamp.'.'.$nonce.'.'.$request->getContent() : $request->getContent().$timestamp;
        $secret = $nonce !== '' ? $apiKey->webhook_secret : $request->attributes->get('api_signing_secret');
        if (! is_string($secret) || $secret === '') return $this->deny('Invalid signing credentials.');
        $computed = hash_hmac('sha256', $payload, $secret);
        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;

        if (! hash_equals($computed, $signature)) {
            return $this->deny('Invalid signature.');
        }

        $nonceKey = 'api-signature-nonce:'.$apiKey->id.':'.hash('sha256', $nonce !== '' ? $nonce : $signature);
        if (! Cache::add($nonceKey, true, now()->addMinutes(10))) {
            return $this->deny('Request replay detected.', 409);
        }

        return $next($request);
    }

    private function deny(string $message, int $status = 401): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }
}
