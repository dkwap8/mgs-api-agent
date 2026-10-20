<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\VirtualAccount;
use PHPUnit\Framework\TestCase;

class SensitiveSerializationContractTest extends TestCase
{
    public function test_sensitive_provider_fields_are_hidden_by_default(): void
    {
        $this->assertContains('provider_response', (new Payout())->getHidden());
        $this->assertContains('metadata', (new Payout())->getHidden());
        $this->assertContains('bvn_hash', (new \App\Models\KYCIdentity())->getHidden());
        $this->assertContains('nin_hash', (new \App\Models\KYCIdentity())->getHidden());
        $this->assertContains('provider_response', (new \App\Models\CustomerKYCVerification())->getHidden());
        $this->assertContains('provider_response', (new Transaction())->getHidden());
        $this->assertContains('metadata', (new Transaction())->getHidden());
        $this->assertContains('metadata', (new Customer())->getHidden());
        $this->assertContains('metadata', (new VirtualAccount())->getHidden());
        $this->assertContains('provider_customer_id', (new VirtualAccount())->getHidden());
    }
}
