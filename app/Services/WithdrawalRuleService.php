<?php

namespace App\Services;

use App\Models\MerchantWithdrawalSetting;
use App\Models\Payout;
use Illuminate\Validation\ValidationException;

class WithdrawalRuleService
{
    public function evaluate(
        string $merchantType,
        float|int|string $amount,
        float|int|string $withdrawnToday = 0,
        int $requestsToday = 0
    ): array {
        $amount = \App\Support\Money::positive($amount);
        $withdrawnToday = \App\Support\Money::format($withdrawnToday);

        $setting = MerchantWithdrawalSetting::query()
            ->where('merchant_type', $merchantType)
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Withdrawal settings have not been configured for this business type.',
                ],
            ]);
        }

        if (! $setting->withdrawals_enabled) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Withdrawals are currently disabled for this business type.',
                ],
            ]);
        }

        if (
            \App\Support\Money::decimal($amount)->isLessThan($setting->minimum_withdrawal_amount)
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    sprintf(
                        'The minimum withdrawal amount is ₦%s.',
                        number_format(
                            (string) $setting->minimum_withdrawal_amount,
                            2
                        )
                    ),
                ],
            ]);
        }

        if (
            $setting->maximum_withdrawal_amount !== null &&
            \App\Support\Money::decimal($amount)->isGreaterThan($setting->maximum_withdrawal_amount)
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    sprintf(
                        'The maximum withdrawal amount is ₦%s.',
                        number_format(
                            (string) $setting->maximum_withdrawal_amount,
                            2
                        )
                    ),
                ],
            ]);
        }

        if (
            $setting->daily_withdrawal_limit !== null &&
            \App\Support\Money::decimal($withdrawnToday)->plus($amount)->isGreaterThan($setting->daily_withdrawal_limit)
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    'This withdrawal would exceed your daily withdrawal limit.',
                ],
            ]);
        }

        if (
            $setting->daily_request_limit !== null &&
            $requestsToday >=
            (int) $setting->daily_request_limit
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    'You have reached your daily withdrawal request limit.',
                ],
            ]);
        }

        $fee = $setting->calculateFee($amount);

        $addOn = $setting->fee_collection_mode ===
            MerchantWithdrawalSetting::FEE_MODE_ADD_ON;

        $manualReview =
            $setting->requiresManualReview($amount);

        return [
            'setting_id' => $setting->id,
            'merchant_type' => $merchantType,
            'amount' => $amount,
            'fee' => $fee,

            'wallet_debit' => $addOn
                ? (string) \App\Support\Money::decimal($amount)->plus($fee)
                : $amount,

            'net_amount' => $addOn
                ? $amount
                : (string) \Brick\Math\BigDecimal::max(\App\Support\Money::decimal($amount)->minus($fee), '0.00'),

            'fee_collection_mode' =>
                $setting->fee_collection_mode,

            'automatic' => ! $manualReview,

            'processing_mode' => $manualReview
                ? Payout::MODE_MANUAL
                : Payout::MODE_AUTOMATIC,

            'initial_status' => $manualReview
                ? Payout::STATUS_PENDING
                : Payout::STATUS_APPROVED,

            'review_reason' => $manualReview
                ? $this->manualReviewReason(
                    $setting,
                    $amount
                )
                : null,

            'automatic_limit' =>
                (string) $setting->automatic_withdrawal_limit,

            'daily_limit' =>
                $setting->daily_withdrawal_limit !== null
                    ? (string) $setting->daily_withdrawal_limit
                    : null,
        ];
    }

    private function manualReviewReason(
        MerchantWithdrawalSetting $setting,
        string $amount
    ): string {
        if (! $setting->automatic_withdrawals_enabled) {
            return 'Automatic withdrawals are disabled for this business type.';
        }

        if (
            \App\Support\Money::decimal($amount)->isGreaterThan($setting->automatic_withdrawal_limit)
        ) {
            return sprintf(
                'Withdrawal amount exceeds the automatic processing limit of ₦%s.',
                number_format(
                    (string) $setting->automatic_withdrawal_limit,
                    2
                )
            );
        }

        return 'This withdrawal requires administrator review.';
    }
}