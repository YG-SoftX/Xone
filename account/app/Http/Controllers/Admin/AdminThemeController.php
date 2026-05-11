<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminThemeController extends Controller
{
    /**
     * Available services that can have custom themes.
     */
    private array $services = ['account', 'mail', 'docx', 'xcel'];

    /**
     * Default color palette.
     */
    private array $defaultColors = [
        'primary' => '#3B82F6',
        'secondary' => '#64748B',
        'background' => '#F8FAFC',
        'surface' => '#FFFFFF',
        'text' => '#0F172A',
        'accent' => '#10B981',
    ];

    /**
     * Default font settings.
     */
    private array $defaultFonts = [
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
        'heading_size' => '1.5rem',
        'body_size' => '1rem',
        'small_size' => '0.875rem',
    ];

    /**
     * Default logo paths.
     */
    private array $defaultLogos = [
        'favicon' => '/images/favicon.ico',
        'header_logo' => '/images/logo.svg',
        'header_logo_dark' => '/images/logo-dark.svg',
        'app_icon' => '/images/app-icon.png',
    ];

    /**
     * Default theme settings.
     */
    private array $defaultSettings = [
        'border_radius' => '0.5rem',
        'shadow_style' => 'soft',
    ];

    /**
     * Display theme settings for all services.
     */
    public function index(Request $request)
    {
        try {
            $selectedService = $request->get('service', 'account');

            if (!in_array($selectedService, $this->services)) {
                $selectedService = 'account';
            }

            $themes = Theme::forService($selectedService)
                ->orderBy('is_active', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $stats = [
                'total_themes' => Theme::count(),
                'active_themes' => Theme::active()->count(),
                'services_with_themes' => Theme::select('service')
                    ->distinct()
                    ->pluck('service')
                    ->count(),
            ];

            return view('admin.themes.index', compact('themes', 'stats', 'selectedService'));
        } catch (Exception $e) {
            Log::error('AdminThemeController@index failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load theme settings.');
        }
    }

    /**
     * Save theme colors, fonts, logos, and settings as JSON.
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'theme_id' => 'nullable|exists:themes,id',
                'service' => 'required|in:account,mail,docx,xcel',
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
                // Colors — hex (#RGB / #RRGGBB / #RRGGBBAA) or CSS rgb()/rgba()/hsl()/hsla()
                'color_primary'    => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                'color_secondary'  => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                'color_background' => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                'color_surface'    => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                'color_text'       => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                'color_accent'     => ['nullable', 'string', 'max:50', 'regex:/^(#[0-9A-Fa-f]{3,8}|rgb\(|rgba\(|hsl\(|hsla\()/'],
                // Fonts — safe font family names and CSS size values
                'font_heading_font' => ['nullable', 'string', 'max:100', 'regex:/^[\w\s,\-]+$/'],
                'font_body_font'    => ['nullable', 'string', 'max:100', 'regex:/^[\w\s,\-]+$/'],
                'font_heading_size' => ['nullable', 'string', 'max:20',  'regex:/^\d+(\.\d+)?(px|rem|em|%)$/'],
                'font_body_size'    => ['nullable', 'string', 'max:20',  'regex:/^\d+(\.\d+)?(px|rem|em|%)$/'],
                'font_small_size'   => ['nullable', 'string', 'max:20',  'regex:/^\d+(\.\d+)?(px|rem|em|%)$/'],
                // Logos — must be a root-relative path (/...) or https URL; no javascript: or data:
                'logo_favicon'          => ['nullable', 'string', 'max:500', 'regex:/^(\/[\w\-\.\/]+|https:\/\/).*/'],
                'logo_header_logo'      => ['nullable', 'string', 'max:500', 'regex:/^(\/[\w\-\.\/]+|https:\/\/).*/'],
                'logo_header_logo_dark' => ['nullable', 'string', 'max:500', 'regex:/^(\/[\w\-\.\/]+|https:\/\/).*/'],
                'logo_app_icon'         => ['nullable', 'string', 'max:500', 'regex:/^(\/[\w\-\.\/]+|https:\/\/).*/'],
                // Settings
                'setting_border_radius' => ['nullable', 'string', 'max:20', 'regex:/^\d+(\.\d+)?(px|rem|em|%)$/'],
                'setting_shadow_style'  => 'nullable|in:none,soft,medium,hard',
            ]);

            $colors = [
                'primary' => $validated['color_primary'] ?? $this->defaultColors['primary'],
                'secondary' => $validated['color_secondary'] ?? $this->defaultColors['secondary'],
                'background' => $validated['color_background'] ?? $this->defaultColors['background'],
                'surface' => $validated['color_surface'] ?? $this->defaultColors['surface'],
                'text' => $validated['color_text'] ?? $this->defaultColors['text'],
                'accent' => $validated['color_accent'] ?? $this->defaultColors['accent'],
            ];

            $fonts = [
                'heading_font' => $validated['font_heading_font'] ?? $this->defaultFonts['heading_font'],
                'body_font' => $validated['font_body_font'] ?? $this->defaultFonts['body_font'],
                'heading_size' => $validated['font_heading_size'] ?? $this->defaultFonts['heading_size'],
                'body_size' => $validated['font_body_size'] ?? $this->defaultFonts['body_size'],
                'small_size' => $validated['font_small_size'] ?? $this->defaultFonts['small_size'],
            ];

            $logos = [
                'favicon' => $validated['logo_favicon'] ?? $this->defaultLogos['favicon'],
                'header_logo' => $validated['logo_header_logo'] ?? $this->defaultLogos['header_logo'],
                'header_logo_dark' => $validated['logo_header_logo_dark'] ?? $this->defaultLogos['header_logo_dark'],
                'app_icon' => $validated['logo_app_icon'] ?? $this->defaultLogos['app_icon'],
            ];

            $settings = [
                'border_radius' => $validated['setting_border_radius'] ?? $this->defaultSettings['border_radius'],
                'shadow_style' => $validated['setting_shadow_style'] ?? $this->defaultSettings['shadow_style'],
            ];

            // If setting this theme as active, deactivate others for the same service
            if (!empty($validated['is_active'])) {
                Theme::forService($validated['service'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            if (!empty($validated['theme_id'])) {
                // Update existing theme
                $theme = Theme::findOrFail($validated['theme_id']);
                $theme->update([
                    'name' => $validated['name'],
                    'service' => $validated['service'],
                    'is_active' => !empty($validated['is_active']),
                    'colors' => $colors,
                    'fonts' => $fonts,
                    'logos' => $logos,
                    'settings' => $settings,
                ]);

                Log::info("Theme updated", [
                    'theme_id' => $theme->id,
                    'service' => $validated['service'],
                    'name' => $validated['name'],
                ]);

                return redirect()->back()->with('success', "Theme '{$theme->name}' updated successfully.");
            }

            // Create new theme
            $theme = Theme::create([
                'name' => $validated['name'],
                'service' => $validated['service'],
                'is_active' => !empty($validated['is_active']),
                'colors' => $colors,
                'fonts' => $fonts,
                'logos' => $logos,
                'settings' => $settings,
            ]);

            Log::info("Theme created", [
                'theme_id' => $theme->id,
                'service' => $validated['service'],
                'name' => $validated['name'],
            ]);

            return redirect()->back()->with('success', "Theme '{$theme->name}' created successfully.");
        } catch (Exception $e) {
            Log::error('AdminThemeController@update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to save theme. Please try again.');
        }
    }

    /**
     * Reset a theme to default values.
     */
    public function reset($id)
    {
        try {
            $theme = Theme::findOrFail($id);

            $theme->update([
                'colors' => $this->defaultColors,
                'fonts' => $this->defaultFonts,
                'logos' => $this->defaultLogos,
                'settings' => $this->defaultSettings,
            ]);

            Log::info("Theme reset to defaults", [
                'theme_id' => $theme->id,
                'service' => $theme->service,
                'name' => $theme->name,
            ]);

            return redirect()->back()->with('success', "Theme '{$theme->name}' reset to defaults.");
        } catch (Exception $e) {
            Log::error('AdminThemeController@reset failed: ' . $e->getMessage(), [
                'theme_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to reset theme.');
        }
    }

    /**
     * Preview frontend with the selected theme.
     */
    public function preview($id)
    {
        try {
            $theme = Theme::findOrFail($id);

            // Store preview theme in session for frontend rendering
            session(['preview_theme' => $theme->toArray()]);

            Log::info("Theme preview requested", [
                'theme_id' => $theme->id,
                'service' => $theme->service,
            ]);

            return view('admin.themes.preview', compact('theme'));
        } catch (Exception $e) {
            Log::error('AdminThemeController@preview failed: ' . $e->getMessage(), [
                'theme_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load theme preview.');
        }
    }

    /**
     * Delete a theme.
     */
    public function destroy($id)
    {
        try {
            $theme = Theme::findOrFail($id);

            // Prevent deleting the only active theme for a service
            $activeCount = Theme::forService($theme->service)->where('is_active', true)->count();
            if ($theme->is_active && $activeCount <= 1) {
                return redirect()->back()->with('error', 'Cannot delete the only active theme for this service. Deactivate another theme first.');
            }

            $name = $theme->name;
            $theme->delete();

            Log::info("Theme deleted", [
                'theme_id' => $id,
                'name' => $name,
            ]);

            return redirect()->back()->with('success', "Theme '{$name}' deleted.");
        } catch (Exception $e) {
            Log::error('AdminThemeController@destroy failed: ' . $e->getMessage(), [
                'theme_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete theme.');
        }
    }
}
