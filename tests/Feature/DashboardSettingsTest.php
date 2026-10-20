<?php
namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Support\Facades\Hash;
use Tests\Support\PaymentTestCase;

class DashboardSettingsTest extends PaymentTestCase
{
    public function test_business_contact_edit_does_not_modify_merchant_or_another_business(): void
    {
        $other = Business::create(['merchant_id' => $this->merchant->id, 'name' => 'Other', 'business_code' => 'BIZ-TWO', 'metadata' => ['business_email' => 'other@example.test']]);
        $this->actingAs($this->user)->putJson('/api/v1/merchant/profile', ['business_email' => 'new@example.test'])->assertOk();
        $this->assertSame('new@example.test', $this->business->fresh()->metadata['business_email']);
        $this->assertSame('other@example.test', $other->fresh()->metadata['business_email']);
        $this->assertNull($this->merchant->fresh()->business_email);
    }
    public function test_verified_business_identity_change_requires_review(): void
    {
        $this->actingAs($this->user)->putJson('/api/v1/merchant/profile', ['business_name' => 'Unreviewed Name'])->assertUnprocessable();
        $this->assertSame('Business One', $this->business->fresh()->name);
    }
    public function test_withdrawal_pin_change_requires_current_password(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/auth/setup-withdrawal-pin', ['pin' => '5678', 'pin_confirmation' => '5678', 'password' => 'wrong'])->assertUnprocessable();
        $this->assertTrue(Hash::check('1234', $this->user->fresh()->withdrawal_pin));
        $this->postJson('/api/v1/auth/setup-withdrawal-pin', ['pin' => '5678', 'pin_confirmation' => '5678', 'password' => 'test-password'])->assertOk();
        $this->assertTrue(Hash::check('5678', $this->user->fresh()->withdrawal_pin));
    }
    public function test_wallet_preserves_kobo_above_binary_float_precision(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite stores large decimal values as floats; run this regression on MariaDB.');
        }
        $wallet = $this->business->wallet;
        $wallet->update(['available_balance' => '90071992547409.91', 'ledger_balance' => '90071992547409.91']);
        app(\App\Services\WalletService::class)->credit($this->merchant->id, $this->business->id, '0.01', 'test', 2);
        $this->assertSame('90071992547409.92', $wallet->fresh()->available_balance);
    }
}
