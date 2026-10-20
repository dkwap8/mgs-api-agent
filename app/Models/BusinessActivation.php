<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessActivation extends Model
{
    protected $fillable = [
        'merchant_id',
        'business_id',
        'user_id',
        'owner_customer_id',
        'business_type',
        'status',
        'kyc_status',
        'kyc_verified_at',
        'kyc_failure_reason',
        'business_name',
        'first_name',
        'last_name',
        'middle_name',
        'business_email',
        'business_phone',
        'website_url',
        'business_category',
        'business_description',
        'cac_number',
        'cac_certificate',
        'cac_status_report',
        'country',
        'state',
        'city',
        'street_address',
        'landmark',
        'proof_of_address',
        'nin',
        'nin_hash',
        'bvn',
        'bvn_hash',
        'bank_name',
        'account_number',
        'account_name',
        'accept_terms',
        'approved_at',
        'approved_by',
        'approved_by_admin_id',
        'rejected_by_admin_id',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $hidden = [
        'nin', 'bvn', 'nin_hash', 'bvn_hash',
    ];

    protected $casts = [
        'nin' => 'encrypted',
        'bvn' => 'encrypted',
        'accept_terms' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
    ];

    public function ownerCustomer()
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}