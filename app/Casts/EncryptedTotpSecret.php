<?php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EncryptedTotpSecret implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') return null;
        // Read legacy secrets without rewriting existing records or rotating keys.
        if (preg_match('/^[A-Z2-7]{16,64}$/', (string) $value)) return (string) $value;
        return Crypt::decryptString($value);
    }
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString($value);
    }
}
