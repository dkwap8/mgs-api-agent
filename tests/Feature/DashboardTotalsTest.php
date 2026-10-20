<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Services\WalletService;
use Tests\Support\PaymentTestCase;

class DashboardTotalsTest extends PaymentTestCase
{
    public function test_admin_summary_sums_all_business_wallets_and_counts_all_records(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $business = Business::create(['merchant_id' => $this->merchant->id, 'name' => 'Extra '.$i, 'business_code' => 'EXTRA-'.$i, 'verification_status' => 'pending']);
            app(WalletService::class)->createWallet($this->merchant->id, $business->id);
            app(WalletService::class)->credit($this->merchant->id, $business->id, '10.01', 'fixture', $i + 2);
        }
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/summary')->assertOk()
            ->assertJsonPath('data.total_businesses', 16)
            ->assertJsonPath('data.total_wallet_balance', '1150.15')
            ->assertJsonPath('data.health_status', 'not_monitored');
    }

    public function test_user_balance_does_not_include_another_business_wallet(): void
    {
        $business = Business::create(['merchant_id' => $this->merchant->id, 'name' => 'Other', 'business_code' => 'OTHER', 'verification_status' => 'pending']);
        app(WalletService::class)->createWallet($this->merchant->id, $business->id);
        app(WalletService::class)->credit($this->merchant->id, $business->id, '50.00', 'fixture', 2);
        $this->actingAs($this->user)->withHeader('X-Business-ID', $business->id)->getJson('/api/v1/wallet/balance')->assertOk()->assertJsonPath('data.available_balance', '50.00');
        $this->withHeader('X-Business-ID', $this->business->id)->getJson('/api/v1/wallet/balance')->assertOk()->assertJsonPath('data.available_balance', '1000.00');
    }
}
