from pathlib import Path
root = Path(__file__).resolve().parents[1]
path = root / 'app/Http/Controllers/Api/V1/AuthController.php'
source = path.read_text(encoding='utf-8')
start = source.index('                $validTotp = ')
end = source.index('                    $security->recordLogin', start)
source = source[:start] + '''                $validMfa = app(\\App\\Services\\UserMfaService::class)->verify($user,
                    $validated['two_factor_code'] ?? null, $validated['recovery_code'] ?? null);
                if (! $validMfa) {
''' + source[end:]
start = source.index('    public function setupTwoFactor(')
end = source.index('    public function setupWithdrawalPin(', start)
source = source[:start] + '''    private function requireSecurityPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => ['The current password is incorrect.']]);
        }
    }

    public function setupTwoFactor(Request $request, TotpService $totp): JsonResponse
    {
        $this->requireSecurityPassword($request);
        $secret = DB::transaction(function () use ($request, $totp): string {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_if($user->two_factor_confirmed_at, 422, 'Two-factor authentication is already enabled.');
            $secret = $totp->generateSecret();
            $user->forceFill(['two_factor_pending_secret' => $secret, 'two_factor_last_counter' => null])->save();
            return $secret;
        });
        return response()->json(['status' => 'success', 'data' => [
            'secret' => $secret, 'qr_code' => 'otpauth://totp/'.rawurlencode('MGSAPI:'.$request->user()->email).'?secret='.$secret.'&issuer=MGSAPI',
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function verifyTwoFactor(Request $request, TotpService $totp): JsonResponse
    {
        $this->requireSecurityPassword($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        $codes = DB::transaction(function () use ($request): array {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_if($user->two_factor_confirmed_at, 422, 'Two-factor authentication is already enabled.');
            abort_unless($user->two_factor_pending_secret && app(\\App\\Services\\UserMfaService::class)->consumeTotp($user, $request->input('code'), $user->two_factor_pending_secret), 422, 'Invalid authenticator code.');
            $user->forceFill(['two_factor_secret' => $user->two_factor_pending_secret, 'two_factor_pending_secret' => null, 'two_factor_confirmed_at' => now()])->save();
            app(AccountSecurityService::class)->revokeOtherTokens($user, $request->user()->currentAccessToken()?->id);
            return app(AccountSecurityService::class)->generateRecoveryCodes($user);
        });
        return response()->json(['status' => 'success', 'message' => 'Authenticator enabled. Save your recovery codes.', 'data' => ['recovery_codes' => $codes]])->header('Cache-Control', 'private, no-store');
    }

''' + source[end:]
start = source.index('        $record = DB::table(\'password_reset_tokens\')', source.index('    public function resetPassword('))
end = source.index('\n    public function logoutAll(', start)
source = source[:start] + '''        return DB::transaction(function () use ($validated, $email, $security): JsonResponse {
            $record = DB::table('password_reset_tokens')->where('email', $email)->lockForUpdate()->first();
            $created = $record?->created_at ? \\Illuminate\\Support\\Carbon::parse($record->created_at) : null;
            if (! $created || $created->isFuture() || $created->copy()->addMinutes(30)->isPast() || ! Hash::check($validated['token'], $record->token)) {
                return response()->json(['status' => 'error', 'message' => 'The password reset token is invalid or expired.'], 422);
            }
            $user = User::where('email', $email)->lockForUpdate()->firstOrFail();
            $user->forceFill(['password' => Hash::make($validated['password']), 'password_changed_at' => now(),
                'remember_token' => Str::random(60), 'failed_login_attempts' => 0, 'locked_until' => null])->save();
            $security->revokeAllSessions($user);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json(['status' => 'success', 'message' => 'Password reset successfully. Please sign in again.']);
        }, 3);
    }
''' + source[end:]
start = source.index('    public function regenerateRecoveryCodes(')
source = source[:start] + '''    public function regenerateRecoveryCodes(Request $request, AccountSecurityService $security): JsonResponse
    {
        $this->requireSecurityPassword($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        $codes = DB::transaction(function () use ($request, $security): array {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->two_factor_confirmed_at && app(\\App\\Services\\UserMfaService::class)->consumeTotp($user, $request->input('code')), 422, 'An unused authenticator code is required.');
            return $security->generateRecoveryCodes($user);
        });
        return response()->json(['status' => 'success', 'data' => ['recovery_codes' => $codes]])->header('Cache-Control', 'private, no-store');
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $this->requireSecurityPassword($request);
        $request->validate(['code' => ['nullable', 'digits:6'], 'recovery_code' => ['nullable', 'string']]);
        DB::transaction(function () use ($request): void {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless(app(\\App\\Services\\UserMfaService::class)->verify($user, $request->input('code'), $request->input('recovery_code')), 422, 'An unused authenticator or recovery code is required.');
            $user->forceFill(['two_factor_secret' => null, 'two_factor_pending_secret' => null, 'two_factor_last_counter' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
            app(AccountSecurityService::class)->revokeOtherTokens($user, $request->user()->currentAccessToken()?->id);
        }, 3);
        return response()->json(['status' => 'success', 'message' => 'Two-factor authentication disabled.']);
    }
}
'''
path.write_text(source, encoding='utf-8')
