<?php

namespace App\Http\Controllers;

use App\Services\UnifiedSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private UnifiedSearchService $searchService;
    
    public function __construct(UnifiedSearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    /**
     * Main search page (Google-style)
     */
    public function index(Request $request)
    {
        try {
            $query = substr(strip_tags($request->input('q', '')), 0, 500);
            $type  = in_array($request->input('type'), ['all','web','mail','drive','docs','contacts','calendar','chat'], true)
                ? $request->input('type') : 'all';
            
            // Pagination parameters
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(50, max(10, (int) $request->input('per_page', 20))); // Default 20, range 10-50
            
            if (empty($query)) {
                return view('search.home', [
                    'trending' => $this->getTrendingSearches(),
                    'recent' => $this->getUserRecentSearches(),
                ]);
            }
            // Use the unified search service
            $searchResults = $this->searchService->search($query, [
                'type' => $type,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            // Track search for analytics (only on first page)
            if ($page === 1) {
                $this->trackSearch($query, $searchResults);
            }
            
            // Re-structure data to match the view's expectations
            $resultsData = [
                'web' => $searchResults['web'],
                'ecosystem' => $searchResults['ecosystem'],
                'ai_enhanced' => $searchResults['ai_enhanced'],
            ];

            // Align pagination names (current_page instead of page)
            $pagination = $searchResults['meta'];
            $pagination['current_page'] = $pagination['page'];

            return view('search.results', [
                'query'         => $query,
                'type'          => $type,
                'results'       => $resultsData,
                'total_results' => $searchResults['meta']['total'],
                'suggestions'   => $searchResults['suggestions'],
                'pagination'    => $pagination,
            ]);
        } catch (\Exception $e) {
            Log::error('Search failed', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->view('search.results', [
                'query' => $query ?? '',
                'type' => $type ?? 'all',
                'results' => ['web' => [], 'ecosystem' => [], 'ai_enhanced' => null],
                'total_results' => 0,
                'suggestions' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1],
                'error' => 'Search is temporarily unavailable. Please try again.',
            ], 500);
        }
    }
    
    /**
     * API endpoint for autocomplete suggestions
     */
    public function suggestions(Request $request)
    {
        $query = substr(strip_tags($request->input('q', '')), 0, 200);
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }
        
        // Get suggestions from the service
        return response()->json($this->searchService->getAISuggestions($query));
    }
    
    /**
     * Track when user clicks on a result (for training)
     */
    public function trackClick(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'result_url' => 'required|url',
            'result_type' => 'required|in:web,mail,drive,docs,contacts',
            'position' => 'required|integer|min:1',
        ]);
        
        // Update training data with click feedback
        try {
            DB::table('search_training_data')
                ->where('query', $request->input('query'))
                ->where('session_id', session()->getId())
                ->latest()
                ->first()
                ?->update(['had_clicks' => true]);
        } catch (\Exception $e) {
            \Log::error('Failed to track click', ['error' => $e->getMessage()]);
        }
        
        // Boost this result in ranking algorithm
        $this->boostResultRanking(
            $request->input('result_url'),
            $request->input('result_type'),
            $request->input('position')
        );
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Voice search endpoint
     */
    public function voiceSearch(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,mp4,wav',
        ]);
        
        // For now, redirect - speech-to-text would require additional setup
        return response()->json([
            'message' => 'Voice search requires browser Web Speech API support',
        ], 501);
    }
    
    /**
     * "I'm Feeling Lucky" - go directly to first result
     */
    public function feelingLucky(Request $request)
    {
        $query = substr(strip_tags($request->input('q', '')), 0, 500);

        if (empty($query)) {
            return redirect()->route('search.index');
        }

        $results = $this->searchService->search($query, ['type' => 'web']);

        if (!empty($results['web'][0]['url'])
            && filter_var($results['web'][0]['url'], FILTER_VALIDATE_URL)
            && str_starts_with($results['web'][0]['url'], 'https://')) {
            return redirect($results['web'][0]['url']);
        }

        return redirect()->route('search.index', ['q' => $query]);
    }
    
    /**
     * Get trending searches
     */
    private function getTrendingSearches(): array
    {
        try {
            return DB::table('trending_searches')
                ->where('trend_date', '>=', now()->subDays(7))
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('query')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get user's recent searches
     */
    private function getUserRecentSearches(): array
    {
        if (!auth()->check()) {
            return [];
        }
        
        try {
            return DB::table('search_training_data')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->pluck('query')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Track search query
     */
    private function trackSearch(string $query, array $results): void
    {
        // Update trending searches
        try {
            DB::table('trending_searches')->updateOrInsert(
                ['query' => $query, 'trend_date' => now()->format('Y-m-d')],
                ['count' => DB::raw('count + 1')]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to track search', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Count total results
     */
    private function countTotalResults(array $results): int
    {
        return collect($results)->sum(fn ($r) => is_array($r) ? count($r) : 0);
    }
    
    /**
     * Boost result ranking based on clicks
     */
    private function boostResultRanking(string $url, string $type, int $position): void
    {
        try {
            DB::table('search_result_rankings')->updateOrInsert(
                ['url' => $url, 'result_type' => $type],
                [
                    'click_count' => DB::raw('click_count + 1'),
                    'impression_count' => DB::raw('impression_count + 1'),
                    'ranking_score' => DB::raw('ranking_score + 0.1'),
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to boost ranking', ['error' => $e->getMessage()]);
        }
    }
}
