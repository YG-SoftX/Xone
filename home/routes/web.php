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

// Include authentication routes
require __DIR__.'/auth.php';

// Main search routes (Public)
Route::get('/test', function() { return 'ok'; });
Route::get('/', [SearchController::class, 'index'])->name('search.home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// ── Agentic Browser routes ──
Route::get('/browser', [SearchController::class, 'browser'])->name('browser.home');
Route::get('/browse', [SearchController::class, 'browse'])
    ->middleware('throttle:120,1')  // Prevent open-proxy abuse
    ->name('browser.proxy');
Route::get('/browse/resource', [SearchController::class, 'browseResource'])
    ->middleware('throttle:300,1')  // Higher limit: images/CSS/JS are numerous
    ->name('browser.resource');

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