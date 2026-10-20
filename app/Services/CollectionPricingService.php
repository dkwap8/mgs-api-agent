<?php
namespace App\Services;

use App\Models\PricingRule;
use App\Support\Money;
use Brick\Math\RoundingMode;
use RuntimeException;

class CollectionPricingService
{
    public function calculate(string $amount): ?array
    {
        $amount = Money::positive($amount);
        $active = PricingRule::query()->where('type', 'collection')->where('is_active', true);
        // Existing provider configurations continue until a commercial schedule is activated.
        if (! (clone $active)->exists()) return null;
        $rules = $active->where(fn ($q) => $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount))
            ->where(fn ($q) => $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount))->get();
        if ($rules->count() !== 1) throw new RuntimeException('Collection pricing must match exactly one active rule; review uncovered or overlapping ranges.');
        $rule = $rules->first();
        $fee = Money::decimal($amount)->multipliedBy($rule->fee_percentage ?? '0.00')
            ->dividedBy(100, 2, RoundingMode::HalfUp)->plus($rule->fee_amount ?? '0.00');
        if ($fee->isNegative() || $fee->isGreaterThan($amount)) throw new RuntimeException('Collection fee exceeds the payment amount.');
        return ['pricing_rule_id' => $rule->id, 'amount' => $amount, 'charge' => (string) $fee,
            'wallet_credit' => (string) Money::decimal($amount)->minus($fee), 'total_payable' => $amount,
            'collection_mode' => 'deduct'];
    }
}
