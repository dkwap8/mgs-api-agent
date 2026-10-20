<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Revenue extends Model
{
    protected $fillable = ['transaction_id', 'merchant_id', 'business_id', 'fee', 'provider_cost', 'currency'];
    protected $casts = ['fee' => 'decimal:2', 'provider_cost' => 'decimal:2'];
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
