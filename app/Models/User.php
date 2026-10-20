<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'uuid', 'name', 'email', 'phone', 'password', 'status',
        'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at', 'withdrawal_pin', 'current_merchant_id',
        'current_business_id', 'failed_login_attempts', 'locked_until',
        'last_login_at', 'password_changed_at',
    ];

    protected $hidden = [
        'password', 'withdrawal_pin', 'two_factor_secret', 'two_factor_pending_secret', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'two_factor_secret' => \App\Casts\EncryptedTotpSecret::class,
        'two_factor_pending_secret' => 'encrypted',
        'two_factor_last_counter' => 'integer',
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->hasOne(Merchant::class);
    }

    /** @deprecated Kept temporarily for old code; a user owns one merchant account. */
    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    public function currentBusiness()
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }

    public function resolveActiveMerchant(): ?Merchant
    {
        return $this->merchant()->first();
    }

    public function resolveActiveBusiness(): ?Business
    {
        $merchant = $this->resolveActiveMerchant();

        if (! $merchant) {
            return null;
        }

        if ($this->current_business_id) {
            $business = $merchant->businesses()->find($this->current_business_id);
            if ($business) {
                return $business;
            }
        }

        $business = $merchant->businesses()->oldest('id')->first();

        if ($business) {
            $this->forceFill(['current_business_id' => $business->id])->save();
        }

        return $business;
    }

    public function switchBusiness(int $businessId): ?Business
    {
        $merchant = $this->resolveActiveMerchant();
        $business = $merchant?->businesses()->find($businessId);

        if (! $business) {
            return null;
        }

        $this->forceFill(['current_business_id' => $business->id])->save();

        return $business;
    }

    public function devices() { return $this->hasMany(Device::class); }
    public function loginHistories() { return $this->hasMany(LoginHistory::class); }
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }

    /**
     * Determine whether the user has at least one of the supplied roles.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $roles = array_values(array_unique(array_filter(
            array_map(
                static fn (string $role): string => trim($role),
                $roles
            ),
            static fn (string $role): bool => $role !== ''
        )));

        if ($roles === []) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                static fn (Role $role): bool => in_array(
                    $role->name,
                    $roles,
                    true
                )
            );
        }

        return $this->roles()
            ->whereIn('roles.name', $roles)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                static fn (Role $role): bool =>
                    $role->relationLoaded('permissions')
                        ? $role->permissions->contains('name', $permission)
                        : $role->permissions()
                            ->where('permissions.name', $permission)
                            ->exists()
            );
        }

        return $this->roles()
            ->whereHas(
                'permissions',
                fn ($query) => $query->where(
                    'permissions.name',
                    $permission
                )
            )
            ->exists();
    }
}
