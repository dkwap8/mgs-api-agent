<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureActiveMerchant;
use App\Models\Business;
use App\Models\Wallet;
use App\Services\WalletService;
use Tests\TestCase;
use ReflectionMethod;

class BusinessWalletIsolationContractTest extends TestCase
{
    public function test_business_has_its_own_wallet_relationship(): void
    {
        $business = new Business();

        $this->assertTrue(method_exists($business, 'wallet'));
        $this->assertSame(Wallet::class, $business->wallet()->getRelated()::class);
    }

    public function test_wallet_service_requires_business_context_for_balance_and_mutations(): void
    {
        foreach ([
            'createWallet' => 3,
            'credit' => 7,
            'debit' => 7,
            'reserveFunds' => 6,
            'captureReservedFunds' => 5,
            'releaseReservedFunds' => 5,
            'getBalance' => 2,
            'getTransactions' => 3,
        ] as $method => $minimumParameters) {
            $reflection = new ReflectionMethod(WalletService::class, $method);
            $this->assertGreaterThanOrEqual($minimumParameters, $reflection->getNumberOfParameters());
            $this->assertSame('businessId', $reflection->getParameters()[1]->getName());
        }
    }

    public function test_business_context_middleware_supports_explicit_business_header(): void
    {
        $source = file_get_contents((new ReflectionMethod(EnsureActiveMerchant::class, 'handle'))->getFileName());

        $this->assertStringContainsString('X-Business-ID', $source);
        $this->assertStringContainsString('BUSINESS_ACCESS_DENIED', $source);
    }
}
