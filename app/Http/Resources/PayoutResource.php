<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'provider_reference' => $this->provider_reference,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'net_amount' => $this->net_amount,
            'currency' => $this->business?->currency ?? 'NGN',
            'narration' => $this->narration,
            'status' => $this->status,
            'processing_mode' => $this->processing_mode,
            'failure_reason' => $this->when($this->isFinal(), $this->failure_reason),
            'bank_account' => $this->whenLoaded('bankAccount', fn () => [
                'id' => $this->bankAccount?->id,
                'bank_name' => $this->bankAccount?->bank_name,
                'bank_code' => $this->bankAccount?->bank_code,
                'account_number' => $this->bankAccount?->account_number,
                'account_name' => $this->bankAccount?->account_name,
            ]),
            'approved_at' => $this->approved_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
