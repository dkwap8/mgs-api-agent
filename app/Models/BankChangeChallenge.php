<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankChangeChallenge extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['otp_hash'];
    protected $casts = ['expires_at'=>'datetime','consumed_at'=>'datetime'];
}
