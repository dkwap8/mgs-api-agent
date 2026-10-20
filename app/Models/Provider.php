<?php

namespace App\Models;

use App\Services\ProviderManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Provider extends Model
{
    public const TYPE_VIRTUAL_ACCOUNT = 'virtual_account';
    public const TYPE_PAYOUT = 'payout';
    public const HEALTH_HEALTHY = 'healthy';
    public const HEALTH_DEGRADED = 'degraded';
    public const HEALTH_UNHEALTHY = 'unhealthy';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'priority',
        'config',
        'metadata',
        'last_health_check',
        'health_status',
    ];

    protected $hidden = ['config', 'metadata'];

    protected $casts = [
        'config' => \App\Casts\EncryptedProviderConfig::class,
        'metadata' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'last_health_check' => 'datetime',
    ];

    protected static function booted(): void
    {
        $clearCache = static function (): void {
            app(ProviderManager::class)->forgetCache();
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function fundingCharge(): HasOne
    {
        return $this->hasOne(ProviderFundingCharge::class);
    }

    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(ProviderHealthCheck::class);
    }

    public function operationAttempts(): HasMany
    {
        return $this->hasMany(ProviderOperationAttempt::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ProviderIncident::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('health_status', self::HEALTH_HEALTHY);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->whereIn('health_status', [
            self::HEALTH_HEALTHY,
            self::HEALTH_DEGRADED,
        ]);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isHealthy(): bool
    {
        return $this->health_status === self::HEALTH_HEALTHY;
    }

    public function supports(string $capability): bool
    {
        return (bool) data_get($this->metadata ?? [], 'capabilities.' . $capability, data_get($this->metadata ?? [], $capability, false));
    }
}
