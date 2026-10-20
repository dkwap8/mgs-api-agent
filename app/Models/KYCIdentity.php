<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KYCIdentity extends Model
{
    protected $table = 'kyc_identities';

    protected $fillable = [
        'bvn_hash',
        'nin_hash',
        'verified_name',
        'date_of_birth',
        'verification_provider',
        'verification_reference',
        'status',
        'verified_at',
        'provider_response',
    ];

    protected $hidden = ['bvn_hash', 'nin_hash', 'provider_response', 'date_of_birth'];

    protected $casts = [
        'verified_at' => 'datetime',
        'provider_response' => 'array',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'kyc_identity_id');
    }
}