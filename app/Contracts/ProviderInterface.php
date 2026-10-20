<?php
// app/Contracts/ProviderInterface.php

namespace App\Contracts;

use App\Models\Customer;
use App\Models\Payout;

interface ProviderInterface
{
    public function createVirtualAccount(Customer $customer, array $metadata = []);
    public function verifyTransaction($reference);
    public function sendPayout(Payout $payout);
    public function verifyPayout(Payout $payout);
    public function handleWebhook($payload, $signature);
    public function healthCheck();
    public function verifyBankAccount($accountNumber, $bankCode);
}