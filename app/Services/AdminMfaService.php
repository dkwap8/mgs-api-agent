<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminMfaService
{
    public function verify(Admin $admin, ?string $code, ?string $recovery = null): bool
    {
        return DB::transaction(function () use ($admin, $code, $recovery): bool {
            $locked = Admin::lockForUpdate()->findOrFail($admin->id);
            if (! $locked->two_factor_confirmed_at) return false;
            if ($code && $this->consumeTotp($locked, $code)) return true;
            if (! $recovery) return false;
            $codes = $locked->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check(strtoupper(trim($recovery)), $hash)) {
                    unset($codes[$index]);
                    $locked->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    return true;
                }
            }
            return false;
        }, 3);
    }

    public function consumeTotp(Admin $admin, string $code, ?string $secret = null): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) return false;
        $totp = app(TotpService::class);
        $counter = intdiv(time(), 30);
        foreach ([$counter, $counter - 1, $counter + 1] as $candidate) {
            if ($candidate <= ($admin->two_factor_last_counter ?? -1)) continue;
            if (hash_equals($totp->code($secret ?? $admin->two_factor_secret, $candidate), $code)) {
                $admin->forceFill(['two_factor_last_counter' => $candidate])->save();
                return true;
            }
        }
        return false;
    }

    public function recoveryCodes(Admin $admin): array
    {
        $codes = array_map(fn () => strtoupper(Str::random(6).'-'.Str::random(6)), range(1, 8));
        $admin->forceFill(['two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $codes)])->save();
        return $codes;
    }
}
