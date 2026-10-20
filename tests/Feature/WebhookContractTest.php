<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebhookContractTest extends TestCase
{
    public function test_monnify_name_enquiry_uses_v2_get_and_rejects_unmatched_account(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://provider.example.test/api/v1/auth/login' => \Illuminate\Support\Facades\Http::response(['responseBody' => ['accessToken' => 'fixture-token']]),
            'https://provider.example.test/api/v2/disbursements/account/validate*' => \Illuminate\Support\Facades\Http::response([
                'requestSuccessful' => true, 'responseCode' => '0',
                'responseBody' => ['accountNumber' => '1234567890', 'accountName' => 'Fixture Name'],
            ]),
        ]);
        $provider = new Provider(['slug' => 'monnify', 'config' => ['base_url' => 'https://provider.example.test', 'api_key' => 'fixture-key', 'secret_key' => 'fixture-secret']]);
        $service = new \App\Services\ProviderServices\MonnifyService($provider);
        $this->assertSame('success', $service->verifyBankAccount('1234567890', '999')['status']);
        $this->assertSame('failed', $service->verifyBankAccount('9999999999', '999')['status']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/api/v2/disbursements/account/validate'));
    }

    public function test_monnify_disbursement_keeps_merchant_and_provider_references_distinct(): void
    {
        $result = app(WebhookService::class)->normalizeProviderPayload(new Provider(['slug' => 'monnify']), [
            'eventType' => 'SUCCESSFUL_DISBURSEMENT',
            'eventData' => ['reference' => 'PO-merchant', 'transactionReference' => 'MFDS-provider', 'status' => 'SUCCESS'],
        ]);
        $this->assertSame('payout', $result['type']);
        $this->assertSame('PO-merchant', $result['reference']);
        $this->assertSame('MFDS-provider', $result['provider_reference']);
    }

    public function test_monnify_reserved_account_collection_reads_documented_destination(): void
    {
        $result = app(WebhookService::class)->normalizeProviderPayload(new Provider(['slug' => 'monnify']), [
            'eventType' => 'SUCCESSFUL_TRANSACTION', 'eventData' => [
                'transactionReference' => 'MNFY-reference', 'currency' => 'NGN', 'amountPaid' => '3000.15',
                'paymentStatus' => 'PAID', 'destinationAccountInformation' => ['accountNumber' => '6000140770'],
            ],
        ]);
        $this->assertSame('6000140770', $result['account_number']);
        $this->assertSame('3000.15', $result['amount']);
    }

    public function test_unrelated_monnify_event_cannot_become_a_collection(): void
    {
        $this->expectException(\RuntimeException::class);
        app(WebhookService::class)->normalizeProviderPayload(new Provider(['slug' => 'monnify']), ['eventType' => 'SUCCESSFUL_REFUND']);
    }

    public function test_monnify_uses_raw_body_sha512_signature(): void
    {
        $provider = new Provider(['slug' => 'monnify', 'config' => ['secret_key' => 'test-secret']]);
        $body = '{"eventType":"SUCCESSFUL_TRANSACTION","eventData":{"amountPaid":"100.00"}}';
        $request = Request::create('/webhook', 'POST', [], [], [], [], $body);
        $request->headers->set('monnify-signature', hash_hmac('sha512', $body, 'test-secret'));
        app(WebhookService::class)->verifySignature($provider, $request);
        $this->addToAssertionCount(1);
        $request->headers->set('monnify-signature', hash_hmac('sha256', $body, 'test-secret'));
        $this->expectException(\RuntimeException::class);
        app(WebhookService::class)->verifySignature($provider, $request);
    }

    public function test_sandbox_flag_cannot_bypass_authentication_for_wallet_credit_webhook(): void
    {
        $provider = new Provider(['slug' => 'test', 'config' => ['sandbox' => true]]);
        $this->expectException(\RuntimeException::class);
        app(WebhookService::class)->verifySignature($provider, Request::create('/webhook', 'POST'));
    }
}
