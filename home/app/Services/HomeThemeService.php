<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HomeThemeService — reads theme settings from the shared `themes` table
 * (managed by the master panel / account module) and generates CSS variables
 * for the home module UI.
 *
 * When an admin changes the theme in the master panel, they update the `themes`
 * table with `service = 'home'` or `service = 'yg-xone'`. This service picks
 * up those changes and applies them to the home frontend.
 */
class HomeThemeService
{
    /**
     * Cache TTL in seconds (15 minutes).
     */
    private const CACHE_TTL = 900;

    /**
     * Cache key prefix.
     */
    private const CACHE_PREFIX = 'home_theme_';

    /**
     * Get the full theme configuration array for the home module.
     */
    public function getTheme(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'config', self::CACHE_TTL, function () {
            return $this->fetchTheme();
        });
    }

    /**
     * Generate CSS custom properties string from the active theme.
     */
    public function getCssVariables(): string
    {
        $theme = $this->getTheme();
        $colors = $theme['colors'];
        $fonts = $theme['fonts'];
        $settings = $theme['settings'];

        $css = ":root {\n";

        // Color variables
        foreach ($colors as $key => $value) {
            $css .= "    --yg-{$key}: {$value};\n";
        }

        // Font variables
        $headingFont = $fonts['heading_font'] ?? 'Outfit';
        $bodyFont = $fonts['body_font'] ?? 'Inter';
        $css .= "    --font-heading: '{$headingFont}', system-ui, sans-serif;\n";
        $css .= "    --font-body: '{$bodyFont}', system-ui, sans-serif;\n";

        // Design settings
        $borderRadius = $settings['border_radius'] ?? 12;
        $shadowStyle = $settings['shadow_style'] ?? 'modern';
        $css .= "    --yg-radius: {$borderRadius}px;\n";
        $css .= "    --yg-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);\n";
        $css .= "    --yg-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);\n";
        $css .= "    --yg-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);\n";

        // Background gradient if defined
        if (!empty($theme['background_gradient'])) {
            $css .= "    --yg-bg-gradient: {$theme['background_gradient']};\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Get inline style tag with theme CSS variables.
     */
    public function getCssVariablesTag(): string
    {
        return '<style>' . $this->getCssVariables() . '</style>';
    }

    /**
     * Get the theme logo URL, or null if not set.
     */
    public function getLogo(string $type = 'header_logo'): ?string
    {
        $theme = $this->getTheme();
        $logos = $theme['logos'] ?? [];

        if (!empty($logos[$type])) {
            $path = $logos[$type];
            // If it's a full URL, return as-is
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // Otherwise, treat as storage path
            return asset('storage/' . ltrim($path, '/'));
        }

        return null;
    }

    /**
     * Get the theme-powered-by text.
     */
    public function getPoweredByText(): string
    {
        $theme = $this->getTheme();
        $settings = $theme['settings'] ?? [];

        return $settings['powered_by_text'] ?? 'Powered by';
    }

    /**
     * Get the copyright text.
     */
    public function getCopyrightText(): string
    {
        $theme = $this->getTheme();
        $settings = $theme['settings'] ?? [];

        return $settings['copyright'] ?? 'YGXONE Sovereign Intelligence Platform © ' . date('Y');
    }

    /**
     * Get the favicon URL.
     */
    public function getFavicon(): string
    {
        $logo = $this->getLogo('favicon');
        return $logo ?? 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚀</text></svg>';
    }

    /**
     * Get the header logo HTML (img tag or fallback SVG text).
     */
    public function getHeaderLogoHtml(string $class = 'h-8'): string
    {
        $logoUrl = $this->getLogo('header_logo');

        if ($logoUrl) {
            return '<img src="' . e($logoUrl) . '" alt="YGXONE" class="' . e($class) . '">';
        }

        // SVG text fallback
        return '<span class="font-heading font-black tracking-tight ' . e($class) . '" style="font-size:1.4em">' .
               'YGX<span style="background:linear-gradient(135deg,var(--yg-primary,#2563eb),var(--yg-secondary,#7c3aed));-webkit-background-clip:text;-webkit-text-fill-color:transparent">ONE</span>' .
               '</span>';
    }

    /**
     * Clear the theme cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'config');
    }

    // ── Private ────────────────────────────────────────────────────────────────

    /**
     * Fetch theme from the database, falling back to defaults.
     */
    private function fetchTheme(): array
    {
        $defaultColors = [
            'primary'   => '#2563eb',
            'secondary' => '#7c3aed',
            'accent'    => '#f59e0b',
            'background'=> '#ffffff',
            'surface'   => '#f8fafc',
            'text'      => '#0f172a',
            'text-dim'  => '#64748b',
            'border'    => '#e2e8f0',
            'success'   => '#22c55e',
            'danger'    => '#ef4444',
        ];

        $defaultFonts = [
            'heading_font' => 'Outfit',
            'body_font'    => 'Inter',
        ];

        $defaultSettings = [
            'border_radius' => 12,
            'shadow_style'  => 'modern',
            'powered_by_text' => 'Powered by',
            'copyright'     => 'YGXONE Sovereign Intelligence Platform',
        ];

        try {
            // Try to find a theme for this service
            $theme = DB::table('themes')
                ->where('service', 'home')
                ->where('is_active', true)
                ->first();

            // Fall back to 'yg-xone' service
            if (!$theme) {
                $theme = DB::table('themes')
                    ->where('service', 'yg-xone')
                    ->where('is_active', true)
                    ->first();
            }

            if (!$theme) {
                return [
                    'colors'   => $defaultColors,
                    'fonts'    => $defaultFonts,
                    'logos'    => [],
                    'settings' => $defaultSettings,
                    'name'     => 'Default',
                ];
            }

            $colors = !empty($theme->colors) && is_string($theme->colors)
                ? json_decode($theme->colors, true) ?? []
                : (is_array($theme->colors) ? $theme->colors : []);

            $fonts = !empty($theme->fonts) && is_string($theme->fonts)
                ? json_decode($theme->fonts, true) ?? []
                : (is_array($theme->fonts) ? $theme->fonts : []);

            $logos = !empty($theme->logos) && is_string($theme->logos)
                ? json_decode($theme->logos, true) ?? []
                : (is_array($theme->logos) ? $theme->logos : []);

            $settings = !empty($theme->settings) && is_string($theme->settings)
                ? json_decode($theme->settings, true) ?? []
                : (is_array($theme->settings) ? $theme->settings : []);

            return [
                'colors'   => array_merge($defaultColors, $colors),
                'fonts'    => array_merge($defaultFonts, $fonts),
                'logos'    => $logos,
                'settings' => array_merge($defaultSettings, $settings),
                'name'     => $theme->name ?? 'Custom',
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch theme from database, using defaults', ['error' => $e->getMessage()]);
            return [
                'colors'   => $defaultColors,
                'fonts'    => $defaultFonts,
                'logos'    => [],
                'settings' => $defaultSettings,
                'name'     => 'Default',
            ];
        }
    }
}
