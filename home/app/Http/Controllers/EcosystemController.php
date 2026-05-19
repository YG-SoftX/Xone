<?php

namespace App\Http\Controllers;

use App\Services\EcosystemService;
use App\Services\HomeThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * EcosystemController — API endpoints that allow the frontend (and the master
 * panel) to query ecosystem state, theme settings, and service availability
 * dynamically.
 *
 * This is the data layer that makes the home UI fully controllable from the
 * master panel without needing to redeploy.
 */
class EcosystemController extends Controller
{
    private EcosystemService $ecosystem;
    private HomeThemeService $theme;

    public function __construct(EcosystemService $ecosystem, HomeThemeService $theme)
    {
        $this->ecosystem = $ecosystem;
        $this->theme = $theme;
    }

    /**
     * Get all active ecosystem apps for the home page grid.
     */
    public function apps(): JsonResponse
    {
        $apps = $this->ecosystem->getActiveApps();

        return response()->json([
            'success' => true,
            'data'    => $apps,
            'meta'    => [
                'total'  => count($apps),
                'cached' => Cache::has('yg_ecosystem_active_apps'),
            ],
        ]);
    }

    /**
     * Get ecosystem health status summary.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->ecosystem->getHealthSummary(),
        ]);
    }

    /**
     * Get current theme CSS variables as JSON.
     */
    public function theme(): JsonResponse
    {
        $theme = $this->theme->getTheme();

        return response()->json([
            'success' => true,
            'data'    => [
                'colors'   => $theme['colors'],
                'fonts'    => $theme['fonts'],
                'logos'    => $theme['logos'],
                'settings' => $theme['settings'],
                'name'     => $theme['name'],
                'css'      => $this->theme->getCssVariables(),
            ],
        ]);
    }

    /**
     * Get ecosystem stats for the admin dashboard.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->ecosystem->getEcosystemStats(),
        ]);
    }

    /**
     * Clear ecosystem cache (called by master panel after updates).
     */
    public function clearCache(): JsonResponse
    {
        $this->ecosystem->clearCache();
        $this->theme->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Ecosystem and theme cache cleared.',
        ]);
    }

    /**
     * Get theme helper HTML (CSS variables tag + logo HTML).
     * Used by the master panel to preview themes.
     */
    public function themePreview(): \Illuminate\View\View
    {
        return view('ecosystem.theme-preview', [
            'themeService' => $this->theme,
        ]);
    }
}
