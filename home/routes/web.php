<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EcosystemController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\LaunchImageController;
use App\Http\Controllers\DownloadController;

// Include authentication routes
require __DIR__.'/auth.php';

// Main search routes (Public)
Route::get('/test', function() { return 'ok'; });

// ── Agentic Browser as Homepage ──
Route::get('/', [SearchController::class, 'browser'])->name('browser.home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search-home', [SearchController::class, 'index'])->name('search.home');

// ── Dynamic PWA Manifest (reads from Master Panel Browser Settings) ──
Route::get('/site.webmanifest', [ManifestController::class, 'index'])->name('pwa.manifest');

// ── iOS PWA Launch Image Generator ──
Route::get('/icons/launch-{size}.png', [LaunchImageController::class, 'serve'])->name('pwa.launch_image');
Route::get('/icons/launch-generate-all', [LaunchImageController::class, 'generateAll'])->name('pwa.launch_generate');

// ── Agentic Browser proxy routes ──
Route::get('/browse', [SearchController::class, 'browse'])->name('browser.browse');
Route::get('/browser', [SearchController::class, 'browser'])->name('browser.home_legacy');

Route::get('/browse/resource', [SearchController::class, 'browseResource'])
    ->middleware('throttle:300,1')  // Higher limit: images/CSS/JS are numerous
    ->name('browser.resource');

// POST form submission via the browse-nav-worker (iframe-intercepted forms)
Route::post('/browse/submit', [SearchController::class, 'browserSubmit'])
    ->middleware('throttle:60,1')
    ->name('browser.submit');

// Blocked-popup resolver — worker sends intercepted window.open URLs here
Route::get('/browse/popup', [SearchController::class, 'browsePopup'])
    ->middleware('throttle:60,1')
    ->name('browser.popup');

// Structured-JSON page API — Phase 2 (REST data protocol for each worker renderer)
Route::get('/browse/api/page', [SearchController::class, 'browseApiPage'])
    ->middleware('throttle:120,1')
    ->name('browser.api_page');

// ── AI Agent routes ──
Route::get('/agent/settings', [AgentController::class, 'settings'])->name('agent.settings');
Route::post('/agent/settings', [AgentController::class, 'saveSettings'])->name('agent.settings.save');
Route::post('/agent/run', [AgentController::class, 'run'])
    ->middleware('throttle:30,1')  // 30 agent runs per minute
    ->name('agent.run');
Route::get('/agent/status', [AgentController::class, 'status'])
    ->middleware('throttle:60,1')
    ->name('agent.status');

// Autocomplete — throttled to prevent scraping
Route::get('/api/search/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('search.suggestions');

// Click tracking — throttled
Route::post('/search/track-click', [SearchController::class, 'trackClick'])
    ->middleware('throttle:120,1')
    ->name('search.track.click');

// Voice search
Route::post('/search/voice', [SearchController::class, 'voiceSearch'])
    ->middleware('throttle:10,1')
    ->name('search.voice');

// Feeling lucky — throttled to prevent redirect abuse
Route::get('/search/lucky', [SearchController::class, 'feelingLucky'])
    ->middleware('throttle:30,1')
    ->name('search.lucky');


// Ecosystem API routes (public — consumed by frontend JS)
Route::get('/api/ecosystem/apps', [EcosystemController::class, 'apps'])->name('ecosystem.apps');
Route::get('/api/ecosystem/health', [EcosystemController::class, 'health'])->name('ecosystem.health');
Route::get('/api/ecosystem/theme', [EcosystemController::class, 'theme'])->name('ecosystem.theme');

// SSO routes (public)
// Note: /callback must be GET — the account service issues a redirect (not a POST)
//       back to this URL with ?token=xxx in the query string.
Route::get('/login', [SSOController::class, 'initiate'])->name('login');
Route::prefix('sso')->group(function () {
    Route::get('/initiate', [SSOController::class, 'initiate'])->name('sso.initiate');
    Route::get('/callback', [SSOController::class, 'callback'])->name('sso.callback');
    Route::get('/logout', [SSOController::class, 'logout'])->name('sso.logout');
});

// ── Download routes ──
Route::prefix('downloads')->group(function () {
    Route::get('/YGXONE-Browser-Windows.exe', function () {
        return app(DownloadController::class)->downloadDesktopApp(request(), 'windows');
    })->name('download.desktop.windows');
    
    Route::get('/YGXONE-Browser-macOS.dmg', function () {
        return app(DownloadController::class)->downloadDesktopApp(request(), 'macos');
    })->name('download.desktop.macos');
    
    Route::get('/YGXONE-Browser-Linux.AppImage', function () {
        return app(DownloadController::class)->downloadDesktopApp(request(), 'linux');
    })->name('download.desktop.linux');
    
    // Alternative route for download page
    Route::get('/', [DownloadController::class, 'showDownloadsPage'])->name('downloads.page');
});

// Health check (public)
Route::get('/up', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'search_index' => false,
    ];
    
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        Log::error('Health check: Database connection failed', ['error' => $e->getMessage()]);
    }
    
    try {
        Cache::put('health_check', 'ok', 10);
        $checks['cache'] = Cache::get('health_check') === 'ok';
    } catch (\Exception $e) {
        Log::error('Health check: Cache failed', ['error' => $e->getMessage()]);
    }
    
    try {
        $indexedCount = \App\Models\IndexedItem::count();
        $checks['search_index'] = $indexedCount > 0;
        $checks['indexed_items_count'] = $indexedCount;
    } catch (\Exception $e) {
        Log::error('Health check: Search index failed', ['error' => $e->getMessage()]);
    }
    
    $allHealthy = collect($checks)->only(['database', 'cache', 'search_index'])->every(fn ($v) => $v === true);
    
    return response()->json([
        'status'    => $allHealthy ? 'healthy' : 'degraded',
        'timestamp' => now()->toIso8601String(),
        'checks'    => $checks,
    ], $allHealthy ? 200 : 503);
})->name('health.check');

// Admin routes redirect to master portal
Route::get('/admin', function () {
    return redirect('https://master.ygxone.com/admin');
});

// Admin routes (protected with authentication and admin role)
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/cache/clear', [AdminController::class, 'clearCache'])
        ->middleware('throttle:5,1') // 5 requests per minute
        ->name('admin.cache.clear');
    Route::post('/rebuild-index', [AdminController::class, 'rebuildIndex'])
        ->middleware('throttle:2,1') // 2 requests per minute (resource-intensive)
        ->name('admin.rebuild');
    Route::post('/sync-modules', [AdminController::class, 'syncModules'])
        ->middleware('throttle:3,1') // 3 requests per minute
        ->name('admin.sync');
    // Ecosystem admin routes
    Route::get('/ecosystem/stats', [EcosystemController::class, 'stats'])
        ->name('admin.ecosystem.stats');
    Route::post('/ecosystem/clear-cache', [EcosystemController::class, 'clearCache'])
        ->middleware('throttle:5,1')
        ->name('admin.ecosystem.clear');
    Route::get('/ecosystem/theme-preview', [EcosystemController::class, 'themePreview'])
        ->name('admin.ecosystem.theme-preview');
});

require __DIR__.'/api.php';