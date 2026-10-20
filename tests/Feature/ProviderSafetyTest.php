<?php

namespace Tests\Feature;

use App\Models\{Provider, Payout};
use App\Services\ProviderServices\{BaseProviderService, MonnifyService};
use Illuminate\Support\Facades\{Http, Cache};
use Tests\TestCase;

class ProviderSafetyTest extends TestCase
{
    public function test_production_adapter_defaults_cannot_replace_an_explicit_contract(): void
    {
        app()->detectEnvironment(fn () => 'production');
        Http::fake();
        try {
            (new \App\Services\ProviderServices\PalmPayService($this->provider()))->verifyBankAccount('1234567890', '999');
            $this->fail('Uncontracted production endpoints must be blocked.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('explicit contracted endpoint', $exception->getMessage());
        }
        Http::assertNothingSent();
    }
    private function provider(): Provider
    {
        return new Provider(['name' => 'Test', 'slug' => 'test', 'config' => ['base_url' => 'https://provider.example.test']]);
    }
    private function payout(): Payout
    {
        $payout = new Payout(['amount' => 100, 'reference' => 'PO-TEST']);
        $payout->setRelation('bankAccount', null);
        return $payout;
    }
    public function test_http_error_never_means_a_payout_failed_and_post_is_not_retried(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Gateway failure'], 502)]);
        $result = (new BaseProviderService($this->provider()))->sendPayout($this->payout());
        $this->assertSame('unknown', $result['status']);
        Http::assertSentCount(1);
    }
    public function test_successful_http_response_without_status_cannot_settle_payout(): void
    {
        Http::fake(['*' => Http::response(['data' => ['reference' => 'PENDING']], 200)]);
        $this->assertSame('unknown', (new BaseProviderService($this->provider()))->sendPayout($this->payout())['status']);
    }
    public function test_monnify_http_failure_is_unknown_even_if_error_body_says_failed(): void
    {
        Cache::put('providers.monnify.0.token', 'test-token');
        Http::fake(['*' => Http::response(['responseBody' => ['status' => 'failed']], 500)]);
        $service = new MonnifyService($this->provider());
        $this->assertSame('unknown', $service->sendPayout($this->payout())['status']);
        $this->assertSame('unknown', $service->verifyPayout($this->payout())['status']);
        Http::assertSentCount(2);
    }
    public function test_provider_configuration_is_not_serialized(): void
    {
        $provider = $this->provider();
        $provider->config = ['secret_key' => 'do-not-expose'];
        $this->assertArrayNotHasKey('config', $provider->toArray());
    }
}
