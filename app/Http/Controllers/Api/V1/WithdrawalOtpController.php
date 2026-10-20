<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Payout;
use App\Models\WithdrawalOtp;
use App\Services\PayoutService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WithdrawalOtpController extends Controller
{
    use ApiResponseTrait;

    private const EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const MAX_RESENDS = 3;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        protected PayoutService $payoutService
    ) {
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id' => [
                'required',
                'integer',
                'exists:bank_accounts,id',
            ],
            'amount' => [
                new \App\Rules\ExactAmount,
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
            'pin' => [
                'required',
                'digits_between:4,6',
            ],
            'narration' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $user = $request->user();
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        if (
            ! $user->withdrawal_pin ||
            ! Hash::check($validated['pin'], $user->withdrawal_pin)
        ) {
            throw ValidationException::withMessages([
                'pin' => ['The withdrawal PIN is incorrect.'],
            ]);
        }

        $bankAccount = BankAccount::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->where('id', $validated['bank_account_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($bankAccount->withdrawals_available_at?->isFuture()) throw ValidationException::withMessages(['bank_account_id'=>['This settlement account is under the 24-hour security hold.']]);

        $preview = $this->payoutService->preview(
            $merchant->id,
            $business->id,
            $validated['amount']
        );

        if (! $preview['has_sufficient_balance']) {
            throw ValidationException::withMessages([
                'amount' => [
                    sprintf(
                        'Insufficient balance. This withdrawal requires ₦%s including charges.',
                        number_format((float) $preview['wallet_debit'], 2)
                    ),
                ],
            ]);
        }

        $amount = \App\Support\Money::positive($validated['amount']);

        $existing = WithdrawalOtp::query()
            ->where('user_id', $user->id)
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->where('bank_account_id', $bankAccount->id)
            ->where('amount', $amount)
            ->where('status', WithdrawalOtp::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            $elapsed = $existing->last_sent_at->diffInSeconds(now());

            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;

                return $this->errorResponse(
                    "Please wait {$remaining} seconds before requesting another OTP.",
                    429
                );
            }

            if ($existing->resend_count >= self::MAX_RESENDS) {
                return $this->errorResponse(
                    'Maximum OTP resend limit reached. Please restart the withdrawal.',
                    429
                );
            }
        }

        $otp = (string) random_int(100000, 999999);

        $withdrawalOtp = DB::transaction(function () use (
            $existing,
            $otp,
            $user,
            $merchant,
            $business,
            $bankAccount,
            $validated,
            $preview,
            $amount
        ): WithdrawalOtp {
            WithdrawalOtp::query()
                ->where('user_id', $user->id)
                ->where('business_id', $business->id)
                ->where('status', WithdrawalOtp::STATUS_PENDING)
                ->when(
                    $existing,
                    fn ($query) => $query->whereKeyNot($existing->id)
                )
                ->update([
                    'status' => WithdrawalOtp::STATUS_EXPIRED,
                ]);

            $payload = [
                'narration' => $validated['narration'] ?? null,
                'preview' => $preview,
                'bank_name' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
                'account_name' => $bankAccount->account_name,
            ];

            if ($existing) {
                $existing->update([
                    'otp_hash' => Hash::make($otp),
                    'payload' => $payload,
                    'attempts' => 0,
                    'resend_count' => $existing->resend_count + 1,
                    'status' => WithdrawalOtp::STATUS_PENDING,
                    'last_sent_at' => now(),
                    'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
                    'verified_at' => null,
                    'used_at' => null,
                    'payout_id' => null,
                ]);

                return $existing->fresh();
            }

            return WithdrawalOtp::query()->create([
                'request_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'business_id' => $business->id,
                'bank_account_id' => $bankAccount->id,
                'amount' => $amount,
                'otp_hash' => Hash::make($otp),
                'payload' => $payload,
                'attempts' => 0,
                'resend_count' => 0,
                'status' => WithdrawalOtp::STATUS_PENDING,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            ]);
        });

        Mail::raw(
            $this->messageBody(
                $user->name,
                $otp,
                $withdrawalOtp
            ),
            function ($message) use ($user): void {
                $message
                    ->to($user->email)
                    ->subject('Confirm your MGS API withdrawal');
            }
        );

        return $this->successResponse([
            'request_id' => $withdrawalOtp->request_id,
            'email_hint' => $this->maskEmail($user->email),
            'expires_in_seconds' => self::EXPIRY_MINUTES * 60,
            'resend_after_seconds' => self::RESEND_COOLDOWN_SECONDS,
            'preview' => $preview,
        ], 'Withdrawal OTP sent successfully.');
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'uuid'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $claim = DB::transaction(function () use (
            $validated,
            $user,
            $merchant,
            $business
        ): array {
            $otpRequest = WithdrawalOtp::query()
                ->where('request_id', $validated['request_id'])
                ->where('user_id', $user->id)
                ->where('merchant_id', $merchant->id)
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $otpRequest->status === WithdrawalOtp::STATUS_USED &&
                $otpRequest->payout_id
            ) {
                return [
                    'state' => 'used',
                    'otp' => $otpRequest,
                ];
            }

            if ($otpRequest->isExpired()) {
                $otpRequest->update([
                    'status' => WithdrawalOtp::STATUS_EXPIRED,
                ]);

                return [
                    'state' => 'error',
                    'message' =>
                        'The withdrawal OTP has expired. Please request a new one.',
                ];
            }

            if (! in_array($otpRequest->status, [WithdrawalOtp::STATUS_PENDING, WithdrawalOtp::STATUS_PROCESSING], true)) {
                return [
                    'state' => 'error',
                    'message' => 'This withdrawal OTP cannot be used.',
                ];
            }

            if ($otpRequest->attempts >= self::MAX_ATTEMPTS) {
                $otpRequest->update([
                    'status' => WithdrawalOtp::STATUS_EXPIRED,
                ]);

                return [
                    'state' => 'error',
                    'message' =>
                        'Too many incorrect attempts. Please request a new OTP.',
                ];
            }

            if (! Hash::check($validated['otp'], $otpRequest->otp_hash)) {
                $otpRequest->increment('attempts');

                if (
                    $otpRequest->attempts >= self::MAX_ATTEMPTS
                ) {
                    $otpRequest->update([
                        'status' => WithdrawalOtp::STATUS_EXPIRED,
                    ]);
                }

                return [
                    'state' => 'error',
                    'message' => 'The withdrawal OTP is incorrect.',
                ];
            }

            $otpRequest->update([
                'status' => WithdrawalOtp::STATUS_PROCESSING,
                'verified_at' => now(),
            ]);

            return [
                'state' => 'claimed',
                'otp' => $otpRequest->fresh(),
            ];
        });

        if ($claim['state'] === 'error') {
            throw ValidationException::withMessages([
                'otp' => [$claim['message']],
            ]);
        }

        /** @var WithdrawalOtp $withdrawalOtp */
        $withdrawalOtp = $claim['otp'];

        if ($claim['state'] === 'used') {
            $payout = Payout::query()
                ->with([
                    'bankAccount',
                    'business',
                    'withdrawalSetting',
                ])
                ->findOrFail($withdrawalOtp->payout_id);

            return $this->successResponse(
                $payout,
                'Withdrawal was already submitted.'
            );
        }

        try {
            $payload = $withdrawalOtp->payload ?? [];

            $payout = $this->payoutService->requestPayout(
                $merchant->id,
                $business->id,
                $withdrawalOtp->bank_account_id,
                $withdrawalOtp->amount,
                null,
                [
                    'narration' => $payload['narration'] ?? null,
                    'requested_by_user_id' => $user->id,
                    'risk_review_reason' => app(\App\Services\PayoutRiskService::class)->reason($user,$request),
                    'withdrawal_otp_request_id' =>
                        $withdrawalOtp->request_id,
                ]
            );

            $withdrawalOtp->update([
                'status' => WithdrawalOtp::STATUS_USED,
                'payout_id' => $payout->id,
                'used_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'auditable_type' => Payout::class,
                'auditable_id' => $payout->id,
                'event' => 'payout_requested',
                'new_values' => $payout->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $message =
                $payout->processing_mode === Payout::MODE_AUTOMATIC
                    ? 'Withdrawal verified and submitted for automatic processing.'
                    : 'Withdrawal verified and submitted for administrator review.';

            return $this->successResponse(
                $payout,
                $message,
                201
            );
        } catch (Throwable $exception) {
            WithdrawalOtp::query()
                ->whereKey($withdrawalOtp->id)
                ->where('status', WithdrawalOtp::STATUS_PROCESSING)
                ->update([
                    'status' => WithdrawalOtp::STATUS_PENDING,
                ]);

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            return $this->errorResponse(
                'Unable to submit the withdrawal. Please retry with the same OTP request.',
                500
            );
        }
    }

    private function messageBody(
        string $name,
        string $otp,
        WithdrawalOtp $request
    ): string {
        $payload = $request->payload ?? [];

        return sprintf(
            "Hello %s,\n\nYour MGS API withdrawal verification code is: %s\n\nWithdrawal amount: ₦%s\nBank: %s\nAccount: %s\n\nThis code expires in %d minutes. Do not share this code with anyone.\n\nIf you did not request this withdrawal, secure your account immediately.",
            $name ?: 'Merchant',
            $otp,
            number_format((float) $request->amount, 2),
            $payload['bank_name'] ?? 'N/A',
            $this->maskAccountNumber(
                (string) ($payload['account_number'] ?? '')
            ),
            self::EXPIRY_MINUTES
        );
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(
            explode('@', $email, 2),
            2,
            ''
        );

        $visible = mb_substr(
            $local,
            0,
            min(2, mb_strlen($local))
        );

        return $visible
            . str_repeat(
                '*',
                max(mb_strlen($local) - mb_strlen($visible), 3)
            )
            . '@'
            . $domain;
    }

    private function maskAccountNumber(string $accountNumber): string
    {
        if (strlen($accountNumber) <= 4) {
            return $accountNumber;
        }

        return str_repeat('*', strlen($accountNumber) - 4)
            . substr($accountNumber, -4);
    }
}
