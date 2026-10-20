<?php

namespace Tests\Unit;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\VirtualAccountResource;
use App\Http\Resources\WalletResource;
use PHPUnit\Framework\TestCase;

class ResourceContractTest extends TestCase
{
    public function test_financial_resources_exist_and_use_the_canonical_resource_layer(): void
    {
        foreach ([
            CustomerResource::class,
            PayoutResource::class,
            TransactionResource::class,
            VirtualAccountResource::class,
            WalletResource::class,
        ] as $resource) {
            $this->assertTrue(class_exists($resource));
        }
    }
}
