<?php

namespace App\Http\Controllers;

use App\Services\BrowserExtensionService;
use App\Services\DeveloperToolsService;
use App\Services\OfflineModeService;
use App\Services\CrossDeviceSyncService;
use App\Services\SourceComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BrowserFeaturesController
 * 
 * Handles API endpoints for advanced browser features:
 * - Extensions management
 * - Developer tools
 * - Offline mode
 * - Cross-device sync
 * - Source comparison
 */
class BrowserFeaturesController extends Controller
{
    // ────────────────────────────────────────────────────────────────────
    //  Extensions Management
    // ────────────────────────────────────────────────────────────────────

    /**
     * Get installed extensions
     * GET /api/extensions/list
     */
    public function getExtensions()
    {
        try {
            $extensionService = app(BrowserExtensionService::class);
            $extensions = $extensionService->getInstalledExtensions();
            
            return response()->json([
                'success' => true,
                'extensions' => $extensions,
                'count' => count($extensions),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get marketplace extensions
     * GET /api/extensions/marketplace
     */
    public function getMarketplace()
    {
        try {
            $extensionService = app(BrowserExtensionService::class);
            $marketplace = $extensionService->getMarketplaceExtensions();
            
            return response()->json([
                'success' => true,
                'marketplace' => $marketplace,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Toggle extension enable/disable
     * POST /api/extensions/toggle
     */
    public function toggleExtension(Request $request)
    {
        $extensionId = $request->input('extension_id');
        $enabled = $request->input('enabled', true);
        
        if (!$extensionId) {
            return response()->json(['success' => false, 'error' => 'Extension ID required'], 400);
        }
        
        try {
            $extensionService = app(BrowserExtensionService::class);
            $success = $extensionService->toggleExtension($extensionId, $enabled);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Extension toggled' : 'Failed to toggle',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Uninstall extension
     * DELETE /api/extensions/uninstall/{id}
     */
    public function uninstallExtension($id)
    {
        try {
            $extensionService = app(BrowserExtensionService::class);
            $success = $extensionService->uninstallExtension($id);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Extension uninstalled' : 'Failed to uninstall',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Developer Tools
    // ────────────────────────────────────────────────────────────────────

    /**
     * Get network logs
     * GET /api/devtools/network?limit=50
     */
    public function getNetworkLogs(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        try {
            $devTools = app(DeveloperToolsService::class);
            $logs = $devTools->getNetworkLogs($limit);
            
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'count' => count($logs),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get console logs
     * GET /api/devtools/console?limit=50
     */
    public function getConsoleLogs(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        try {
            $devTools = app(DeveloperToolsService::class);
            $logs = $devTools->getConsoleLogs($limit);
            
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'count' => count($logs),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Analyze page elements
     * POST /api/devtools/analyze-elements
     */
    public function analyzeElements(Request $request)
    {
        $html = $request->input('html', '');
        
        if (empty($html)) {
            return response()->json(['success' => false, 'error' => 'HTML required'], 400);
        }
        
        try {
            $devTools = app(DeveloperToolsService::class);
            $analysis = $devTools->analyzeElements($html);
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Measure page performance
     * POST /api/devtools/performance
     */
    public function measurePerformance(Request $request)
    {
        $html = $request->input('html', '');
        
        if (empty($html)) {
            return response()->json(['success' => false, 'error' => 'HTML required'], 400);
        }
        
        try {
            $devTools = app(DeveloperToolsService::class);
            $metrics = $devTools->measurePerformance($html);
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Clear dev tools logs
     * DELETE /api/devtools/clear
     */
    public function clearDevToolsLogs()
    {
        try {
            $devTools = app(DeveloperToolsService::class);
            $success = $devTools->clearLogs();
            
            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Offline Mode
    // ────────────────────────────────────────────────────────────────────

    /**
     * Get Service Worker script
     * GET /api/offline/service-worker.js
     */
    public function getServiceWorker()
    {
        try {
            $offlineService = app(OfflineModeService::class);
            $sw = $offlineService->generateServiceWorker();
            
            return response($sw, 200, [
                'Content-Type' => 'application/javascript',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get offline fallback page
     * GET /api/offline/page
     */
    public function getOfflinePage()
    {
        try {
            $offlineService = app(OfflineModeService::class);
            $html = $offlineService->generateOfflinePage();
            
            return response($html, 200, [
                'Content-Type' => 'text/html',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get cached pages list
     * GET /api/offline/cached-pages
     */
    public function getCachedPages()
    {
        try {
            $offlineService = app(OfflineModeService::class);
            $pages = $offlineService->getCachedPages();
            
            return response()->json([
                'success' => true,
                'pages' => $pages,
                'count' => count($pages),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Clear offline cache
     * DELETE /api/offline/clear
     */
    public function clearOfflineCache()
    {
        try {
            $offlineService = app(OfflineModeService::class);
            $success = $offlineService->clearCache();
            
            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Cross-Device Sync
    // ────────────────────────────────────────────────────────────────────

    /**
     * Sync bookmarks
     * POST /api/sync/bookmarks
     */
    public function syncBookmarks(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        $bookmarks = $request->input('bookmarks', []);
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $success = $syncService->syncBookmarks(auth()->id(), $bookmarks);
            
            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get synced bookmarks
     * GET /api/sync/bookmarks
     */
    public function getSyncedBookmarks()
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $bookmarks = $syncService->getSyncedBookmarks(auth()->id());
            
            return response()->json([
                'success' => true,
                'bookmarks' => $bookmarks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Sync browsing history
     * POST /api/sync/history
     */
    public function syncHistory(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        $history = $request->input('history', []);
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $success = $syncService->syncHistory(auth()->id(), $history);
            
            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get synced history
     * GET /api/sync/history?limit=100
     */
    public function getSyncedHistory(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        $limit = $request->input('limit', 100);
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $history = $syncService->getSyncedHistory(auth()->id(), $limit);
            
            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Register device for sync
     * POST /api/sync/register-device
     */
    public function registerDevice(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        $deviceId = $request->input('device_id', session()->getId());
        $deviceName = $request->input('device_name', 'Unknown Device');
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $success = $syncService->registerDevice(auth()->id(), $deviceId, $deviceName);
            
            return response()->json([
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get user devices
     * GET /api/sync/devices
     */
    public function getUserDevices()
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Authentication required'], 401);
        }
        
        try {
            $syncService = app(CrossDeviceSyncService::class);
            $devices = $syncService->getUserDevices(auth()->id());
            
            return response()->json([
                'success' => true,
                'devices' => $devices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Source Comparison
    // ────────────────────────────────────────────────────────────────────

    /**
     * Compare two sources
     * POST /api/compare/sources
     */
    public function compareSources(Request $request)
    {
        $source1 = $request->input('source_1', []);
        $source2 = $request->input('source_2', []);
        
        if (empty($source1) || empty($source2)) {
            return response()->json(['success' => false, 'error' => 'Both sources required'], 400);
        }
        
        try {
            $comparisonService = app(SourceComparisonService::class);
            $comparison = $comparisonService->compareSources($source1, $source2);
            
            return response()->json([
                'success' => true,
                'comparison' => $comparison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Compare multiple sources
     * POST /api/compare/multiple
     */
    public function compareMultipleSources(Request $request)
    {
        $sources = $request->input('sources', []);
        
        if (count($sources) < 2) {
            return response()->json(['success' => false, 'error' => 'At least 2 sources required'], 400);
        }
        
        try {
            $comparisonService = app(SourceComparisonService::class);
            $comparison = $comparisonService->compareMultipleSources($sources);
            
            return response()->json([
                'success' => true,
                'comparison' => $comparison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
