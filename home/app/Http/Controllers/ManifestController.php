<?php

namespace App\Http\Controllers;

use App\Services\BrowserConfigService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * ManifestController — Serves a dynamic site.webmanifest that reflects
 * the Master Admin panel's Browser Settings configuration.
 *
 * This replaces the static site.webmanifest file. Every change in the
 * Master Panel (branding, colors, icons, splash screen) is reflected
 * immediately without touching any files.
 */
class ManifestController extends Controller
{
    /**
     * Generate and serve the dynamic web manifest.
     * Route: GET /site.webmanifest
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $config = app(BrowserConfigService::class);
            $manifest = $config->pwaManifest();
            $splash = $config->splashConfig();
        } catch (\Exception $e) {
            // Fallback to hardcoded defaults if config service fails
            $manifest = [
                'name'             => 'YGXONE Browser',
                'short_name'       => 'YGXONE',
                'description'      => 'AI-powered agentic browser',
                'start_url'        => '/',
                'scope'            => '/',
                'display'          => 'standalone',
                'orientation'      => 'any',
                'theme_color'      => '#2563eb',
                'background_color' => '#ffffff',
            ];
            $splash = [
                'enabled'       => true,
                'title'         => 'YGXONE',
                'subtitle'      => 'AI-Powered Agentic Browser',
                'bg_color'      => '#0f172a',
                'spinner_color' => '#2563eb',
            ];
        }

        $data = [
            'name'             => $manifest['name'],
            'short_name'       => $manifest['short_name'],
            'description'      => $manifest['description'],
            'start_url'        => $manifest['start_url'],
            'scope'            => $manifest['scope'],
            'display'          => $manifest['display'],
            'orientation'      => $manifest['orientation'] ?? 'any',
            'background_color' => $manifest['background_color'],
            'theme_color'      => $manifest['theme_color'],
            'categories'       => ['productivity', 'utilities'],
            'icons'            => [
                [
                    'src'     => '/icons/icon-192.png',
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src'     => '/icons/icon-512.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'screenshots' => [
                [
                    'src'          => '/icons/screenshot.png',
                    'sizes'        => '1280x720',
                    'type'         => 'image/png',
                    'form_factor'  => 'wide',
                ],
            ],
            'shortcuts' => [
                [
                    'name'       => 'Browser',
                    'short_name' => 'Browse',
                    'url'        => '/',
                    'icons'      => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']],
                ],
                [
                    'name'       => 'AI Agent',
                    'short_name' => 'Agent',
                    'url'        => '/agent/settings',
                    'icons'      => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']],
                ],
            ],
            // Dynamic splash metadata (non-standard but informative)
            'yg_splash' => $splash,
        ];

        return response()->json(
            $data,
            200,
            [
                'Content-Type'  => 'application/manifest+json',
                'Cache-Control' => 'public, max-age=86400',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
