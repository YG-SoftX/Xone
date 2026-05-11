<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ThemeHelper
{
    /**
     * Get the active theme settings for a specific service.
     */
    public static function getActiveTheme($service = 'account')
    {
        return Cache::remember("theme_{$service}", 3600, function () use ($service) {
            $theme = \App\Models\Theme::where('service', $service)
                ->where('is_active', true)
                ->first();

            if (!$theme) {
                return self::getDefaultTheme($service);
            }

            // Convert local storage paths to full URLs
            $theme->logos = self::processLogoPaths($theme->logos);

            return $theme;
        });
    }

    /**
     * Convert relative storage paths to full asset URLs.
     */
    private static function processLogoPaths($logos)
    {
        if (!$logos) return [];

        foreach ($logos as $key => $path) {
            if ($path && !filter_var($path, FILTER_VALIDATE_URL)) {
                // If it's not a full URL, treat it as a storage path
                $logos[$key] = asset('storage/' . ltrim($path, '/'));
            }
        }
        return $logos;
    }

    /**
     * Default theme fallback if no active theme is found in DB.
     */
    private static function getDefaultTheme($service)
    {
        return (object) [
            'service' => $service,
            'name' => 'Default Premium',
            'colors' => [
                'primary' => '#2563eb',
                'secondary' => '#9333ea',
                'background' => '#f8fafc',
            ],
            'logos' => [
                'powered_by_name' => 'YGXone',
                'header_logo' => null, // Will use SVG fallback if null
            ],
            'settings' => [
                'powered_by_text' => 'Powered by',
                'copyright' => 'Sovereign Digital Ecosystem • All Rights Reserved'
            ]
        ];
    }
}
