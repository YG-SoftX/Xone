<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BrowserSetting — Key-value config store for master-admin-controlled browser settings.
 *
 * Groups:
 *   search_engines  → default_search_engine, enabled_search_engines
 *   presets         → quick_links (JSON array of {name, url, icon, color})
 *   features        → agent_enabled, byok_enabled, browse_quotas
 *   theme           → browser_logo, browser_favicon, browser_brand_name
 */
class BrowserSetting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'group'];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function allSettings(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    /**
     * Get all settings in a group.
     */
    public static function group(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * Set a setting value, creating if it doesn't exist.
     */
    public static function set(string $key, mixed $value, ?string $label = null, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'label' => $label, 'group' => $group]
        );
    }

    /**
     * Get JSON-decoded value.
     */
    public static function getJson(string $key, array $default = []): array
    {
        $value = static::get($key);
        if (empty($value)) return $default;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }
}
