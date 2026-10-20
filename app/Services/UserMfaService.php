<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserMfaService
{
    public function verify(User $user, ?string $code, ?string $recovery = null): bool
    {
        return DB::transaction(function () use ($user, $code, $recovery): bool {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            if (! $locked->two_factor_confirmed_at) return false;
            if ($code && $this->consumeTotp($locked, $code)) return true;
            return $recovery ? app(AccountSecurityService::class)->consumeRecoveryCode($locked, $recovery) : false;
        }, 3);
    }
    // Caller must hold the user row lock throughout this operation.
    public function consumeTotp(User $user, string $code, ?string $secret = null): bool
    {
        $secret ??= $user->two_factor_secret;
        if (! $secret || ! preg_match('/^\d{6}$/', $code)) return false;
        $counter = intdiv(time(), 30);
        foreach ([$counter, $counter - 1, $counter + 1] as $candidate) {
            if ($candidate <= ($user->two_factor_last_counter ?? -1)) continue;
            if (hash_equals(app(TotpService::class)->code($secret, $candidate), $code)) {
                $user->forceFill(['two_factor_last_counter' => $candidate])->save();
                return true;
            }
        }
        return false;
    }
}
