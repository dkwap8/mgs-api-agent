<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VirtualAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'status' => $this->status,
            'is_test' => (bool) $this->is_test,
            'expires_at' => $this->expires_at?->toISOString(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'external_reference' => $this->customer?->external_reference,
                'name' => $this->customer?->fullName(),
                'email' => $this->customer?->email,
                'phone' => $this->customer?->phone,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'slug' => $this->provider?->slug,
            ]),
            'business' => $this->whenLoaded('business', fn () => ['id'=>$this->business?->id,'name'=>$this->business?->name]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
