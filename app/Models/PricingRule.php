<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = ['name','type','min_amount','max_amount','fee_amount','fee_percentage','is_active'];
    protected $casts = ['min_amount'=>'decimal:2','max_amount'=>'decimal:2','fee_amount'=>'decimal:2','fee_percentage'=>'decimal:2','is_active'=>'boolean'];
}
