<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class Money
{
    public static function decimal(float|int|string $value): BigDecimal
    {
        // JSON numbers decoded as floats have limited precision. Large amounts
        // must arrive as decimal strings, rather than silently losing kobo.
        if (is_float($value) && (! is_finite($value) || abs($value) > 999999999.99)) {
            throw new InvalidArgumentException('Large amounts must be supplied as decimal strings.');
        }
        try {
            return BigDecimal::of((string) $value)->toScale(2, RoundingMode::Unnecessary);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Amounts must be exact decimals with at most two fractional digits.', 0, $exception);
        }
    }

    public static function format(float|int|string $value): string
    {
        return (string) self::decimal($value);
    }

    public static function positive(float|int|string $value): string
    {
        $amount = self::decimal($value);
        if ($amount->isLessThanOrEqualTo(0) || $amount->isGreaterThan('9999999999999999.99')) {
            throw new InvalidArgumentException('The amount must be positive and within the supported decimal range.');
        }
        return (string) $amount;
    }

    public static function fee(float|int|string $amount, string $type, float|int|string|null $percentage, float|int|string|null $fixed, float|int|string|null $minimum, float|int|string|null $maximum): string
    {
        $fee = $type === 'percentage'
            ? self::decimal($amount)->multipliedBy(BigDecimal::of((string) ($percentage ?? 0)))->dividedBy(100, 2, RoundingMode::HalfUp)
            : self::decimal($fixed ?? 0);
        if ($minimum !== null && $fee->isLessThan(self::decimal($minimum))) $fee = self::decimal($minimum);
        if ($maximum !== null && $fee->isGreaterThan(self::decimal($maximum))) $fee = self::decimal($maximum);
        return $fee->isNegative() ? '0.00' : (string) $fee;
    }

    public static function json(array $payload, string $amountField = 'amount'): string
    {
        $amount = self::positive($payload[$amountField]);
        $marker = '__MGS_EXACT_AMOUNT_'.bin2hex(random_bytes(16)).'__';
        $payload[$amountField] = $marker;
        return str_replace('"'.$marker.'"', $amount, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public static function jsonDecimals(array $payload, array $fields): string
    {
        $replacements = [];
        foreach ($fields as $field) {
            $decimal = self::format($payload[$field]);
            $marker = '__MGS_DECIMAL_'.bin2hex(random_bytes(16)).'__';
            $payload[$field] = $marker;
            $replacements['"'.$marker.'"'] = $decimal;
        }
        return strtr(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $replacements);
    }
}
