<?php

namespace Tests\Unit;

use App\Http\Middleware\ApiKeyAuth;
use App\Models\BankAccount;
use App\Models\Merchant;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use App\Services\WalletService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MultiBusinessFinancialIsolationContractTest extends TestCase
{
    public function test_wallet_operations_require_business_context(): void
    {
        foreach ([
            'createWallet',
            'credit',
            'debit',
            'reserveFunds',
            'captureReservedFunds',
            'releaseReservedFunds',
            'getWalletBalance',
            'getBalance',
            'getTransactions',
        ] as $method) {
            $parameters = (new ReflectionMethod(WalletService::class, $method))->getParameters();
            $this->assertSame('merchantId', $parameters[0]->getName());
            $this->assertSame('businessId', $parameters[1]->getName());
        }
    }

    public function test_repository_cannot_create_merchant_only_wallets(): void
    {
        $this->assertFalse(method_exists(WalletRepository::class, 'createForMerchant'));
        $this->assertTrue(method_exists(WalletRepository::class, 'createForBusiness'));
    }

    public function test_merchant_has_no_single_wallet_fallback_relation(): void
    {
        $this->assertFalse(method_exists(Merchant::class, 'wallet'));
        $this->assertTrue(method_exists(Merchant::class, 'wallets'));
    }

    public function test_financial_models_expose_business_ownership(): void
    {
        $this->assertContains('business_id', (new Wallet())->getFillable());
        $this->assertContains('business_id', (new BankAccount())->getFillable());
    }

    public function test_api_key_auth_has_no_business_fallback(): void
    {
        $source = file_get_contents((new ReflectionMethod(ApiKeyAuth::class, 'handle'))->getFileName());
        $this->assertStringNotContainsString('oldest(', $source);
        $this->assertStringContainsString('! $apiKey->business_id', $source);
    }
}
