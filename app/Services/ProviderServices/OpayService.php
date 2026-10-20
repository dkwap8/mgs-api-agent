<?php
namespace App\Services\ProviderServices;
class OpayService extends BaseProviderService
{
    protected function defaultMetadata(): array
    {
        return [
            'auth' => ['scheme' => 'bearer'],
            'endpoints' => [
                'virtual_account_create' => '/api/v1/international/cashier/create',
                'transaction_verify' => '/api/v1/international/cashier/status',
                'payout_create' => '/api/v1/international/transfer',
                'payout_verify' => '/api/v1/international/transfer/status',
                'bank_verify' => '/api/v1/international/transfer/validate',
                'health' => '/',
            ],
        ];
    }
}
