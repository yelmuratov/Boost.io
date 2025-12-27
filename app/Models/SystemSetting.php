<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            // Cast value based on type
            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value by key
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        // Convert value to string for storage
        $stringValue = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;

        $setting->update(['value' => $stringValue]);

        Cache::forget("system_setting_{$key}");

        return true;
    }

    /**
     * Get all settings as an array
     *
     * @return array
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('system_settings_all', 3600, function () {
            $settings = self::query()->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    /**
     * Cast a value based on its type
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private static function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => $value === 'true',
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
