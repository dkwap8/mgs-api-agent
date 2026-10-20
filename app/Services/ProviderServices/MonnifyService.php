<?php

namespace App\Services\ProviderServices;

use App\Models\Customer;
use App\Models\Payout;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MonnifyService extends BaseProviderService
{
    protected function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(min($this->timeout, 10))
            ->timeout($this->timeout)
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        $providerId = $this->provider?->id ?? 0;

        return Cache::remember("providers.monnify.{$providerId}.token", now()->addMinutes(45), function (): string {
            if ($this->apiKey === '' || $this->secretKey === '') {
                throw new RuntimeException('Monnify API key and secret key are required.');
            }

            $response = Http::acceptJson()
                ->withBasicAuth($this->apiKey, $this->secretKey)
                ->connectTimeout(10)
                ->timeout($this->timeout)
                ->post($this->requireBaseUrl() . '/api/v1/auth/login');

            $token = (string) ($response->json('responseBody.accessToken') ?? '');

            if (! $response->successful() || $token === '') {
                throw new RuntimeException($response->json('responseMessage') ?? 'Unable to authenticate with Monnify.');
            }

            return $token;
        });
    }

    public function createVirtualAccount(Customer $customer, array $metadata = []): array
    {
        $contractCode = (string) $this->config('contract_code');
        if ($contractCode === '') {
            throw new RuntimeException('Monnify contract code is required.');
        }

        $reference = $customer->external_reference ?: 'CUS-' . $customer->id;
        $response = $this->request()->post($this->requireBaseUrl() . '/api/v2/bank-transfer/reserved-accounts', [
            'accountReference' => $reference,
            'accountName' => $customer->fullName(),
            'currencyCode' => 'NGN',
            'contractCode' => $contractCode,
            'customerEmail' => $customer->email,
            'customerName' => $customer->fullName(),
            'getAllAvailableBanks' => (bool) $this->config('get_all_available_banks', false),
            'preferredBanks' => (array) $this->config('preferred_banks', []),
            'incomeSplitConfig' => [],
        ]);

        $body = $response->json() ?? [];
        $data = $body['responseBody'] ?? [];
        $account = $data['accounts'][0] ?? [];

        if (! $response->successful() || empty($account['accountNumber'])) {
            throw new RuntimeException($body['responseMessage'] ?? 'Monnify failed to create the reserved account.');
        }

        return [
            'account_number' => $account['accountNumber'],
            'account_name' => $account['accountName'] ?? $data['accountName'] ?? $customer->fullName(),
            'bank_name' => $account['bankName'] ?? 'Monnify',
            'bank_code' => $account['bankCode'] ?? null,
            'provider_customer_id' => $data['accountReference'] ?? $reference,
            'expires_at' => null,
            'metadata' => ['provider_response' => $data, 'request_metadata' => $metadata],
        ];
    }

    public function verifyTransaction($reference): array
    {
        $response = $this->request()->get($this->requireBaseUrl() . '/api/v2/transactions/' . urlencode((string) $reference));
        $body = $response->json() ?? [];
        $data = $body['responseBody'] ?? [];

        return [
            'status' => $response->successful() ? ($data['paymentStatus'] ?? 'unknown') : 'unknown',
            'reference' => $data['transactionReference'] ?? $reference,
            'provider_reference' => $data['transactionReference'] ?? $reference,
            'amount' => $data['amountPaid'] ?? $data['amount'] ?? null,
            'message' => $body['responseMessage'] ?? null,
            'response' => $body,
        ];
    }

    public function verifyBankAccount($accountNumber, $bankCode): array
    {
        $response = $this->request()->get($this->requireBaseUrl().'/api/v2/disbursements/account/validate', [
            'accountNumber' => (string) $accountNumber, 'bankCode' => (string) $bankCode,
        ]);
        $body = $response->json() ?? [];
        $data = $body['responseBody'] ?? [];
        $valid = $response->successful() && ($body['requestSuccessful'] ?? false) === true
            && (string) ($body['responseCode'] ?? '') === '0'
            && is_string($data['accountName'] ?? null) && trim($data['accountName']) !== ''
            && (string) ($data['accountNumber'] ?? '') === (string) $accountNumber;
        return [
            'status' => $valid ? 'success' : 'failed',
            'account_name' => $valid ? $data['accountName'] : null,
            'account_number' => (string) $accountNumber, 'bank_code' => (string) $bankCode,
            'bank_name' => $data['bankName'] ?? null,
            'message' => $body['responseMessage'] ?? 'Bank account verification failed.',
            'response' => $body,
        ];
    }

    public function sendPayout(Payout $payout): array
    {
        $payout->loadMissing('bankAccount');
        $payload = [
            'amount' => (string) $payout->amount,
            'reference' => $payout->reference,
            'narration' => $payout->narration,
            'destinationBankCode' => $payout->bankAccount?->bank_code,
            'destinationAccountNumber' => $payout->bankAccount?->account_number,
            'destinationAccountName' => $payout->bankAccount?->account_name,
            'currency' => 'NGN',
            'sourceAccountNumber' => $this->config('wallet_account_number'),
            'async' => true,
        ];
        $response = $this->request()->withBody(\App\Support\Money::json($payload), 'application/json')->post($this->requireBaseUrl().'/api/v2/disbursements/single');

        $body = $response->json() ?? [];
        $data = $body['responseBody'] ?? [];

        return [
            'status' => $response->successful() ? ($data['status'] ?? 'pending') : 'unknown',
            'reference' => $payout->reference,
            'provider_reference' => $data['transactionReference'] ?? $data['reference'] ?? $payout->reference,
            'message' => $body['responseMessage'] ?? null,
            'response' => $body,
        ];
    }

    public function verifyPayout(Payout $payout): array
    {
        $reference = $payout->reference;
        $response = $this->request()->get($this->requireBaseUrl() . '/api/v2/disbursements/single/summary', ['reference' => $reference]);
        $body = $response->json() ?? [];
        $data = $body['responseBody'] ?? [];

        return [
            'status' => $response->successful() ? ($data['status'] ?? 'unknown') : 'unknown',
            'reference' => $payout->reference,
            'provider_reference' => $data['transactionReference'] ?? $data['reference'] ?? $reference,
            'message' => $body['responseMessage'] ?? null,
            'response' => $body,
        ];
    }
}
