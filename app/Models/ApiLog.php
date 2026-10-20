<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = ['business_id','merchant_id','api_key_id','endpoint','method','status_code','request','response','ip_address','user_agent','duration'];
    protected $casts = ['request'=>'array','response'=>'array'];
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function apiKey() { return $this->belongsTo(ApiKey::class); }
}
