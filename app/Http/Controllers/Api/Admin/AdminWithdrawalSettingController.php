<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantWithdrawalSetting;
use App\Services\WithdrawalRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminWithdrawalSettingController extends Controller
{
    /**
     * Return every configured merchant withdrawal rule.
     */
    public function index(): JsonResponse
    {
        $settings = MerchantWithdrawalSetting::query()
            ->orderByRaw("
                CASE merchant_type
                    WHEN 'starter' THEN 1
                    WHEN 'registered' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('merchant_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Return one merchant-type withdrawal configuration.
     */
    public function show(
        MerchantWithdrawalSetting $withdrawalSetting
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $withdrawalSetting,
        ]);
    }

    /**
     * Create a configuration for a future merchant type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSettings($request);

        $setting = DB::transaction(function () use (
            $validated
        ): MerchantWithdrawalSetting {
            return MerchantWithdrawalSetting::create(
                $this->normalizedPayload($validated)
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal settings created successfully.',
            'data' => $setting,
        ], 201);
    }

    /**
     * Update an existing merchant-type configuration.
     */
    public function update(
        Request $request,
        MerchantWithdrawalSetting $withdrawalSetting
    ): JsonResponse {
        $validated = $this->validateSettings(
            $request,
            $withdrawalSetting
        );

        DB::transaction(function () use (
            $validated,
            $withdrawalSetting
        ): void {
            $withdrawalSetting->update(
                $this->normalizedPayload($validated)
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal settings updated successfully.',
            'data' => $withdrawalSetting->fresh(),
        ]);
    }

    /**
     * Preview the fee and approval decision without creating a payout.
     */
    public function preview(
        Request $request,
        WithdrawalRuleService $withdrawalRuleService
    ): JsonResponse {
        $validated = $request->validate([
            'merchant_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists(
                    'merchant_withdrawal_settings',
                    'merchant_type'
                ),
            ],

            'amount' => [
                new \App\Rules\ExactAmount,
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],

            'withdrawn_today' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'requests_today' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000',
            ],
        ]);

        $result = $withdrawalRuleService->evaluate(
            $validated['merchant_type'],
            $validated['amount'],
            $validated['withdrawn_today'] ?? 0,
            $validated['requests_today'] ?? 0
        );

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal preview calculated successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Enable or disable withdrawals for one merchant type.
     */
    public function toggle(
        MerchantWithdrawalSetting $withdrawalSetting
    ): JsonResponse {
        $withdrawalSetting->update([
            'withdrawals_enabled' =>
                ! $withdrawalSetting->withdrawals_enabled,
        ]);

        return response()->json([
            'success' => true,

            'message' => $withdrawalSetting->withdrawals_enabled
                ? 'Withdrawals enabled successfully.'
                : 'Withdrawals disabled successfully.',

            'data' => $withdrawalSetting,
        ]);
    }

    /**
     * Delete only custom settings.
     *
     * Starter and registered settings are protected because they
     * are required by the current onboarding architecture.
     */
    public function destroy(
        MerchantWithdrawalSetting $withdrawalSetting
    ): JsonResponse {
        if (
            in_array(
                $withdrawalSetting->merchant_type,
                [
                    MerchantWithdrawalSetting::TYPE_STARTER,
                    MerchantWithdrawalSetting::TYPE_REGISTERED,
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Starter and Registered withdrawal settings cannot be deleted.',
            ], 422);
        }

        $withdrawalSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal settings deleted successfully.',
        ]);
    }

    private function validateSettings(
        Request $request,
        ?MerchantWithdrawalSetting $setting = null
    ): array {
        return $request->validate([
            'merchant_type' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9][a-z0-9_-]*$/',
                Rule::unique(
                    'merchant_withdrawal_settings',
                    'merchant_type'
                )->ignore($setting?->id),
            ],

            'withdrawals_enabled' => [
                'required',
                'boolean',
            ],

            'automatic_withdrawals_enabled' => [
                'required',
                'boolean',
            ],

            'automatic_withdrawal_limit' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'minimum_withdrawal_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],

            'maximum_withdrawal_amount' => [
                'nullable',
                'numeric',
                'gte:minimum_withdrawal_amount',
                'max:999999999999.99',
            ],

            'daily_withdrawal_limit' => [
                'nullable',
                'numeric',
                'gte:minimum_withdrawal_amount',
                'max:999999999999.99',
            ],

            'charge_enabled' => [
                'required',
                'boolean',
            ],

            'charge_type' => [
                'required',
                Rule::in([
                    MerchantWithdrawalSetting::CHARGE_PERCENTAGE,
                    MerchantWithdrawalSetting::CHARGE_FIXED,
                ]),
            ],

            'percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'fixed_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'maximum_charge' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'minimum_charge' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'fee_collection_mode' => [
                'required',
                Rule::in([
                    MerchantWithdrawalSetting::FEE_MODE_ADD_ON,
                    MerchantWithdrawalSetting::FEE_MODE_DEDUCT,
                ]),
            ],

            'daily_request_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
        ], [
            'merchant_type.regex' =>
                'Merchant type may contain lowercase letters, numbers, underscores and hyphens only.',

            'maximum_withdrawal_amount.gte' =>
                'The maximum withdrawal amount cannot be lower than the minimum withdrawal amount.',

            'daily_withdrawal_limit.gte' =>
                'The daily withdrawal limit cannot be lower than the minimum withdrawal amount.',
        ]);
    }

    private function normalizedPayload(
        array $validated
    ): array {
        $chargeEnabled = (bool) $validated[
            'charge_enabled'
        ];

        $automaticEnabled = (bool) $validated[
            'automatic_withdrawals_enabled'
        ];

        return [
            'merchant_type' => strtolower(
                trim($validated['merchant_type'])
            ),

            'withdrawals_enabled' =>
                (bool) $validated['withdrawals_enabled'],

            'automatic_withdrawals_enabled' =>
                $automaticEnabled,

            'automatic_withdrawal_limit' =>
                $automaticEnabled
                    ? (string) \Brick\Math\BigDecimal::of($validated[
                            'automatic_withdrawal_limit'
                        ])->toScale(2, \Brick\Math\RoundingMode::HalfUp)
                    : 0,

            'minimum_withdrawal_amount' => (string) \Brick\Math\BigDecimal::of($validated[
                    'minimum_withdrawal_amount'
                ])->toScale(2, \Brick\Math\RoundingMode::HalfUp),

            'maximum_withdrawal_amount' =>
                $this->nullableMoney(
                    $validated[
                        'maximum_withdrawal_amount'
                    ] ?? null
                ),

            'daily_withdrawal_limit' =>
                $this->nullableMoney(
                    $validated[
                        'daily_withdrawal_limit'
                    ] ?? null
                ),

            'charge_enabled' => $chargeEnabled,

            'charge_type' => $validated['charge_type'],

            'percentage' =>
                $chargeEnabled &&
                $validated['charge_type'] ===
                    MerchantWithdrawalSetting::CHARGE_PERCENTAGE
                    ? (string) \Brick\Math\BigDecimal::of($validated['percentage'])->toScale(4, \Brick\Math\RoundingMode::HalfUp)
                    : 0,

            'fixed_amount' =>
                $chargeEnabled &&
                $validated['charge_type'] ===
                    MerchantWithdrawalSetting::CHARGE_FIXED
                    ? (string) \Brick\Math\BigDecimal::of($validated['fixed_amount'])->toScale(2, \Brick\Math\RoundingMode::HalfUp)
                    : 0,

            'maximum_charge' => $chargeEnabled
                ? $this->nullableMoney(
                    $validated['maximum_charge'] ?? null
                )
                : null,

            'minimum_charge' => $chargeEnabled
                ? $this->nullableMoney(
                    $validated['minimum_charge'] ?? null
                )
                : null,

            'fee_collection_mode' =>
                $validated['fee_collection_mode'],

            'daily_request_limit' =>
                isset($validated['daily_request_limit']) &&
                $validated['daily_request_limit'] !== ''
                    ? (int) $validated['daily_request_limit']
                    : null,
        ];
    }

    private function nullableMoney(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        return (string) \Brick\Math\BigDecimal::of($value)->toScale(2, \Brick\Math\RoundingMode::HalfUp);
    }
}
