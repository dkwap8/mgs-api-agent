<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicKey = trim((string) $request->header('X-MGS-Public-Key'));
        $authorization = trim((string) $request->header('Authorization'));

        if (! str_starts_with($authorization, 'Bearer ')) {
            return response()->json(['status' => 'error', 'message' => 'Missing API credentials.'], 401);
        }

        $secretKey = trim(substr($authorization, 7));
        if ($secretKey === '') {
            return response()->json(['status' => 'error', 'message' => 'Missing API secret key.'], 401);
        }

        $apiKey = ApiKey::query()
            ->with(['merchant.user', 'business'])
            ->when($publicKey !== '', fn ($q) => $q->where('public_key', $publicKey),
                fn ($q) => $q->where('secret_key_fingerprint', hash('sha256', $secretKey)))
            ->where('status', ApiKey::STATUS_ACTIVE)
            ->whereNull('revoked_at')
            ->first();

        if (! $apiKey || ! Hash::check($secretKey, $apiKey->secret_key_hash)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid API credentials.'], 401);
        }

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('merchant', $apiKey->merchant);
        $request->attributes->set('business', $apiKey->business);

        if (! $apiKey->isLive()) {
            return response()->json(['success' => false, 'message' => 'Test API access is unavailable until the isolated sandbox is configured.'], 503);
        }

        if ($apiKey->ip_whitelist && ! in_array($request->ip(), $apiKey->ip_whitelist, true)) {
            return response()->json(['status' => 'error', 'message' => 'IP address is not whitelisted.'], 403);
        }

        $merchant = $apiKey->merchant;
        if (! $merchant || $merchant->user?->status !== 'active' || ! $merchant->isApproved() || $merchant->isSuspended()) {
            return response()->json(['status' => 'error', 'message' => 'Merchant account is not active.'], 403);
        }

        $business = $apiKey->business;

        if (
            ! $apiKey->business_id ||
            ! $business ||
            (int) $business->merchant_id !== (int) $merchant->id ||
            ! $business->isApproved() ||
            $business->isSuspended()
        ) {
            return response()->json(['status' => 'error', 'message' => 'The API key business is not active.'], 403);
        }

        if ($apiKey->isLive() && ! $merchant->live_access) {
            return response()->json(['status' => 'error', 'message' => 'Live API access is disabled.'], 403);
        }

        ApiKey::query()->whereKey($apiKey->id)->update(['last_used_at' => now()]);

        $request->attributes->set('merchant', $merchant);
        $request->attributes->set('business', $business);
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_signing_secret', $secretKey);

        return $next($request);
    }
}
