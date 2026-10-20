<?php

namespace App\Services\ProviderServices;

use App\Contracts\ProviderInterface;
use App\Models\Customer;
use App\Models\Payout;
use App\Models\Provider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BaseProviderService implements ProviderInterface
{
    protected ?Provider $provider;
    protected string $baseUrl = '';
    protected string $apiKey = '';
    protected string $secretKey = '';
    protected string $webhookSecret = '';
    protected int $timeout = 15;
    protected bool $sandbox = false;

    public function __construct(?Provider $provider = null)
    {
        $this->setProvider($provider);
    }

    public function setProvider(?Provider $provider): static
    {
        $this->provider = $provider;
        $config = $provider?->config ?? [];
        $metadata = $provider?->metadata ?? [];

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? config('services.provider.base_url', '')), '/');
        $this->apiKey = (string) ($config['api_key'] ?? config('services.provider.api_key', ''));
        $this->secretKey = (string) ($config['secret_key'] ?? config('services.provider.secret_key', ''));
        $this->webhookSecret = (string) ($config['webhook_secret'] ?? $this->secretKey);
        $this->sandbox = (bool) ($config['sandbox'] ?? false);
        $this->timeout = max(5, min((int) ($metadata['timeout'] ?? 15), 120));

        return $this;
    }

    public function provider(): ?Provider
    {
        return $this->provider;
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->provider?->config ?? [], $key, $default);
    }

    protected function defaultMetadata(): array
    {
        return [];
    }

    protected function metadata(string $key, mixed $default = null): mixed
    {
        $metadata = array_replace_recursive($this->defaultMetadata(), $this->provider?->metadata ?? []);

        return Arr::get($metadata, $key, $default);
    }

    protected function requireBaseUrl(): string
    {
        if ($this->baseUrl === '') {
            throw new RuntimeException(sprintf(
                'The %s provider does not have a base URL configured.',
                $this->provider?->name ?? 'selected'
            ));
        }

        return $this->baseUrl;
    }

    protected function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout(min($this->timeout, 10))
            ->timeout($this->timeout);

        $authScheme = Str::lower((string) $this->metadata('auth.scheme', 'bearer'));

        if ($authScheme === 'basic') {
            $request = $request->withBasicAuth($this->apiKey, $this->secretKey);
        } elseif ($authScheme === 'bearer' && $this->secretKey !== '') {
            $request = $request->withToken($this->secretKey);
        } elseif ($authScheme === 'api_key' && $this->apiKey !== '') {
            $request = $request->withHeader(
                (string) $this->metadata('auth.api_key_header', 'X-API-Key'),
                $this->apiKey
            );
        }

        foreach ((array) $this->metadata('auth.headers', []) as $header => $value) {
            $request = $request->withHeader((string) $header, (string) $value);
        }

        if ($authScheme !== 'api_key' && $this->apiKey !== '') {
            $request = $request->withHeader(
                (string) $this->metadata('auth.api_key_header', 'X-API-Key'),
                $this->apiKey
            );
        }

        return $request;
    }

    protected function endpoint(string $operation, array $replacements = []): string
    {
        if (app()->environment('production') && ! Arr::get($this->provider?->metadata ?? [], 'endpoints.'.$operation)) {
            throw new RuntimeException('This provider operation requires an explicit contracted endpoint configuration before production use.');
        }
        $defaults = [
            'virtual_account_create' => '/virtual-account',
            'transaction_verify' => '/transactions/{reference}/verify',
            'payout_create' => '/payouts',
            'payout_verify' => '/payouts/{reference}',
            'bank_verify' => '/bank/verify',
            'health' => '/health',
        ];

        $path = (string) $this->metadata(
            'endpoints.' . $operation,
            $defaults[$operation] ?? ''
        );

        foreach ($replacements as $key => $value) {
            $path = str_replace('{' . $key . '}', urlencode((string) $value), $path);
        }

        return $this->requireBaseUrl() . '/' . ltrim($path, '/');
    }

    protected function responseData(array $response, string $operation): array
    {
        $path = (string) $this->metadata('responses.' . $operation . '.data_path', 'data');
        $data = $path === '' ? $response : Arr::get($response, $path, $response);

        return is_array($data) ? $data : $response;
    }

    protected function mappedValue(array $data, string $operation, string $field, mixed $default = null): mixed
    {
        $path = (string) $this->metadata('responses.' . $operation . '.fields.' . $field, $field);

        return Arr::get($data, $path, $default);
    }

    public function createVirtualAccount(Customer $customer, array $metadata = []): array
    {
        $response = $this->request()->post(
            $this->endpoint('virtual_account_create'),
            [
                'customer' => [
                    'name' => $customer->fullName(),
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'customer_reference' => $customer->external_reference,
                ],
                'metadata' => $metadata,
            ]
        );

        if (! $response->successful()) {
            Log::error('Provider virtual account creation failed.', [
                'provider_id' => $this->provider?->id,
                'provider' => $this->provider?->slug,
                'customer_id' => $customer->id,
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException(
                $response->json('message') ?? 'The provider failed to create the virtual account.'
            );
        }

        $data = $this->responseData($response->json() ?? [], 'virtual_account_create');

        return [
            'account_number' => $this->mappedValue($data, 'virtual_account_create', 'account_number'),
            'account_name' => $this->mappedValue($data, 'virtual_account_create', 'account_name'),
            'bank_name' => $this->mappedValue($data, 'virtual_account_create', 'bank_name'),
            'bank_code' => $this->mappedValue($data, 'virtual_account_create', 'bank_code'),
            'provider_customer_id' => $this->mappedValue($data, 'virtual_account_create', 'provider_customer_id', $data['customer_id'] ?? null),
            'expires_at' => $this->mappedValue($data, 'virtual_account_create', 'expires_at'),
            'metadata' => $this->mappedValue($data, 'virtual_account_create', 'metadata', $metadata),
        ];
    }

    public function verifyTransaction($reference): array
    {
        $response = $this->request()->get(
            $this->endpoint('transaction_verify', ['reference' => $reference])
        );

        if (! $response->successful()) {
            return [
                'status' => 'unknown',
                'message' => $response->json('message') ?? 'Transaction verification failed.',
                'status_code' => $response->status(),
            ];
        }

        return $this->responseData($response->json() ?? [], 'transaction_verify');
    }

    public function sendPayout(Payout $payout): array
    {
        $payout->loadMissing('bankAccount');

        $payload = [
                'amount' => (string) $payout->amount,
                'bank_code' => $payout->bankAccount?->bank_code,
                'account_number' => $payout->bankAccount?->account_number,
                'reference' => $payout->reference,
                'narration' => $payout->narration,
            ];
        $response = $this->request()->withBody(\App\Support\Money::json($payload), 'application/json')->post($this->endpoint('payout_create'));

        if (! $response->successful()) {
            return [
                'status' => 'unknown',
                'message' => $response->json('message') ?? 'The payout outcome must be reconciled.',
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ];
        }

        $data = $this->responseData($response->json() ?? [], 'payout_create');

        return [
            'status' => $this->mappedValue($data, 'payout_create', 'status', 'unknown'),
            'reference' => $this->mappedValue($data, 'payout_create', 'reference', $this->mappedValue($data, 'payout_create', 'provider_reference')),
            'provider_reference' => $this->mappedValue($data, 'payout_create', 'provider_reference', $this->mappedValue($data, 'payout_create', 'reference')),
            'message' => $this->mappedValue($data, 'payout_create', 'message'),
            'response' => $data,
        ];
    }

    public function verifyPayout(Payout $payout): array
    {
        $reference = $payout->provider_reference ?: $payout->reference;

        $response = $this->request()->get(
            $this->endpoint('payout_verify', ['reference' => $reference])
        );

        if (! $response->successful()) {
            return [
                'status' => 'unknown',
                'message' => $response->json('message') ?? 'Payout status verification failed.',
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ];
        }

        $data = $this->responseData($response->json() ?? [], 'payout_verify');

        return [
            'status' => $this->mappedValue($data, 'payout_verify', 'status', 'unknown'),
            'reference' => $this->mappedValue($data, 'payout_verify', 'reference', $payout->reference),
            'provider_reference' => $this->mappedValue($data, 'payout_verify', 'provider_reference', $this->mappedValue($data, 'payout_verify', 'reference', $payout->provider_reference)),
            'message' => $this->mappedValue($data, 'payout_verify', 'message'),
            'response' => $data,
        ];
    }

    public function handleWebhook($payload, $signature): array
    {
        $payload = is_array($payload) ? $payload : [];
        $signature = trim((string) $signature);

        if ($this->webhookSecret === '') {
            throw new RuntimeException('The provider webhook secret has not been configured.');
        }

        $algorithm = Str::lower((string) $this->metadata('webhook.algorithm', 'sha256'));

        if (! in_array($algorithm, hash_hmac_algos(), true)) {
            throw new RuntimeException('The configured provider webhook algorithm is not supported.');
        }

        $encoding = Str::lower((string) $this->metadata('webhook.encoding', 'hex'));
        $raw = $encoding !== 'base64';
        $computed = hash_hmac(
            $algorithm,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $this->webhookSecret,
            $raw
        );

        if ($encoding === 'base64') {
            $computed = base64_encode($computed);
        }

        $prefix = (string) $this->metadata('webhook.signature_prefix', $algorithm . '=');

        if ($prefix !== '' && str_starts_with($signature, $prefix)) {
            $signature = substr($signature, strlen($prefix));
        }

        if ($signature === '' || ! hash_equals($computed, $signature)) {
            throw new RuntimeException('Invalid webhook signature.');
        }

        return [
            'event' => Arr::get($payload, (string) $this->metadata('webhook.fields.event', 'event')),
            'reference' => Arr::get($payload, (string) $this->metadata('webhook.fields.reference', 'reference')),
            'amount' => Arr::get($payload, (string) $this->metadata('webhook.fields.amount', 'amount')),
            'status' => Arr::get($payload, (string) $this->metadata('webhook.fields.status', 'status')),
            'payload' => $payload,
        ];
    }

    public function healthCheck(): array
    {
                $startedAt = microtime(true);

        try {
            $response = $this->request()->get($this->endpoint('health'));
            $responseTime = (int) round((microtime(true) - $startedAt) * 1000);

            return [
                'status' => $response->successful()
                    ? Provider::HEALTH_HEALTHY
                    : ($response->serverError() ? Provider::HEALTH_UNHEALTHY : Provider::HEALTH_DEGRADED),
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => Provider::HEALTH_UNHEALTHY,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function verifyBankAccount($accountNumber, $bankCode): array
    {
        $response = $this->request()->post(
            $this->endpoint('bank_verify'),
            [
                'account_number' => (string) $accountNumber,
                'bank_code' => (string) $bankCode,
            ]
        );

        if (! $response->successful()) {
            return [
                'status' => 'failed',
                'message' => $response->json('message') ?? 'Bank account verification failed.',
                'status_code' => $response->status(),
            ];
        }

        $data = $this->responseData($response->json() ?? [], 'bank_verify');

        return [
            'status' => $this->mappedValue($data, 'bank_verify', 'status', 'success'),
            'account_name' => $this->mappedValue($data, 'bank_verify', 'account_name'),
            'bank_name' => $this->mappedValue($data, 'bank_verify', 'bank_name'),
            'account_number' => (string) $accountNumber,
            'bank_code' => (string) $bankCode,
            'response' => $data,
        ];
    }
}
