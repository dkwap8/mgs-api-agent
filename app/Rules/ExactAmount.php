<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExactAmount implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            if (! is_string($value) && ! is_int($value) && ! is_float($value)) throw new \InvalidArgumentException;
            \App\Support\Money::positive($value);
        } catch (\Throwable) {
            $fail('Enter a positive amount with at most two decimal places. Supply large amounts as decimal strings.');
        }
    }
}
