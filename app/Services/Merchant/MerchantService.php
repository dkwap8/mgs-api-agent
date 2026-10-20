<?php

namespace App\Services\Merchant;

use App\Models\Merchant;
use App\Repositories\MerchantRepository;

class MerchantService
{
    public function __construct(private MerchantRepository $merchantRepository) {}

    public function approve(Merchant $merchant): Merchant
    {
        return $this->merchantRepository->approve($merchant);
    }

    public function reject(Merchant $merchant): Merchant
    {
        return $this->merchantRepository->reject($merchant);
    }
}
