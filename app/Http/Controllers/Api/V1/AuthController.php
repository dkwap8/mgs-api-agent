<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\TotpService;
use App\Services\AccountSecurityService;
use App\Mail\PasswordResetMail;
use App\Models\Device;
use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Business;
use App\Models\LoginHistory;
use App\Models\Merchant;
use App\Models\RegistrationOtp;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ApiKey\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    protected function formatValidationErrors(array $errors): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Compatibility registration endpoint.
     *
     * Registration begins by sending an OTP. The user account is only
     * created after the OTP is verified successfully.
     */
    public function register(Request $request): JsonResponse
    {
        return $this->sendRegistrationOtp($request);
    }

    /**
     * Validate registration information and send an OTP to the email.
     */
    public function sendRegistrationOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'phone' => [
                    'required',
                    'digits:11',
                    'unique:users,phone',
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ], [
                'business_name.required' => 'Please enter your business name.',
                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',
                'phone.required' => 'Please enter your phone number.',
                'phone.digits' => 'Your phone number must contain exactly 11 digits.',
                'phone.unique' => 'This phone number is already registered.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email address is already registered.',
                'password.required' => 'Please enter a password.',
                'password.min' => 'Your password must contain at least 8 characters.',
                'password.confirmed' => 'The password confirmation does not match.',
            ]);
        } catch (ValidationException $exception) {
            return $this->formatValidationErrors($exception->errors());
        }

        $validated['business_name'] = trim($validated['business_name']);
        $validated['first_name'] = trim($validated['first_name']);
        $validated['last_name'] = trim($validated['last_name']);
        $validated['phone'] = trim($validated['phone']);
        $validated['email'] = Str::lower(trim($validated['email']));

        $otp = (string) random_int(100000, 999999);

        try {
            DB::transaction(function () use ($validated, $otp): void {
                RegistrationOtp::query()
                    ->where('email', $validated['email'])
                    ->whereNull('verified_at')
                    ->delete();

                RegistrationOtp::create([
                    'email' => $validated['email'],
                    'otp' => Hash::make($otp),
                    'payload' => $validated,
                    'attempts' => 0,
                    'resend_count' => 0,
                    'expires_at' => now()->addMinutes(10),
                ]);
            });

            Mail::raw(
                implode("\n", [
                    "Hello {$validated['first_name']},",
                    '',
                    "Your MGS API verification code is: {$otp}",
                    '',
                    'This code expires in 10 minutes.',
                    '',
                    'If you did not request this registration, please ignore this email.',
                ]),
                function ($message) use ($validated): void {
                    $message
                        ->to($validated['email'])
                        ->subject('Verify your MGS API account');
                }
            );

            return response()->json([
                'status' => 'success',
                'message' => 'A verification code has been sent to your email address.',
                'data' => [
                    'email' => $validated['email'],
                    'expires_in' => 600,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'We could not send your verification code. Please try again.',
            ], 500);
        }
    }

    /**
     * Resend a registration OTP.
     */
    public function resendRegistrationOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                ],
            ], [
                'email.required' => 'Your email address is required.',
                'email.email' => 'Please enter a valid email address.',
            ]);
        } catch (ValidationException $exception) {
            return $this->formatValidationErrors($exception->errors());
        }

        $email = Str::lower(trim($validated['email']));

        $registrationOtp = RegistrationOtp::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $registrationOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending registration was found for this email address.',
            ], 404);
        }

        if ($registrationOtp->resend_count >= 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have reached the maximum OTP resend limit. Please restart registration.',
            ], 429);
        }

        $payload = $registrationOtp->payload;

        if (! is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The pending registration data is invalid. Please restart registration.',
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            $registrationOtp->update([
                'otp' => Hash::make($otp),
                'resend_count' => $registrationOtp->resend_count + 1,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::raw(
                implode("\n", [
                    'Hello '.($payload['first_name'] ?? 'there').',',
                    '',
                    "Your new MGS API verification code is: {$otp}",
                    '',
                    'This code expires in 10 minutes.',
                ]),
                function ($message) use ($email): void {
                    $message
                        ->to($email)
                        ->subject('Your new MGS API verification code');
                }
            );

            return response()->json([
                'status' => 'success',
                'message' => 'A new verification code has been sent.',
                'data' => [
                    'email' => $email,
                    'expires_in' => 600,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'We could not resend the verification code. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify the OTP, complete registration and authenticate the new user.
     */
    public function verifyRegistrationOtp(Request $request, AccountSecurityService $security): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                ],
                'otp' => [
                    'required',
                    'digits:6',
                ],
            ], [
                'email.required' => 'Your email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'otp.required' => 'Please enter the verification code.',
                'otp.digits' => 'The verification code must contain exactly 6 digits.',
            ]);
        } catch (ValidationException $exception) {
            return $this->formatValidationErrors($exception->errors());
        }

        $email = Str::lower(trim($validated['email']));

        $registrationOtp = RegistrationOtp::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $registrationOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending registration was found. Please restart registration.',
            ], 404);
        }

        if ($registrationOtp->expires_at->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The verification code has expired. Please request a new code.',
            ], 422);
        }

        if ($registrationOtp->attempts >= 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many incorrect attempts. Please restart registration.',
            ], 429);
        }

        if (! Hash::check($validated['otp'], $registrationOtp->otp)) {
            $registrationOtp->increment('attempts');

            return response()->json([
                'status' => 'error',
                'message' => 'The verification code is incorrect.',
                'errors' => [
                    'otp' => [
                        'The verification code is incorrect.',
                    ],
                ],
            ], 422);
        }

        $payload = $registrationOtp->payload;

        if (! is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The pending registration information is invalid. Please restart registration.',
            ], 422);
        }

        if (User::query()->where('email', $payload['email'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already registered.',
            ], 422);
        }

        if (User::query()->where('phone', $payload['phone'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This phone number is already registered.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($payload, $registrationOtp): array {
                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => trim(
                        $payload['first_name'].' '.$payload['last_name']
                    ),
                    'email' => Str::lower(trim($payload['email'])),
                    'phone' => $payload['phone'],
                    'password' => Hash::make($payload['password']),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]);

                $merchant = Merchant::create([
                    'user_id' => $user->id,
                    'business_name' => trim($payload['business_name']),
                    'business_email' => Str::lower(trim($payload['email'])),
                    'business_phone' => $payload['phone'],
                    'verification_status' => Merchant::STATUS_PENDING,
                    'is_verified' => false,
                    'live_access' => false,
                    'verified_at' => null,
                ]);

                do {
                    $businessCode = 'MGS'.str_pad(
                        (string) random_int(1, 999999),
                        6,
                        '0',
                        STR_PAD_LEFT
                    );
                } while (
                    Business::query()
                        ->where('business_code', $businessCode)
                        ->exists()
                );

                $business = Business::create([
                    'merchant_id' => $merchant->id,
                    'name' => trim($payload['business_name']),
                    'business_code' => $businessCode,
                    'verification_status' => Business::STATUS_PENDING,
                    'is_verified' => false,
                    'verified_at' => null,
                    'activation_id' => null,
                ]);

                $user->forceFill([
                    'current_merchant_id' => $merchant->id,
                    'current_business_id' => $business->id,
                ])->save();

                $wallet = Wallet::create([
                    'merchant_id' => $merchant->id,
                    'business_id' => $business->id,
                    'currency' => 'NGN',
                    'available_balance' => 0,
                    'ledger_balance' => 0,
                    'reserved_balance' => 0,
                    'status' => 'active',
                ]);

                app(ApiKeyService::class)->generate(
                    $merchant->id,
                    $business->id,
                    ApiKey::ENVIRONMENT_TEST
                );

                $registrationOtp->update([
                    'verified_at' => now(),
                ]);

                return [
                    'user' => $user,
                    'merchant' => $merchant,
                    'business' => $business,
                    'wallet' => $wallet,
                ];
            });

            /** @var User $user */
            $user = $result['user'];

            $device = $security->registerDevice($request, $user);
            $expiresAt = now()->addHours(12);
            $token = $user->createToken(
                'device:'.$device->id,
                ['account:read', 'merchant:read', 'merchant:write', 'payments:write'],
                $expiresAt
            )->plainTextToken;
            $security->recordLogin($request, $user, 'success');

            return response()->json([
                'status' => 'success',
                'message' => 'Registration completed successfully.',
                'data' => [
                    'user' => $result['user']->only([
                        'id',
                        'uuid',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'email_verified_at',
                        'current_merchant_id',
                        'current_business_id',
                    ]),
                    'merchant' => $result['merchant'],
                    'business' => $result['business'],
                    'businesses' => [
                        $result['business'],
                    ],
                    'wallet' => $result['wallet'],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Account creation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Authenticate an existing user.
     */
    public function login(Request $request, AccountSecurityService $security): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'two_factor_code' => ['nullable', 'string'],
                'recovery_code' => ['nullable', 'string'],
            ]);

            $email = Str::lower(trim($validated['email']));
            $user = User::query()->where('email', $email)->first();

            if ($user?->locked_until?->isFuture()) {
                $security->recordLogin($request, $user, 'failed', 'account_locked');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many failed login attempts. Try again later.',
                    'retry_after' => now()->diffInSeconds($user->locked_until),
                ], 429);
            }

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                if ($user) {
                    $security->markFailedLogin($user);
                }
                $security->recordLogin($request, $user, 'failed', 'invalid_credentials');
                return response()->json(['status' => 'error', 'message' => 'Invalid email address or password.'], 401);
            }

            if ($user->status !== 'active') {
                $security->recordLogin($request, $user, 'failed', 'inactive_account');
                return response()->json(['status' => 'error', 'message' => 'Your account is not active.'], 403);
            }

            if ($user->two_factor_confirmed_at) {
                $validMfa = app(\App\Services\UserMfaService::class)->verify($user,
                    $validated['two_factor_code'] ?? null, $validated['recovery_code'] ?? null);
                if (! $validMfa) {
                    $security->recordLogin($request, $user, 'failed', 'two_factor_required');
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Two-factor authentication is required.',
                        'requires_two_factor' => true,
                    ], 422);
                }
            }

            $merchant = $user->resolveActiveMerchant();
            $business = $user->resolveActiveBusiness();
            if (! $merchant) {
                return response()->json(['status' => 'error', 'message' => 'Merchant account not found.'], 403);
            }

            $device = $security->registerDevice($request, $user);
            $expiresAt = now()->addHours(12);
            $token = $user->createToken(
                'device:'.$device->id,
                ['account:read', 'merchant:read', 'merchant:write', 'payments:write'],
                $expiresAt
            )->plainTextToken;

            app(\App\Services\NotificationService::class)->notifyUser($user->id,'login_alert','New sign-in','A sign-in to your MGS API account occurred from IP '.$request->ip().'. Review your devices if this was unexpected.');
            $security->clearFailedLogins($user);
            $security->recordLogin($request, $user, 'success');

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful.',
                'data' => [
                    'user' => $user->fresh(),
                    'merchant' => $merchant,
                    'business' => $business,
                    'businesses' => $merchant->businesses()->orderByDesc('id')->get(),
                    'device' => $device,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ]);
        } catch (ValidationException $exception) {
            return $this->formatValidationErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['status' => 'error', 'message' => 'Login failed. Please try again.'], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $merchant = $user->resolveActiveMerchant();
        $business = $user->resolveActiveBusiness();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'current_merchant' => $merchant,
                'current_business' => $business?->load([
                    'wallet',
                ]),
                'current_business_api_keys' => $business
                    ? $merchant?->apiKeys()
                        ->where('business_id', $business->id)
                        ->select('id', 'business_id', 'public_key', 'environment', 'status', 'last_used_at', 'revoked_at', 'created_at')
                        ->latest('id')
                        ->get()
                    : [],
                'businesses' => $merchant
                    ? $merchant->businesses()
                        ->orderByDesc('id')
                        ->get()
                    : [],
            ],
        ]);
    }

    public function changePassword(Request $request, AccountSecurityService $security): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'The current password is incorrect.'], 422);
        }

        $currentTokenId = $user->currentAccessToken()?->id ?? null;
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
        $security->revokeOtherTokens($user, $currentTokenId);
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Password changed and other sessions were signed out.']);
    }

    private function requireSecurityPassword(Request $request): void
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
            abort_unless($user->two_factor_pending_secret && app(\App\Services\UserMfaService::class)->consumeTotp($user, $request->input('code'), $user->two_factor_pending_secret), 422, 'Invalid authenticator code.');
            $user->forceFill(['two_factor_secret' => $user->two_factor_pending_secret, 'two_factor_pending_secret' => null, 'two_factor_confirmed_at' => now()])->save();
            app(AccountSecurityService::class)->revokeOtherTokens($user, $request->user()->currentAccessToken()?->id ?? null);
            return app(AccountSecurityService::class)->generateRecoveryCodes($user);
        });
        return response()->json(['status' => 'success', 'message' => 'Authenticator enabled. Save your recovery codes.', 'data' => ['recovery_codes' => $codes]])->header('Cache-Control', 'private, no-store');
    }

    public function setupWithdrawalPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => [
                'required',
                'digits_between:4,6',
                'confirmed',
            ],
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $request->user()->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => ['The current password is incorrect.']]);
        }

        $request->user()->update([
            'withdrawal_pin' => Hash::make($validated['pin']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Withdrawal PIN created successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            Mail::to($email)->queue(new PasswordResetMail($token, $email));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'If the account exists, password reset instructions have been sent.',
        ]);
    }

    public function resetPassword(Request $request, AccountSecurityService $security): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);
        $email = Str::lower(trim($validated['email']));
        return DB::transaction(function () use ($validated, $email, $security): JsonResponse {
            $record = DB::table('password_reset_tokens')->where('email', $email)->lockForUpdate()->first();
            $created = $record?->created_at ? \Illuminate\Support\Carbon::parse($record->created_at) : null;
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

    public function logoutAll(Request $request, AccountSecurityService $security): JsonResponse
    {
        $security->revokeAllSessions($request->user());
        return response()->json(['status' => 'success', 'message' => 'All sessions have been signed out.']);
    }

    public function devices(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $request->user()->devices()->latest('last_seen_at')->get()]);
    }

    public function revokeDevice(Request $request, Device $device): JsonResponse
    {
        abort_unless($device->user_id === $request->user()->id, 404);
        $device->update(['trusted' => false, 'revoked_at' => now()]);
        $request->user()->tokens()->where('name', 'device:'.$device->id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Device access revoked.']);
    }

    public function regenerateRecoveryCodes(Request $request, AccountSecurityService $security): JsonResponse
    {
        $this->requireSecurityPassword($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        $codes = DB::transaction(function () use ($request, $security): array {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->two_factor_confirmed_at && app(\App\Services\UserMfaService::class)->consumeTotp($user, $request->input('code')), 422, 'An unused authenticator code is required.');
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
            abort_unless(app(\App\Services\UserMfaService::class)->verify($user, $request->input('code'), $request->input('recovery_code')), 422, 'An unused authenticator or recovery code is required.');
            $user->forceFill(['two_factor_secret' => null, 'two_factor_pending_secret' => null, 'two_factor_last_counter' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
            app(AccountSecurityService::class)->revokeOtherTokens($user, $request->user()->currentAccessToken()?->id ?? null);
        }, 3);
        return response()->json(['status' => 'success', 'message' => 'Two-factor authentication disabled.']);
    }
}
