<?php

namespace App\Services\ProviderServices;

use App\Models\Customer;
use Illuminate\Support\Str;

class LocalVirtualAccountService extends BaseProviderService
{
    public function createVirtualAccount(
        Customer $customer,
        array $metadata = []
    ): array {
        if (app()->environment('production')) {
            throw new \RuntimeException('The local test provider cannot create production accounts.');
        }
        return [
            'provider_customer_id' =>
                'LOCAL-' . Str::upper(Str::random(12)),

            'account_number' =>
                '9' . random_int(100000000, 999999999),

            'account_name' =>
                $customer->fullName(),

            'bank_name' =>
                'MGS Test Bank',

            'bank_code' =>
                '999',

            'expires_at' =>
                null,

            'metadata' =>
                $metadata,
        ];
    }

    public function healthCheck(): array
    {
        return [
            'status' => 'healthy',
            'message' => 'Local test provider is available.',
        ];
    }
}