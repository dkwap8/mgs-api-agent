<?php

namespace App\Services\ApiKey;

use App\Models\ApiKey;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiKeyService
{
    public function rotate(
        int $merchantId,
        int $businessId,
        string $environment = ApiKey::ENVIRONMENT_TEST,
        array $ipWhitelist = []
    ): array {
        return DB::transaction(function () use ($merchantId, $businessId, $environment, $ipWhitelist): array {
            $business = Business::query()
                ->whereKey($businessId)
                ->where('merchant_id', $merchantId)
                ->lockForUpdate()
                ->first();

            if (! $business) {
                throw ValidationException::withMessages([
                    'business' => ['The selected business does not belong to this merchant.'],
                ]);
            }

            if ($environment === ApiKey::ENVIRONMENT_LIVE && ! $business->isApproved()) {
                throw ValidationException::withMessages([
                    'environment' => ['Live API keys require an approved business.'],
                ]);
            }

            ApiKey::query()
                ->where('merchant_id', $merchantId)
                ->where('business_id', $businessId)
                ->where('environment', $environment)
                ->where('status', ApiKey::STATUS_ACTIVE)
                ->lockForUpdate()
                ->update([
                    'status' => ApiKey::STATUS_REVOKED,
                    'revoked_at' => now(),
                ]);

            $publicKey = ($environment === ApiKey::ENVIRONMENT_LIVE ? 'MGS_PK_LIVE_' : 'MGS_PK_TEST_').Str::random(40);
            $secretKey = ($environment === ApiKey::ENVIRONMENT_LIVE ? 'MGS_SK_LIVE_' : 'MGS_SK_TEST_').Str::random(60);
            $webhookSecret = 'MGS_WHSEC_'.Str::random(48);

            $apiKey = ApiKey::query()->create([
                'merchant_id' => $merchantId,
                'business_id' => $businessId,
                'public_key' => $publicKey,
                'secret_key_hash' => Hash::make($secretKey),
                'secret_key_fingerprint' => hash('sha256', $secretKey),
                'webhook_secret' => $webhookSecret,
                'environment' => $environment,
                'status' => ApiKey::STATUS_ACTIVE,
                'ip_whitelist' => array_values(array_unique($ipWhitelist)),
                'last_rotated_at' => now(),
            ]);

            return [
                'id' => $apiKey->id,
                'business_id' => $businessId,
                'public_key' => $publicKey,
                'secret_key' => $secretKey,
                'webhook_secret' => $webhookSecret,
                'environment' => $environment,
                'ip_whitelist' => $apiKey->ip_whitelist,
                'warning' => 'Store the secret key and webhook secret now. They will not be shown again.',
            ];
        }, 3);
    }

    public function generate(int $merchantId, int $businessId, string $environment = ApiKey::ENVIRONMENT_TEST): array
    {
        return $this->rotate($merchantId, $businessId, $environment);
    }

    public function revoke(ApiKey $apiKey): ApiKey
    {
        if ($apiKey->status !== ApiKey::STATUS_REVOKED) {
            $apiKey->update([
                'status' => ApiKey::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);
        }

        return $apiKey->fresh();
    }
}
