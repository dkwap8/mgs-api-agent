<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerKYCVerification extends Model
{
    protected $table = 'customer_kyc_verifications';

    protected $fillable = [
        'customer_id',
        'type',
        'identity_number',
        'identity_hash',
        'reference',
        'provider_response',
        'status',
        'attempts',
        'next_retry_at',
        'processing_at',
        'verified_at',
        'failed_at',
        'last_error',
        'metadata',
    ];

    protected $hidden = ['identity_number', 'identity_hash', 'provider_response'];

    protected $casts = [
        'identity_number' => 'encrypted',
        'provider_response' => 'array',
        'metadata' => 'array',
        'next_retry_at' => 'datetime',
        'processing_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}