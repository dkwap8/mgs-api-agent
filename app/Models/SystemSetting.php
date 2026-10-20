<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getValue(
        string $key,
        mixed $default = null
    ): mixed {
        $value = static::query()
            ->where('key', $key)
            ->value('value');

        return $value === null || $value === ''
            ? $default
            : $value;
    }

    public static function getBoolean(
        string $key,
        bool $default = false
    ): bool {
        $value = static::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public static function getInteger(
        string $key,
        ?int $default = null
    ): ?int {
        $value = static::getValue($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }

    public static function setValue(
        string $key,
        mixed $value,
        string $group = 'general'
    ): self {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return static::query()->updateOrCreate(
            [
                'key' => $key,
            ],
            [
                'value' => $value === null
                    ? ''
                    : (string) $value,

                'group' => $group,
            ]
        );
    }
}