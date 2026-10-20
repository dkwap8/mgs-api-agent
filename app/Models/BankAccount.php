<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'merchant_id',
        'business_id',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'is_verified',
        'verified_at',
        'withdrawals_available_at',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_default' => 'boolean',
        'verified_at' => 'datetime',
        'withdrawals_available_at' => 'datetime',
    ];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function business() { return $this->belongsTo(Business::class); }
    public function payouts() { return $this->hasMany(Payout::class); }
}
