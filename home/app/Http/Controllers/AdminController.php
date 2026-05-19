<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        try {
            // Get today's stats
            $today = Carbon::today();
            
            $stats = [
                'total_searches_today' => DB::table('search_training_data')
                    ->whereDate('created_at', $today)
                    ->count(),
                
                'zero_result_rate' => $this->calculateZeroResultRate(7),
                
                'click_through_rate' => $this->calculateClickThroughRate(7),
                
                'total_indexed_items' => DB::table('indexed_items')->count(),
                
                'active_users_7d' => DB::table('search_training_data')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->distinct('user_id')
                    ->count('user_id'),
                
                'avg_response_time' => $this->getAverageResponseTime(),
            ];
            
            // Get trending queries (last 7 days)
            $trendingQueries = DB::table('search_training_data')
                ->select('query', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('query')
                ->orderBy('count', 'desc')
                ->limit(15)
                ->get();
            
            // Get zero-result queries (content gaps)
            $zeroResultQueries = DB::table('search_training_data')
                ->select('query', DB::raw('COUNT(*) as occurrences'))
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->where('result_count', 0)
                ->groupBy('query')
                ->orderBy('occurrences', 'desc')
                ->limit(10)
                ->get();
            
            // Get indexed items by service
            $indexedByService = DB::table('indexed_items')
                ->select('service', DB::raw('COUNT(*) as count'))
                ->groupBy('service')
                ->get();
            
            // Search volume chart data (last 30 days)
            $searchVolumeData = DB::table('search_training_data')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Top clicked results
            $topClickedResults = DB::table('search_result_rankings')
                ->orderBy('click_count', 'desc')
                ->limit(10)
                ->get(['url', 'result_type', 'click_count', 'impression_count']);
            
            // Pass ecosystem data for admin dashboard
            $ecoService = app(\App\Services\EcosystemService::class);
            $allApps = $ecoService->getAllApps();
            $ecosystemEngine = config('services.master_panel.url', 'https://master.ygxone.com/admin');
            
            return view('admin.dashboard', compact(
                'stats',
                'trendingQueries',
                'zeroResultQueries',
                'indexedByService',
                'searchVolumeData',
                'topClickedResults',
                'allApps',
                'ecosystemEngine'
            ));
        } catch (\Exception $e) {
            \Log::error('Admin dashboard failed', ['error' => $e->getMessage()]);
            
            return view('admin.dashboard', [
                'stats' => [
                    'total_searches_today' => 0,
                    'zero_result_rate' => 0,
                    'click_through_rate' => 0,
                    'total_indexed_items' => 0,
                    'active_users_7d' => 0,
                    'avg_response_time' => 'N/A',
                ],
                'trendingQueries' => collect([]),
                'zeroResultQueries' => collect([]),
                'indexedByService' => collect([]),
                'searchVolumeData' => collect([]),
                'topClickedResults' => collect([]),
                'error' => 'Failed to load dashboard data. Please check database connection.',
            ]);
        }
    }
    
    /**
     * Calculate zero result rate percentage
     */
    private function calculateZeroResultRate(int $days): float
    {
        $total = DB::table('search_training_data')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->count();
        
        if ($total === 0) return 0;
        
        $zeroResults = DB::table('search_training_data')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->where('result_count', 0)
            ->count();
        
        return round(($zeroResults / $total) * 100, 2);
    }
    
    /**
     * Calculate click-through rate percentage
     */
    private function calculateClickThroughRate(int $days): float
    {
        $total = DB::table('search_training_data')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->count();
        
        if ($total === 0) return 0;
        
        $withClicks = DB::table('search_training_data')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->where('had_clicks', true)
            ->count();
        
        return round(($withClicks / $total) * 100, 2);
    }
    
    /**
     * Get average response time (simulated - would need actual metrics)
     */
    private function getAverageResponseTime(): string
    {
        // In production, this would query actual performance metrics
        // For now, return a reasonable estimate
        return '~120ms';
    }
    
    /**
     * Clear search cache
     */
    public function clearCache()
    {
        Cache::flush();
        
        return redirect()->route('admin.dashboard')
            ->with('success', 'Search cache cleared successfully!');
    }
    
    /**
     * Rebuild search index
     */
    public function rebuildIndex()
    {
        try {
            \Artisan::call('scout:import', ['model' => 'App\Models\IndexedItem']);
            
            return redirect()->route('admin.dashboard')
                ->with('success', 'Search index rebuilt successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to rebuild index: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync all ecosystem modules
     */
    public function syncModules()
    {
        try {
            \Artisan::call('search:sync');
            
            return redirect()->route('admin.dashboard')
                ->with('success', 'All modules synced successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to sync modules: ' . $e->getMessage());
        }
    }
}
