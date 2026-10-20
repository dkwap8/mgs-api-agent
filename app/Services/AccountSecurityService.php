<?php

namespace App\Services;

use App\Models\Device;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountSecurityService
{
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;

    public function deviceFingerprint(Request $request): string
    {
        $provided = trim((string) $request->header('X-Device-ID'));
        if ($provided !== '') {
            return hash('sha256', $provided);
        }

        return hash('sha256', implode('|', [
            (string) $request->userAgent(),
            (string) $request->header('X-Device-Platform'),
        ]));
    }

    public function recordLogin(Request $request, ?User $user, string $status, ?string $reason = null): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'email' => strtolower(trim((string) $request->input('email'))),
            'ip_address' => $request->ip(),
            'device' => Str::limit((string) $request->userAgent(), 500, ''),
            'location' => null,
            'status' => $status,
            'metadata' => array_filter([
                'reason' => $reason,
                'device_id' => $this->deviceFingerprint($request),
                'platform' => $request->header('X-Device-Platform'),
            ], static fn ($value) => $value !== null && $value !== ''),
        ]);
    }

    public function registerDevice(Request $request, User $user): Device
    {
        $deviceId = $this->deviceFingerprint($request);

        return Device::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'device_name' => Str::limit((string) ($request->header('X-Device-Name') ?: $request->userAgent()), 255, ''),
                'ip_address' => $request->ip(),
                'platform' => Str::limit((string) $request->header('X-Device-Platform'), 100, ''),
                'last_seen_at' => now(),
            ]
        );
    }

    public function markFailedLogin(User $user): void
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $updates['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
            $updates['failed_login_attempts'] = 0;
        }

        $user->forceFill($updates)->save();
    }

    public function clearFailedLogins(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();
    }

    public function revokeOtherTokens(User $user, ?int $currentTokenId = null): int
    {
        $query = $user->tokens();
        if ($currentTokenId !== null) {
            $query->whereKeyNot($currentTokenId);
        }

        return $query->delete();
    }

    public function revokeAllSessions(User $user): void
    {
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /** @return array<int, string> */
    public function generateRecoveryCodes(User $user): array
    {
        $plain = collect(range(1, 8))
            ->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))
            ->all();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(array_map(
                static fn (string $code): string => Hash::make($code),
                $plain
            )),
        ])->save();

        return $plain;
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
        $user = User::lockForUpdate()->findOrFail($user->id);
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        try {
            $hashes = decrypt($user->two_factor_recovery_codes);
        } catch (\Throwable) {
            return false;
        }

        foreach ((array) $hashes as $index => $hash) {
            if (Hash::check(strtoupper(trim($code)), $hash)) {
                unset($hashes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(array_values($hashes)),
                ])->save();
                return true;
            }
        }

        return false;
        }, 3);
    }
}
