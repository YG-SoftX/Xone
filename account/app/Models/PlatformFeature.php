<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformFeature extends Model
{
    protected $fillable = [
        'feature_key',
        'feature_name',
        'description',
        'is_enabled',
        'is_public',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_public' => 'boolean',
        'config' => 'array',
    ];

    public static function isEnabled(string $key): bool
    {
        $feature = static::where('feature_key', $key)->first();
        return $feature ? $feature->is_enabled : true;
    }

    public static function enable(string $key): void
    {
        static::updateOrCreate(['feature_key' => $key], ['is_enabled' => true]);
    }

    public static function disable(string $key): void
    {
        static::updateOrCreate(['feature_key' => $key], ['is_enabled' => false]);
    }
}
