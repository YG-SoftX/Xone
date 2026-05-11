<?php

namespace App\Services;

use App\Models\Theme;
use App\Models\FrontendContent;

class ThemeService
{
    /**
     * Get the active theme for a service with all customizations
     */
    public static function getTheme(string $service = 'account'): array
    {
        $theme = Theme::forService($service)->active()->first();
        
        $defaultColors = [
            'primary' => '#4285F4',
            'secondary' => '#34A853',
            'background' => '#FFFFFF',
            'surface' => '#F8F9FA',
            'text' => '#202124',
            'accent' => '#EA4335',
        ];

        $defaultFonts = [
            'heading_font' => 'Google Sans',
            'body_font' => 'Roboto',
        ];

        $defaultSettings = [
            'border_radius' => 8,
            'shadow_style' => 'subtle',
        ];

        return [
            'colors' => array_merge($defaultColors, $theme?->colors ?? []),
            'fonts' => array_merge($defaultFonts, $theme?->fonts ?? []),
            'logos' => $theme?->logos ?? [],
            'settings' => array_merge($defaultSettings, $theme?->settings ?? []),
            'name' => $theme?->name ?? 'default',
        ];
    }

    /**
     * Get CSS custom properties for the theme
     */
    public static function getCssVariables(string $service = 'account'): string
    {
        $theme = self::getTheme($service);
        $colors = $theme['colors'];
        $fonts = $theme['fonts'];
        $settings = $theme['settings'];

        return ":root {
            --color-primary: {$colors['primary']};
            --color-secondary: {$colors['secondary']};
            --color-background: {$colors['background']};
            --color-surface: {$colors['surface']};
            --color-text: {$colors['text']};
            --color-accent: {$colors['accent']};
            --font-heading: '{$fonts['heading_font']}', system-ui, sans-serif;
            --font-body: '{$fonts['body_font']}', system-ui, sans-serif;
            --border-radius: {$settings['border_radius']}px;
        }";
    }

    /**
     * Get frontend content by key
     */
    public static function getContent(string $key, string $locale = 'en'): ?FrontendContent
    {
        return FrontendContent::published()->byKey($key)->byLocale($locale)->first();
    }
}
