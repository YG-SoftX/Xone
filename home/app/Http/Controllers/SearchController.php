<?php

namespace App\Http\Controllers;

use App\Services\BrowserProxyService;
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
     * POST form submission proxy.
     *
     * Receives form data forwarded by the browse-nav-worker inside the
     * proxied iframe via postMessage, cURL-POSTs the form to the real
     * destination, rewrites the response, and returns rewritten HTML
     * that the worker applies as an in-place replacement.
     *
     * Request body (JSON):
     *   {
     *     "page_url":   "https://originating-page.example.com/login",
     *     "action":     "https://submit.example.com/login",
     *     "method":     "POST",
     *     "enctype":    "application/x-www-form-urlencoded",
     *     "data":       { "email": "...", "password": "..." }
     *   }
     */
    public function browserSubmit(Request $request)
    {
        $payload = $request->json()->all();

        $pageUrl = $payload['page_url'] ?? $request->input('page_url', '');
        $action  = $payload['action']  ?? $request->input('action', '');
        $method  = $payload['method']  ?? $request->input('method',  'POST');
        $enctype = $payload['enctype'] ?? $request->input('enctype', '');
        $data    = $payload['data']    ?? $request->input('data', []);

        if (empty($action) && empty($pageUrl)) {
            return response('No form target specified.', 400);
        }

        $targetUrl = $action ?: $pageUrl;

        try {
            $proxy = app(BrowserProxyService::class);
            $result = $proxy->submitForm($pageUrl, [
                'action'  => $targetUrl,
                'method'  => $method,
                'enctype' => $enctype,
                'data'    => is_array($data) ? $data : [],
            ]);

            return response($result['content'], $result['statusCode'])
                ->header('Content-Type', $result['contentType'])
                ->header('X-YG-Proxy-URL',  $result['url'])
                ->header('X-YG-Proxy-Title', $result['title'] ?? '')
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            Log::error("Browser form submit failed: " . $e->getMessage());
            return response('<p>Form submission error.</p>', 500)
                ->header('Content-Type', 'text/html');
        }
    }

    /**
     * Blocked-popup resolver.
     *
     * The browse-nav-worker calls this when it intercepts window.open().
     * Fetches the popup page through the proxy and returns rewritten HTML
     * so the outer app can open it in a fresh iframe or new tab.
     */
    public function browsePopup(Request $request)
    {
        $url = $request->input('url', '');
        if (empty($url)) {
            return response('No URL specified.', 400);
        }

        try {
            $proxy = app(BrowserProxyService::class);
            $result = $proxy->fetchPopupPage($url);

            return response($result['content'], $result['statusCode'])
                ->header('Content-Type', $result['contentType'])
                ->header('X-YG-Proxy-URL',  $result['url'])
                ->header('X-YG-Proxy-Title', $result['title'] ?? '')
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            Log::error("Browser popup fetch failed: " . $e->getMessage());
            return response('<p>Unable to load popup.</p>', 500)
                ->header('Content-Type', 'text/html');
        }
    }

    // ────────────────────────────────────────────────────────────────────
    //  REST Data Protocol — Phase 2
    // ────────────────────────────────────────────────────────────────────

    /**
     * GET /browse/api/page?url=…&shields=1&speed_reader=0
     *
     * Returns structured browser-state JSON.
     * Used by Phase 2 each worker renderer — consumes the DOM tree
     * (formatted) and renders it directly into the app shell DOM,
     * bypassing the iframe entirely for GET navigation.
     *     "url","title","statusCode","contentType",
     *     "dom":{...}, "text":"...", "styles":[...], "head":[...],
     *     "links":[...], "forms":[...], "scripts":[...], "images":[...],
     *     "meta":[...], "shields":{...}, "html":"<full rewritten html>"
     *   }
     */
    public function browseApiPage(Request $request)
    {
        $targetUrl    = $request->input('url', '');
        $shields      = $request->boolean('shields',        true);
        $speedReader  = $request->boolean('speed_reader',  false);

        if (empty($targetUrl)) {
            return response()->json(['error' => 'No URL provided.'], 400);
        }

        try {
            $proxy   = app(BrowserProxyService::class);
            $options = ['shields_enabled' => $shields];
            if ($speedReader) $options['speed_reader'] = true;

            $result = $proxy->fetch($targetUrl, $options);

            return response()->json([
                'url'         => $result['url'],
                'title'       => $result['title'],
                'statusCode'  => $result['statusCode'],
                'contentType' => $result['contentType'],
                'shields'     => $result['shields'] ?? [],
                'dom'         => $proxy->buildDomTree($result['content'], $result['url']),
                'text'        => $proxy->extractPlainText($result['content']),
                'styles'      => $proxy->extractStyles($result['content'], $result['url']),
                'head'        => $proxy->extractHead($result['content'], $result['url']),
                'links'       => $proxy->extractLinks($result['content'], $result['url']),
                'forms'       => $proxy->extractForms($result['content'], $result['url']),
                'scripts'     => $proxy->extractScripts($result['content'], $result['url']),
                'images'      => $proxy->extractImages($result['content'], $result['url']),
                'meta'        => $proxy->extractMetaTags($result['content']),
                'html'        => $result['content'],
            ])->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            Log::error("browseApiPage error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Count total results.
     */
    private function countTotalResults(array $results): int
    {
        return collect($results)->sum(fn ($r) => is_array($r) ? count($r) : 0);
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Agentic Browser Methods
    // ────────────────────────────────────────────────────────────────────

    /**
     * Agentic browser home page — shows the browser UI with omnibox,
     * ecosystem grid, and AI agent panel.
     */
    public function browser(Request $request)
    {
        $url = $request->input('url', '');
        $pageTitle = null;

        // If a URL is provided, pre-fetch the title for the tab
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            try {
                $proxy = app(BrowserProxyService::class);
                $result = $proxy->fetch($url);
                $pageTitle = $result['title'] ?? null;
            } catch (\Exception $e) {
                Log::warning("Browser: Failed to pre-fetch title for {$url}");
            }
        }

        return view('search.browser', [
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * Proxy endpoint — fetches a URL through BrowserProxyService and returns
     * the rewritten HTML content for rendering in the browser iframe.
     */
    public function browse(Request $request)
    {
        $targetUrl = $request->input('url', '');

        if (empty($targetUrl)) {
            return response('No URL provided.', 400);
        }

        // Redirect search queries (no dots, no protocol) to the ecosystem search
        if (!str_contains($targetUrl, '.') && !str_starts_with($targetUrl, 'http') && !str_starts_with($targetUrl, 'localhost')) {
            return redirect()->route('search.index', ['q' => $targetUrl]);
        }

        // Special handling for sites that block proxying
        $blockedDomains = [
            'google.com', 'www.google.com',
            'facebook.com', 'www.facebook.com',
            'twitter.com', 'x.com',
            'instagram.com',
            'linkedin.com',
            'netflix.com',
            'amazon.com',
        ];
        
        $parsedUrl = parse_url($targetUrl);
        $host = $parsedUrl['host'] ?? '';
        
        foreach ($blockedDomains as $blocked) {
            if (str_ends_with($host, $blocked)) {
                // Show a friendly message for blocked sites
                return response(
                    '<html>
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Site Cannot Be Proxied — YGXONE Browser</title>
                            <style>
                                body { 
                                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center; 
                                    min-height: 100vh; 
                                    margin: 0;
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                }
                                .container {
                                    text-align: center;
                                    padding: 40px;
                                    background: rgba(255,255,255,0.1);
                                    backdrop-filter: blur(10px);
                                    border-radius: 20px;
                                    max-width: 600px;
                                    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                                }
                                h2 { margin-top: 0; font-size: 28px; font-weight: 700; }
                                p { opacity: 0.95; line-height: 1.8; font-size: 16px; }
                                .icon { font-size: 64px; margin-bottom: 20px; }
                                .reasons {
                                    text-align: left;
                                    background: rgba(255,255,255,0.1);
                                    padding: 20px;
                                    border-radius: 12px;
                                    margin: 20px 0;
                                }
                                .reasons li { margin: 8px 0; }
                                .btn { 
                                    display: inline-block;
                                    color: white; 
                                    text-decoration: none; 
                                    padding: 14px 28px; 
                                    background: rgba(255,255,255,0.2);
                                    border-radius: 10px;
                                    margin-top: 20px;
                                    transition: all 0.3s;
                                    font-weight: 600;
                                    border: 2px solid rgba(255,255,255,0.3);
                                }
                                .btn:hover { 
                                    background: rgba(255,255,255,0.3);
                                    transform: translateY(-2px);
                                }
                                .alternative {
                                    margin-top: 20px;
                                    padding: 15px;
                                    background: rgba(255,255,255,0.05);
                                    border-radius: 10px;
                                    font-size: 14px;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="icon"></div>
                                <h2>This Site Cannot Be Loaded in Browser</h2>
                                <p><strong>' . htmlspecialchars($host) . '</strong> blocks proxy access for security reasons.</p>
                                
                                <div class="reasons">
                                    <strong>Why this happens:</strong>
                                    <ul>
                                        <li>The site uses anti-bot protection</li>
                                        <li>It detects and blocks proxy/iframe access</li>
                                        <li>Security policies prevent external framing</li>
                                    </ul>
                                </div>
                                
                                <div class="alternative">
                                    <strong>💡 Alternative:</strong> Open this site directly in a new tab:<br>
                                    <a href="' . htmlspecialchars($targetUrl) . '" target="_blank" style="color: #ffd700; word-break: break-all;">' . htmlspecialchars($targetUrl) . '</a>
                                </div>
                                
                                <a href="' . route('browser.home') . '" class="btn">← Back to YGXONE Browser</a>
                            </div>
                        </body>
                    </html>', 
                    403
                )->header('Content-Type', 'text/html');
            }
        }

        try {
            $proxy = app(BrowserProxyService::class);
            $result = $proxy->fetch($targetUrl);

            // Build response with headers that allow iframe embedding
            $response = response($result['content'], $result['statusCode'])
                ->header('Content-Type', $result['contentType'])
                ->header('X-Frame-Options', 'ALLOWALL')  // Allow iframe embedding
                ->header('Content-Security-Policy', "default-src * 'unsafe-inline' 'unsafe-eval'; frame-ancestors *;")
                ->header('X-YG-Proxy-URL', $result['url'])
                ->header('X-YG-Proxy-Title', $result['title'] ?? '')
                ->header('Access-Control-Allow-Origin', '*');  // Allow cross-origin requests

            // Log successful proxy for debugging
            Log::debug("Browser proxy success", [
                'url' => $targetUrl,
                'status' => $result['statusCode'],
                'title' => $result['title'] ?? null,
                'shields' => $result['shields'] ?? [],
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error("Browser proxy failed for {$targetUrl}: " . $e->getMessage());
            
            // Return a helpful error page
            return response(
                '<html>
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Page Load Failed — YGXONE Browser</title>
                        <style>
                            body { 
                                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                                display: flex; 
                                align-items: center; 
                                justify-content: center; 
                                min-height: 100vh; 
                                margin: 0;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                            }
                            .error-container {
                                text-align: center;
                                padding: 40px;
                                background: rgba(255,255,255,0.1);
                                backdrop-filter: blur(10px);
                                border-radius: 20px;
                                max-width: 500px;
                                box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                            }
                            h2 { margin-top: 0; font-size: 24px; }
                            p { opacity: 0.9; line-height: 1.6; }
                            .btn { 
                                color: white; 
                                text-decoration: none; 
                                padding: 12px 24px; 
                                background: rgba(255,255,255,0.2);
                                border-radius: 8px;
                                display: inline-block;
                                margin-top: 20px;
                                transition: all 0.3s;
                                border: 2px solid rgba(255,255,255,0.3);
                            }
                            .btn:hover { background: rgba(255,255,255,0.3); }
                            .icon { font-size: 48px; margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="error-container">
                            <div class="icon"></div>
                            <h2>Unable to Load Page</h2>
                            <p>The website could not be loaded through the proxy. This may be because:</p>
                            <ul style="text-align: left; opacity: 0.9;">
                                <li>The site blocks proxy access</li>
                                <li>The URL is invalid or unreachable</li>
                                <li>The site requires special authentication</li>
                            </ul>
                            <a href="' . route('browser.home') . '" class="btn">← Back to YGXONE Browser</a>
                        </div>
                    </body>
                </html>', 
                502
            )->header('Content-Type', 'text/html');
        }
    }

    /**
     * Resource proxy — fetches images, CSS, JS, fonts through the proxy
     * so they load correctly in the sandboxed iframe.
     */
    public function browseResource(Request $request)
    {
        $resourceUrl = $request->input('url', '');

        if (empty($resourceUrl)) {
            return response('No resource URL provided.', 400);
        }

        try {
            $proxy = app(BrowserProxyService::class);
            $result = $proxy->fetchResource($resourceUrl);

            if ($result['statusCode'] >= 400 || empty($result['content'])) {
                return response('', $result['statusCode'] ?: 404);
            }

            return response($result['content'], $result['statusCode'])
                ->header('Content-Type', $result['contentType'])
                ->header('Content-Length', $result['contentLength'])
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            Log::error("Browser resource proxy failed: " . $e->getMessage());
            return response('', 500);
        }
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
    
    // ────────────────────────────────────────────────────────────────────
    //  Advanced Agentic Features (Deep Research, Citations, Knowledge Graph)
    // ────────────────────────────────────────────────────────────────────

    /**
     * Execute deep research on a query
     * POST /api/research/deep
     */
    public function deepResearch(Request $request)
    {
        $query = $request->input('query', '');
        $maxPages = min(15, max(3, (int) $request->input('max_pages', 8)));
        
        if (empty($query)) {
            return response()->json(['success' => false, 'error' => 'Query is required'], 400);
        }
        
        try {
            $researchService = app(\App\Services\DeepResearchService::class);
            $result = $researchService->research($query, [
                'max_pages' => $maxPages,
                'include_citations' => true,
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Deep research failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Research failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get recent citations
     * GET /api/citations/recent
     */
    public function getCitations(Request $request)
    {
        $limit = min(50, max(5, (int) $request->input('limit', 10)));
        $style = $request->input('style', 'apa');
        
        try {
            $citationTracker = app(\App\Services\CitationTracker::class);
            
            if ($request->input('export')) {
                // Export formatted citations
                $formatted = $citationTracker->exportCitations($style);
                return response()->json([
                    'success' => true,
                    'format' => $style,
                    'citations' => $formatted,
                ]);
            }
            
            // Get recent citations
            $citations = $citationTracker->getRecent($limit);
            $stats = $citationTracker->getStats();
            
            return response()->json([
                'success' => true,
                'citations' => $citations,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate formatted citation
     * POST /api/citations/format
     */
    public function formatCitation(Request $request)
    {
        $citationId = $request->input('citation_id');
        $style = $request->input('style', 'apa');
        
        if (!$citationId) {
            return response()->json(['success' => false, 'error' => 'Citation ID required'], 400);
        }
        
        try {
            $citationTracker = app(\App\Services\CitationTracker::class);
            $formatted = $citationTracker->generateCitation($citationId, $style);
            
            return response()->json([
                'success' => true,
                'citation' => $formatted,
                'style' => $style,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Clear session citations
     * DELETE /api/citations/clear
     */
    public function clearCitations()
    {
        try {
            $citationTracker = app(\App\Services\CitationTracker::class);
            $citationTracker->clearSession();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get knowledge graph data
     * GET /api/knowledge-graph
     */
    public function getKnowledgeGraph(Request $request)
    {
        $limit = min(100, max(10, (int) $request->input('limit', 50)));
        
        try {
            $graphService = app(\App\Services\KnowledgeGraphService::class);
            $graphData = $graphService->getGraphData($limit);
            
            return response()->json([
                'success' => true,
                'graph' => $graphData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Find entity connections
     * GET /api/knowledge-graph/connections
     */
    public function getEntityConnections(Request $request)
    {
        $entityName = $request->input('entity', '');
        
        if (empty($entityName)) {
            return response()->json(['success' => false, 'error' => 'Entity name required'], 400);
        }
        
        try {
            $graphService = app(\App\Services\KnowledgeGraphService::class);
            $connections = $graphService->findConnections($entityName);
            
            return response()->json([
                'success' => true,
                'entity' => $connections['entity'] ?? null,
                'connections' => $connections['connections'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Search entities
     * GET /api/knowledge-graph/search
     */
    public function searchEntities(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type');
        
        if (empty($query)) {
            return response()->json(['success' => false, 'error' => 'Search query required'], 400);
        }
        
        try {
            $graphService = app(\App\Services\KnowledgeGraphService::class);
            $entities = $graphService->searchEntities($query, $type);
            
            return response()->json([
                'success' => true,
                'entities' => $entities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Clear knowledge graph session
     * DELETE /api/knowledge-graph/clear
     */
    public function clearKnowledgeGraph()
    {
        try {
            $graphService = app(\App\Services\KnowledgeGraphService::class);
            $graphService->clearSession();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // ────────────────────────────────────────────────────────────────────
    //  Remaining Features: Follow-up Suggestions, Visual Summaries, Password Manager
    // ────────────────────────────────────────────────────────────────────

    /**
     * Generate follow-up suggestions
     * POST /api/suggestions/generate
     */
    public function generateSuggestions(Request $request)
    {
        $task = $request->input('task', '');
        $context = $request->input('context', 'browsing');
        $results = $request->input('results', []);
        
        if (empty($task)) {
            return response()->json(['success' => false, 'error' => 'Task is required'], 400);
        }
        
        try {
            $suggestionService = app(\App\Services\FollowUpSuggestionService::class);
            $suggestions = $suggestionService->generateSuggestions($task, $results, $context);
            
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Track suggestion selection feedback
     * POST /api/suggestions/feedback
     */
    public function trackSuggestionFeedback(Request $request)
    {
        $taskId = $request->input('task_id');
        $selectedIndex = $request->input('selected_index');
        $helpful = $request->input('helpful', true);
        
        if (!$taskId || $selectedIndex === null) {
            return response()->json(['success' => false, 'error' => 'Missing parameters'], 400);
        }
        
        try {
            $suggestionService = app(\App\Services\FollowUpSuggestionService::class);
            $suggestionService->trackSelection($taskId, $selectedIndex, $helpful);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate visual summaries for current page
     * POST /api/visual-summary/generate
     */
    public function generateVisualSummary(Request $request)
    {
        $html = $request->input('html', '');
        $url = $request->input('url', '');
        
        if (empty($html) || empty($url)) {
            return response()->json(['success' => false, 'error' => 'HTML and URL required'], 400);
        }
        
        try {
            $visualService = app(\App\Services\VisualSummaryService::class);
            $visualizations = $visualService->detectAndGenerate($html, $url);
            
            return response()->json([
                'success' => true,
                'visualizations' => $visualizations,
                'count' => count($visualizations),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Save password for website
     * POST /api/passwords/save
     */
    public function savePassword(Request $request)
    {
        $url = $request->input('url', '');
        $username = $request->input('username', '');
        $password = $request->input('password', '');
        
        if (empty($url) || empty($username) || empty($password)) {
            return response()->json(['success' => false, 'error' => 'All fields required'], 400);
        }
        
        try {
            $passwordManager = app(\App\Services\PasswordManagerService::class);
            $userId = auth()->id();
            $success = $passwordManager->saveCredentials($url, $username, $password, $userId);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Password saved successfully' : 'Failed to save password',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get credentials for website
     * GET /api/passwords/get?url=...
     */
    public function getPassword(Request $request)
    {
        $url = $request->input('url', '');
        
        if (empty($url)) {
            return response()->json(['success' => false, 'error' => 'URL required'], 400);
        }
        
        try {
            $passwordManager = app(\App\Services\PasswordManagerService::class);
            $userId = auth()->id();
            $credentials = $passwordManager->getCredentials($url, $userId);
            
            if ($credentials) {
                return response()->json([
                    'success' => true,
                    'credentials' => [
                        'domain' => $credentials['domain'],
                        'username' => $credentials['username'],
                        // Don't send password in API response for security
                        'has_password' => true,
                    ],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No saved credentials found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * List all saved passwords
     * GET /api/passwords/list
     */
    public function listPasswords()
    {
        try {
            $passwordManager = app(\App\Services\PasswordManagerService::class);
            $userId = auth()->id();
            $passwords = $passwordManager->listSavedPasswords($userId);
            
            return response()->json([
                'success' => true,
                'passwords' => $passwords,
                'count' => count($passwords),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete saved password
     * DELETE /api/passwords/delete/{id}
     */
    public function deletePassword($id)
    {
        try {
            $passwordManager = app(\App\Services\PasswordManagerService::class);
            $userId = auth()->id();
            $success = $passwordManager->deletePassword((int) $id, $userId);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Password deleted' : 'Failed to delete',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Detect login form on page
     * POST /api/passwords/detect-form
     */
    public function detectLoginForm(Request $request)
    {
        $html = $request->input('html', '');
        $url = $request->input('url', '');
        
        if (empty($html) || empty($url)) {
            return response()->json(['success' => false, 'error' => 'HTML and URL required'], 400);
        }
        
        try {
            $passwordManager = app(\App\Services\PasswordManagerService::class);
            $forms = $passwordManager->detectLoginForm($html, $url);
            
            return response()->json([
                'success' => true,
                'forms' => $forms,
                'count' => count($forms),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
