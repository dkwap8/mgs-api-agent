<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Payout;
use App\Services\PayoutService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayoutController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected PayoutService $payoutService
    ) {
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => [
                new \App\Rules\ExactAmount,
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
        ]);

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        try {
            $preview = $this->payoutService->preview(
                $merchant->id,
                $business->id,
                $validated['amount']
            );

            return $this->successResponse(
                $preview,
                'Withdrawal preview calculated successfully.'
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                $exception->getMessage(),
                422
            );
        }
    }

    public function requestPayout(Request $request): JsonResponse
    {
        // Backwards-compatible entry point: initiation now sends an OTP, never money.
        return app(WithdrawalOtpController::class)->send($request);
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $payouts = Payout::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->with([
                'bankAccount',
                'withdrawalSetting',
            ])
            ->latest('id')
            ->paginate(
                min(
                    max(
                        (int) $request->input('per_page', 20),
                        5
                    ),
                    100
                )
            );

        return $this->paginatedResponse($payouts, PayoutResource::class);
    }

    public function show(
        string $reference,
        Request $request
    ): JsonResponse {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $payout = Payout::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->where('reference', $reference)
            ->with([
                'bankAccount',
                'withdrawalSetting',
            ])
            ->firstOrFail();

        return $this->successResponse(new PayoutResource($payout));
    }

    public function bankAccounts(Request $request): JsonResponse
    {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $accounts = BankAccount::query()
            ->where('merchant_id', $merchant->id)
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->get();

        return $this->successResponse($accounts);
    }

    public function addBankAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_reference'=>['required','uuid'], 'otp'=>['required','digits:6'],
            'bank_name' => [
                'required',
                'string',
                'max:255',
            ],
            'bank_code' => [
                'required',
                'string',
                'size:3',
            ],
            'account_number' => [
                'required',
                'digits:10',
            ],
            'account_name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $merchantId = $merchant->id;
        $businessId = $business->id;

        $duplicate = BankAccount::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('account_number', $validated['account_number'])
            ->where('bank_code', $validated['bank_code'])
            ->first();

        if ($duplicate && $duplicate->is_verified) {
            return $this->errorResponse(
                'Bank account already exists.',
                422
            );
        }

        app(\App\Services\BankChangeService::class)->consume($request->user(), $businessId, $validated['challenge_reference'], $validated['otp'], $validated['bank_code'], $validated['account_number']);

        // Never trust a name supplied by the browser as evidence of bank verification.
        $verification = $this->payoutService->verifyBankAccount($validated['account_number'], $validated['bank_code']);
        if (! in_array(strtolower((string) ($verification['status'] ?? '')), ['success', 'successful', 'verified'], true)
            || trim((string) ($verification['account_name'] ?? '')) === '') {
            throw ValidationException::withMessages(['account_number' => ['The settlement account could not be verified by the provider.']]);
        }

        $account = BankAccount::query()->updateOrCreate([
            'merchant_id' => $merchantId, 'business_id' => $businessId,
            'bank_code' => $validated['bank_code'], 'account_number' => $validated['account_number'],
        ], [
            'merchant_id' => $merchantId,
            'business_id' => $businessId,
            'bank_name' => $validated['bank_name'],
            'bank_code' => $validated['bank_code'],
            'account_number' => $validated['account_number'],
            'account_name' => trim($verification['account_name']),
            'is_verified' => true,
            'verified_at' => now(),
            'withdrawals_available_at' => now()->addHours(24),
            'is_default' =>
                ! BankAccount::query()
                    ->where('merchant_id', $merchantId)
                    ->where('business_id', $businessId)
                    ->exists(),
            'status' => 'active',
        ]);

        app(\App\Services\AuditService::class)->log($request->user()->id, 'bank_account.added', $account, null, ['bank_code'=>$account->bank_code,'account_last_four'=>substr($account->account_number,-4),'withdrawals_available_at'=>$account->withdrawals_available_at->toIso8601String()]);
        app(\App\Services\NotificationService::class)->notifyUser($request->user()->id, 'bank_account_changed', 'Settlement bank account added', 'A settlement account ending '.substr($account->account_number,-4).' was added. Withdrawals to this account are available after 24 hours.');
        return $this->successResponse(
            $account,
            'Bank account added successfully.',
            201
        );
    }

    public function removeBankAccount(
        int $id,
        Request $request
    ): JsonResponse {
        $merchant = $request->activeMerchant();
        $business = $request->activeBusiness();

        if (! $merchant || ! $business) {
            return $this->errorResponse(
                'Please select an active business.',
                422
            );
        }

        $merchantId = $merchant->id;
        $businessId = $business->id;

        $account = BankAccount::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->findOrFail($id);

        $hasPending = Payout::query()
            ->where('merchant_id', $merchantId)
            ->where('business_id', $businessId)
            ->where('bank_account_id', $id)
            ->whereIn('status', [
                Payout::STATUS_PENDING,
                Payout::STATUS_APPROVED,
                Payout::STATUS_PROCESSING,
            ])
            ->exists();

        if ($hasPending) {
            return $this->errorResponse(
                'This bank account has a pending withdrawal.',
                422
            );
        }

        $account->update([
            'status' => 'inactive',
        ]);
        app(\App\Services\AuditService::class)->log($request->user()->id,'bank_account.removed',$account,null,['account_last_four'=>substr($account->account_number,-4)]);

        return $this->successResponse(
            [],
            'Bank account removed successfully.'
        );
    }

    public function verifyBankAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_number' => [
                'required',
                'digits:10',
            ],
            'bank_code' => [
                'required',
                'string',
                'size:3',
            ],
        ]);

        $result = $this->payoutService->verifyBankAccount(
            $validated['account_number'],
            $validated['bank_code']
        );

        if (
            strtolower((string) ($result['status'] ?? ''))
            === 'success'
        ) {
            return $this->successResponse(
                $result,
                'Bank account verified successfully.'
            );
        }

        return $this->errorResponse(
            $result['message'] ?? 'Bank verification failed.',
            422
        );
    }
}
