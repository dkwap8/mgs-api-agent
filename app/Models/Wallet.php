<?php
// app/Models/Wallet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'merchant_id',
        'business_id',
        'currency',
        'available_balance',
        'ledger_balance',
        'reserved_balance',
        'status'
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'ledger_balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2'
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_FROZEN = 'frozen';
    const STATUS_SUSPENDED = 'suspended';

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasSufficientBalance($amount): bool
    {
        return $this->available_balance >= $amount;
    }
}