<?php
namespace App\Services\ProviderServices;
class NinePsbService extends BaseProviderService
{
    protected function defaultMetadata(): array
    {
        return [
            'auth' => ['scheme' => 'bearer'],
            'endpoints' => [
                'virtual_account_create' => '/api/v1/virtual-account',
                'transaction_verify' => '/api/v1/transactions/{reference}',
                'payout_create' => '/api/v1/transfers',
                'payout_verify' => '/api/v1/transfers/{reference}',
                'bank_verify' => '/api/v1/accounts/resolve',
                'health' => '/api/v1/health',
            ],
        ];
    }
}
