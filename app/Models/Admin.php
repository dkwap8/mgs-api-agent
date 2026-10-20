<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret', 'two_factor_pending_secret', 'two_factor_recovery_codes', 'two_factor_last_counter',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_pending_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_counter' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return strtolower((string) $this->role) === 'super_admin';
    }

    public function hasRole(string $role): bool
    {
        return strtolower((string) $this->role)
            === strtolower(trim($role));
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if ($this->hasRole((string) $role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isSuperAdmin() || Role::query()
            ->where('name', strtolower((string) $this->role))
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->hasPermission($permission);
    }
}
