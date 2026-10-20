<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminMfaService;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminMfaController extends Controller
{
    private function password(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => ['The password is incorrect.']]);
        }
    }

    public function setup(Request $request, TotpService $totp)
    {
        $this->password($request);
        $secret = DB::transaction(function () use ($request, $totp) {
            $admin = Admin::lockForUpdate()->findOrFail($request->user()->id);
            abort_if($admin->two_factor_confirmed_at, 422, 'MFA is already enabled.');
            $secret = $totp->generateSecret();
            $admin->forceFill(['two_factor_pending_secret' => $secret])->save();
            return $secret;
        });
        $uri = 'otpauth://totp/'.rawurlencode('MGS API:'.$request->user()->email).'?secret='.$secret.'&issuer=MGS%20API';
        return response()->json(['success' => true, 'data' => ['secret' => $secret, 'otpauth_uri' => $uri]], 200, ['Cache-Control' => 'no-store']);
    }

    public function confirm(Request $request, AdminMfaService $mfa)
    {
        $this->password($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        $codes = DB::transaction(function () use ($request, $mfa) {
            $admin = Admin::lockForUpdate()->findOrFail($request->user()->id);
            abort_if($admin->two_factor_confirmed_at || ! $admin->two_factor_pending_secret, 422, 'Start MFA setup first.');
            if (! $mfa->consumeTotp($admin, $request->input('code'), $admin->two_factor_pending_secret)) {
                throw ValidationException::withMessages(['code' => ['The authenticator code is invalid.']]);
            }
            $admin->forceFill(['two_factor_secret' => $admin->two_factor_pending_secret, 'two_factor_pending_secret' => null, 'two_factor_confirmed_at' => now()])->save();
            $codes = $mfa->recoveryCodes($admin);
            $admin->tokens()->delete();
            app(\App\Services\AuditService::class)->log(null, 'admin.mfa.enabled', $admin, null, ['enabled' => true]);
            return $codes;
        });
        return response()->json(['success' => true, 'message' => 'MFA enabled. Save the recovery codes and sign in again with a new authenticator code.', 'data' => ['recovery_codes' => $codes]], 200, ['Cache-Control' => 'no-store']);
    }

    public function regenerate(Request $request, AdminMfaService $mfa)
    {
        $this->password($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        abort_unless($mfa->verify($request->user(), $request->input('code')), 422, 'The authenticator code is invalid or already used.');
        app(\App\Services\AuditService::class)->log(null, 'admin.mfa.recovery_codes_replaced', $request->user());
        return response()->json(['success' => true, 'data' => ['recovery_codes' => $mfa->recoveryCodes($request->user())]], 200, ['Cache-Control' => 'no-store']);
    }
}
