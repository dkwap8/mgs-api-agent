<?php

namespace App\Services\KYCProviderServices;

use App\Contracts\KYCProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HttpKYCProvider implements KYCProviderInterface
{
    public function verify(string $type, string $number, ?string $name = null): array
    {
        $endpoint = (string) config("kyc.endpoints.{$type}");
        if ($endpoint === '') {
            return [
                'status' => 'failed',
                'message' => strtoupper($type).' verification endpoint is not configured.',
                'retryable' => false,
            ];
        }

        try {
            $response = $this->client()->post($endpoint, array_filter([
                'type' => $type,
                'number' => $number,
                'name' => $name,
            ], static fn ($value) => $value !== null && $value !== ''));

            $payload = $response->json();
            if (! is_array($payload)) {
                $payload = ['body' => $response->body()];
            }

            if (! $response->successful()) {
                return [
                    'status' => $response->serverError() || $response->status() === 429 ? 'pending' : 'failed',
                    'message' => (string) Arr::get($payload, config('kyc.response.message', 'message'), 'Identity verification request failed.'),
                    'retryable' => $response->serverError() || $response->status() === 429,
                    'raw' => $payload,
                ];
            }

            $providerStatus = strtolower((string) Arr::get($payload, config('kyc.response.status', 'status'), ''));
            $successStatuses = array_map('strtolower', (array) config('kyc.success_statuses', ['verified', 'success', 'successful']));
            $pendingStatuses = array_map('strtolower', (array) config('kyc.pending_statuses', ['pending', 'processing']));

            $status = in_array($providerStatus, $successStatuses, true)
                ? 'verified'
                : (in_array($providerStatus, $pendingStatuses, true) ? 'pending' : 'failed');

            return [
                'status' => $status,
                'message' => (string) Arr::get($payload, config('kyc.response.message', 'message'), ''),
                'name' => Arr::get($payload, config('kyc.response.name', 'data.name')),
                'dob' => Arr::get($payload, config('kyc.response.dob', 'data.date_of_birth')),
                'reference' => Arr::get($payload, config('kyc.response.reference', 'data.reference')),
                'retryable' => $status === 'pending',
                'raw' => $payload,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => 'pending',
                'message' => 'Identity verification provider is temporarily unavailable.',
                'retryable' => true,
            ];
        }
    }

    protected function client(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('kyc.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('kyc.connect_timeout', 10))
            ->timeout((int) config('kyc.timeout', 30))
            ->retry(2, 250, throw: false);

        $scheme = strtolower((string) config('kyc.auth.scheme', 'bearer'));
        $key = (string) config('kyc.auth.key');
        $secret = (string) config('kyc.auth.secret');

        return match ($scheme) {
            'basic' => $request->withBasicAuth($key, $secret),
            'api_key' => $request->withHeader((string) config('kyc.auth.header', 'X-API-Key'), $key),
            'none' => $request,
            default => $key !== '' ? $request->withToken($key) : $request,
        };
    }
}
