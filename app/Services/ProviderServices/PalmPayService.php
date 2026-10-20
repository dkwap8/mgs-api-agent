<?php
namespace App\Services\ProviderServices;
class PalmPayService extends BaseProviderService
{
    protected function defaultMetadata(): array
    {
        return [
            'auth' => ['scheme' => 'api_key', 'api_key_header' => 'Authorization'],
            'endpoints' => [
                'virtual_account_create' => '/api/v1/virtual-accounts',
                'transaction_verify' => '/api/v1/transactions/{reference}',
                'payout_create' => '/api/v1/transfers',
                'payout_verify' => '/api/v1/transfers/{reference}',
                'bank_verify' => '/api/v1/banks/resolve',
                'health' => '/api/v1/health',
            ],
        ];
    }
}
