<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\ProviderFundingCharge;
use App\Models\MerchantWithdrawalSetting;
use App\Services\FundingChargeService;
use App\Support\Money;
use Tests\TestCase;

class ExactMoneyTest extends TestCase
{
    public function test_webhook_json_preserves_large_decimal_amount_and_zero_fee_as_numbers(): void
    {
        $json = \App\Support\Money::jsonDecimals(['amount' => '9999999999999999.99', 'fee' => '0.00', 'merchant_amount' => '9999999999999999.99'], ['amount', 'fee', 'merchant_amount']);
        $this->assertStringContainsString('"amount":9999999999999999.99', $json);
        $this->assertStringContainsString('"fee":0.00', $json);
        $this->assertStringNotContainsString('__MGS', $json);
    }
    public function test_percentage_rounding_caps_and_large_values_preserve_kobo(): void
    {
        $this->assertSame('1.01', Money::fee('100.50', 'percentage', '1.0000', 0, null, null));
        $this->assertSame('10.00', Money::fee('10000.00', 'percentage', '1.0000', 0, null, '10.00'));
        $this->assertSame('5.00', Money::fee('1.00', 'percentage', '1.0000', 0, '5.00', '10.00'));
        $this->assertSame('123456789012.35', Money::fee('12345678901234.56', 'percentage', '1.0000', 0, null, null));
        $provider = new Provider(['name' => 'Test']);
        $provider->setRelation('fundingCharge', new ProviderFundingCharge(['is_enabled' => true, 'charge_type' => 'fixed', 'fixed_amount' => '0.01', 'collection_mode' => 'deduct']));
        $charge = (new FundingChargeService)->calculate($provider, '12345678901234.56');
        $this->assertSame('12345678901234.55', $charge['wallet_credit']);
    }

    public function test_withdrawal_manual_threshold_has_exact_boundary(): void
    {
        $setting = new MerchantWithdrawalSetting(['automatic_withdrawals_enabled' => true, 'automatic_withdrawal_limit' => '12345678901234.56']);
        $this->assertFalse($setting->requiresManualReview('12345678901234.56'));
        $this->assertTrue($setting->requiresManualReview('12345678901234.57'));
    }

    public function test_fractional_kobo_is_rejected_instead_of_rounded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::positive('10.001');
    }
}
