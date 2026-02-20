<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Store a sensitive value (API keys, SMTP passwords) encrypted at rest.
     *
     * The value is stored as: "enc:" . Crypt::encryptString($value)
     *
     * If $value is null/empty, the setting is cleared.
     */
    public static function setSecret(string $key, ?string $value): void
    {
        $v = trim((string) ($value ?? ''));

        if ($v === '') {
            static::set($key, null);
            return;
        }

        static::set($key, 'enc:' . Crypt::encryptString($v));
    }

    /**
     * Retrieve a sensitive value previously stored via setSecret().
     *
     * If the stored value is not encrypted (legacy), it is returned as-is.
     */
    public static function getSecret(string $key, string $default = ''): string
    {
        $raw = (string) static::get($key, $default);

        if (str_starts_with($raw, 'enc:')) {
            try {
                return Crypt::decryptString(substr($raw, 4));
            } catch (\Throwable $e) {
                // If decryption fails, fall back to default.
                return $default;
            }
        }

        return $raw;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('settings:' . $key, function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();
            return $row?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value]
        );

        Cache::forget('settings:' . $key);
    }
}
