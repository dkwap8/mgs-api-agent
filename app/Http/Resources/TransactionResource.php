<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'provider_reference' => $this->provider_reference,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => (string) $this->amount,
            'fee' => (string) $this->fee,
            'merchant_amount' => (string) $this->merchant_amount,
            'description' => $this->description,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business?->id,
                'name' => $this->business?->business_name,
            ]),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => trim(($this->customer->first_name ?? '').' '.($this->customer->last_name ?? '')),
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ] : null),
            'provider' => $this->whenLoaded('provider', fn () => $this->provider ? [
                'id' => $this->provider->id,
                'name' => $this->provider->name,
                'slug' => $this->provider->slug,
            ] : null),
            'virtual_account' => $this->whenLoaded('virtualAccount', fn () => $this->virtualAccount ? [
                'id' => $this->virtualAccount->id,
                'account_name' => $this->virtualAccount->account_name,
                'account_number' => $this->virtualAccount->account_number,
                'bank_name' => $this->virtualAccount->bank_name,
            ] : null),
            'metadata' => $this->when($request->user()?->hasAnyRole(['super_admin', 'admin', 'finance', 'support', 'compliance']), $this->metadata),
        ];
    }
}
