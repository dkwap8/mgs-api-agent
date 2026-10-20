<?php

namespace Tests\Feature;

use App\Models\{User, Merchant, Business, BusinessActivation, BankAccount, MerchantWithdrawalSetting, Payout, WithdrawalOtp, Provider, ApiKey, WalletTransaction};
use App\Services\{PayoutService, WalletService};
use App\Http\Controllers\Api\Admin\AdminPayoutController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Queue, Event, Http};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentSafetyTest extends \Tests\Support\PaymentTestCase
{
    public function test_otp_is_consumed_atomically_and_retry_does_not_reserve_twice(): void
    {
        $otp = $this->otp();
        $first = $this->payout($otp);
        $second = $this->payout($otp);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('used', $otp->fresh()->status);
        $this->assertSame(1, Payout::count());
        $this->assertSame('900.00', $this->business->wallet->available_balance);
        $this->assertSame('100.00', $this->business->wallet->reserved_balance);
    }

    public function test_a_fabricated_otp_marker_cannot_authorize_money_movement(): void
    {
        $this->expectException(ValidationException::class);
        app(PayoutService::class)->requestPayout($this->merchant->id, $this->business->id, $this->bank->id, 100, '1234', ['withdrawal_otp_request_id' => 'fake']);
    }

    public function test_daily_limit_includes_previously_reserved_payouts(): void
    {
        $this->payout();
        try { $this->payout(); $this->fail('Second payout should exceed the daily limit.'); }
        catch (ValidationException) {}
        $this->assertSame(1, Payout::count());
        $this->assertSame('900.00', $this->business->wallet->available_balance);
    }

    public function test_unverified_bank_cannot_receive_payout(): void
    {
        $this->bank->update(['is_verified' => false]);
        $this->expectException(ValidationException::class);
        $this->payout();
    }

    public function test_processing_payout_cannot_be_reapproved_or_rejected_and_retry_only_reconciles(): void
    {
        $payout = $this->payout();
        $payout->update(['status' => 'processing']);
        $controller = app(AdminPayoutController::class);
        $this->assertSame(422, $controller->approve($payout->id)->getStatusCode());
        $request = \Illuminate\Http\Request::create('/test', 'POST', ['reason' => 'Do not refund an unknown outcome']);
        $this->assertSame(422, $controller->reject($request, $payout->id)->getStatusCode());
        $this->assertSame(202, $controller->retryProcessing($payout->id)->getStatusCode());
        $this->assertSame('processing', $payout->fresh()->status);
        $this->assertSame('100.00', $this->business->wallet->reserved_balance);
    }

    public function test_successful_webhook_preserves_provider_reference_and_captures_once(): void
    {
        $payout = $this->payout();
        $payout->update(['status' => 'processing', 'metadata' => ['provider_id' => 42]]);
        $payload = ['reference' => $payout->reference, 'provider_reference' => 'UPSTREAM-123', 'status' => 'success'];
        $service = app(PayoutService::class);
        $service->settleFromProviderWebhook(42, $payload);
        $service->settleFromProviderWebhook(42, $payload);
        $this->assertSame('UPSTREAM-123', $payout->fresh()->provider_reference);
        $this->assertSame('0.00', $this->business->wallet->reserved_balance);
        $this->assertSame('900.00', $this->business->wallet->ledger_balance);
        $this->assertSame(1, WalletTransaction::where('source_type', Payout::class)->count());
    }

    public function test_webhook_requires_original_provider_binding(): void
    {
        $payout = $this->payout();
        $this->expectException(\RuntimeException::class);
        app(PayoutService::class)->settleFromProviderWebhook(42, ['reference' => $payout->reference, 'status' => 'success']);
    }

    public function test_api_key_reads_only_its_business_and_cannot_use_dashboard_controls(): void
    {
        $key = ApiKey::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'public_key' => 'MGS_PK_live_test', 'secret_key_hash' => Hash::make('test-secret'), 'environment' => 'live', 'status' => 'active']);
        $headers = ['X-MGS-Public-Key' => $key->public_key, 'Authorization' => 'Bearer test-secret', 'X-Business-ID' => '999'];
        $this->getJson('/api/v1/wallet/balance', $headers)->assertOk()->assertJsonPath('data.available_balance', '1000.00');
        $this->getJson('/api/v1/merchant/api-keys', $headers)->assertForbidden();
        $this->postJson('/api/v1/payouts', [], $headers)->assertForbidden();
        $key->update(['environment' => 'test']);
        $this->getJson('/api/v1/wallet/balance', $headers)->assertStatus(503);
    }

    public function test_suspended_business_cannot_send_or_verify_withdrawal_otp(): void
    {
        $this->business->update(['verification_status' => 'suspended']);
        $this->actingAs($this->user)->postJson('/api/v1/payouts/send-otp', [])->assertForbidden();
        $this->actingAs($this->user)->postJson('/api/v1/payouts/verify-otp', [])->assertForbidden();
    }
}
