<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'currency' => $this->currency,
            'available_balance' => $this->available_balance,
            'ledger_balance' => $this->ledger_balance,
            'reserved_balance' => $this->reserved_balance,
            'status' => $this->status,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
