<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                ],
                'password' => [
                    'required',
                    'string',
                ],
                'two_factor_code' => ['nullable', 'digits:6'],
                'recovery_code' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        $email = Str::lower(trim($validated['email']));

        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (
            ! $admin ||
            ! Hash::check($validated['password'], $admin->password)
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid administrator email address or password.',
            ], 401);
        }

        if (! $admin->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This administrator account is not active.',
            ], 403);
        }

        if (! $admin->hasAnyRole([
            'admin',
            'super_admin',
            'administrator',
            'support_admin',
            'finance_admin',
            'compliance_admin',
        ])) {
            return response()->json([
                'status' => 'error',
                'message' => 'This account does not have administrator access.',
            ], 403);
        }

        if ($admin->two_factor_confirmed_at && ! app(\App\Services\AdminMfaService::class)->verify($admin, $validated['two_factor_code'] ?? null, $validated['recovery_code'] ?? null)) {
            return response()->json(['success' => false, 'message' => 'An unused authenticator or recovery code is required.', 'requires_two_factor' => true], 422);
        }

        $enrollmentRequired = config('admin_security.require_mfa') && ! $admin->two_factor_confirmed_at;
        $admin->tokens()->delete();

        $expiresAt = now()->addHours(12);

        $token = $admin->createToken(
            'admin-session',
            $enrollmentRequired ? ['admin:mfa-enroll'] : [
                'admin:access',
                'admin:read',
                'admin:write',
            ],
            $expiresAt
        )->plainTextToken;

        $admin->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Administrator login successful.',
            'data' => [
                'admin' => $admin->fresh(),
                'account_type' => 'admin',
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toISOString(),
                'requires_mfa_setup' => $enrollmentRequired,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = $request->user();

        if (! $admin instanceof Admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Administrator authentication is required.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => $admin,
                'account_type' => 'admin',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Administrator logged out successfully.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All administrator sessions have been logged out.',
        ]);
    }
}
