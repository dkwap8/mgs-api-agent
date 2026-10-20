<?php

namespace Tests\Support;

use App\Models\{User, Merchant, Business, BusinessActivation, BankAccount, MerchantWithdrawalSetting, Payout, WithdrawalOtp, Provider, ApiKey, WalletTransaction};
use App\Services\{PayoutService, WalletService};
use App\Http\Controllers\Api\Admin\AdminPayoutController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Queue, Event, Http};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

abstract class PaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Merchant $merchant;
    protected Business $business;
    protected BankAccount $bank;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
        $this->user = User::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Test User', 'email' => 'test@example.test', 'password' => Hash::make('test-password'), 'status' => 'active', 'withdrawal_pin' => Hash::make('1234')]);
        $this->merchant = Merchant::create(['user_id' => $this->user->id, 'business_name' => 'Test Merchant', 'verification_status' => 'approved', 'is_verified' => true, 'live_access' => true]);
        $this->business = Business::create(['merchant_id' => $this->merchant->id, 'name' => 'Business One', 'business_code' => 'BIZ-ONE', 'verification_status' => 'approved', 'is_verified' => true]);
        $this->user->forceFill(['current_merchant_id' => $this->merchant->id, 'current_business_id' => $this->business->id])->save();
        BusinessActivation::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'user_id' => $this->user->id, 'business_name' => 'Business One', 'business_email' => 'biz@example.test', 'business_phone' => '08000000000', 'business_description' => 'Testing', 'state' => 'Kano', 'city' => 'Kano', 'street_address' => 'Test Street', 'proof_of_address' => 'test.pdf', 'bank_name' => 'Test Bank', 'account_number' => '1234567890', 'account_name' => 'Test Merchant', 'business_type' => 'starter', 'status' => 'approved']);
        $this->bank = BankAccount::create(['merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'bank_name' => 'Test Bank', 'bank_code' => '999', 'account_name' => 'Test Merchant', 'account_number' => '1234567890', 'is_verified' => true, 'status' => 'active']);
        MerchantWithdrawalSetting::create(['merchant_type' => 'starter', 'withdrawals_enabled' => true, 'automatic_withdrawals_enabled' => false, 'minimum_withdrawal_amount' => 1, 'daily_withdrawal_limit' => 150]);
        app(WalletService::class)->createWallet($this->merchant->id, $this->business->id);
        app(WalletService::class)->credit($this->merchant->id, $this->business->id, '1000.00', 'test', 1);
    }

    protected function otp(string $amount = '100.00'): WithdrawalOtp
    {
        return WithdrawalOtp::create(['request_id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $this->user->id, 'merchant_id' => $this->merchant->id, 'business_id' => $this->business->id, 'bank_account_id' => $this->bank->id, 'amount' => $amount, 'otp_hash' => Hash::make('123456'), 'status' => 'processing', 'verified_at' => now(), 'last_sent_at' => now(), 'expires_at' => now()->addMinutes(5)]);
    }

    protected function payout(?WithdrawalOtp $otp = null): Payout
    {
        $otp ??= $this->otp();
        return app(PayoutService::class)->requestPayout($this->merchant->id, $this->business->id, $this->bank->id, $otp->amount, null, ['withdrawal_otp_request_id' => $otp->request_id]);
    }

}
