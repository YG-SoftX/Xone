<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UniversalFooterItem;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    /**
     * Get footer items for a specific service
     * 
     * @param string $serviceKey Service identifier (e.g., 'mail', 'drive', 'global')
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $serviceKey = 'global')
    {
        // Validate service key
        $validServices = [
            'global', 'mail', 'drive', 'docx', 'chat', 'calendar',
            'contacts', 'notes', 'xcel', 'meet', 'pay', 'ai',
            'account', 'appstore', 'home'
        ];

        if (!in_array($serviceKey, $validServices)) {
            return response()->json([
                'error' => 'Invalid service key',
                'valid_services' => $validServices
            ], 400);
        }

        // Cache the results for 1 hour to reduce database load
        $cacheKey = "footer_items.{$serviceKey}";
        $footerItems = \Cache::remember($cacheKey, 3600, function () use ($serviceKey) {
            return UniversalFooterItem::forService($serviceKey)
                ->footer()
                ->active()
                ->ordered()
                ->get(['id', 'label', 'url', 'icon', 'is_external', 'metadata'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'label' => $item->label,
                        'url' => $item->url,
                        'icon' => $item->icon ?? 'fas fa-link',
                        'target' => $item->target,
                        'rel' => $item->rel,
                        'metadata' => $item->metadata,
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'service' => $serviceKey,
            'items' => $footerItems,
            'count' => $footerItems->count(),
        ]);
    }

    /**
     * Clear cache for footer items (admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        // Clear all footer item caches
        $services = ['global', 'mail', 'drive', 'docx', 'chat', 'calendar',
                     'contacts', 'notes', 'xcel', 'meet', 'pay', 'ai',
                     'account', 'appstore', 'home'];

        foreach ($services as $service) {
            \Cache::forget("footer_items.{$service}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Footer cache cleared successfully',
        ]);
    }
}
