<?php
namespace Tests\Feature;

use App\Models\{Provider, Customer, VirtualAccount, Transaction, WebhookEndpoint, WebhookLog};
use App\Services\CollectionService;
use Tests\Support\PaymentTestCase;

class CollectionSafetyTest extends PaymentTestCase
{
    private function collection(): array
    {
        $provider = Provider::create(['name' => 'Test Provider', 'slug' => 'test-provider', 'type' => 'virtual_account', 'is_active' => true, 'health_status' => 'healthy']);
        $customer = Customer::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'first_name' => 'Test', 'last_name' => 'Customer']);
        $va = VirtualAccount::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'customer_id' => $customer->id, 'provider_id' => $provider->id, 'reference' => 'VA-TEST', 'account_number' => '9999999999', 'account_name' => 'Test Customer', 'bank_name' => 'Test Bank', 'status' => 'active']);
        $transaction = Transaction::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'customer_id' => $customer->id, 'virtual_account_id' => $va->id, 'provider_id' => $provider->id, 'reference' => 'COL-TEST', 'provider_reference' => 'UPSTREAM-COL', 'amount' => 100, 'fee' => 5, 'merchant_amount' => 95, 'type' => 'collection', 'status' => 'pending']);
        return [$transaction, $provider];
    }
    public function test_pending_verification_persists_without_credit(): void
    {
        [$transaction, $provider] = $this->collection();
        app(CollectionService::class)->settleVerified($transaction, $provider, ['status' => 'unknown']);
        $this->assertSame('processing', $transaction->fresh()->status);
        $this->assertSame('unknown', $transaction->fresh()->metadata['verification_status']);
        $this->assertSame('1000.00', $this->business->wallet->available_balance);
    }
    public function test_duplicate_success_credits_once_and_persists_one_delivery(): void
    {
        [$transaction, $provider] = $this->collection();
        WebhookEndpoint::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'url' => 'https://merchant.example.test/hook', 'secret' => 'test-secret', 'events' => ['collection.success'], 'environment' => 'live', 'status' => 'active']);
        $verification = ['status' => 'success', 'provider_reference' => 'UPSTREAM-COL', 'amount' => '100.00'];
        app(CollectionService::class)->settleVerified($transaction, $provider, $verification);
        app(CollectionService::class)->settleVerified($transaction, $provider, $verification);
        $this->assertSame('1095.00', $this->business->wallet->available_balance);
        $this->assertSame(1, WebhookLog::count());
        $this->assertSame('pending', WebhookLog::first()->status);
    }
    public function test_success_without_amount_cannot_credit(): void
    {
        [$transaction, $provider] = $this->collection();
        $this->expectException(\RuntimeException::class);
        app(CollectionService::class)->settleVerified($transaction, $provider, ['status' => 'success', 'provider_reference' => 'UPSTREAM-COL']);
    }
    public function test_one_kobo_mismatch_cannot_credit(): void
    {
        [$transaction, $provider] = $this->collection();
        $this->expectException(\RuntimeException::class);
        app(CollectionService::class)->settleVerified($transaction, $provider, ['status' => 'success', 'provider_reference' => 'UPSTREAM-COL', 'amount' => '99.99']);
    }
}
