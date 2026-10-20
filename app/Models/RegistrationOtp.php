<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RegistrationOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'payload',
        'attempts',
        'resend_count',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP has already been verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark OTP as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified_at' => now(),
        ]);
    }

    /**
     * Increase verification attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Increase resend count.
     */
    public function incrementResend(): void
    {
        $this->increment('resend_count');
    }

    /**
     * Scope active OTPs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now());
    }
}