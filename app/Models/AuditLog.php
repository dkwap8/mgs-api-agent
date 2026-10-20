<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['admin_id', 'user_id','auditable_type','auditable_id','auditable','event','old_values','new_values','ip_address','user_agent'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
    public function auditable() { return $this->morphTo(); }
    public function admin() { return $this->belongsTo(Admin::class); }
}
