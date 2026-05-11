<?php

namespace App\Services;

use App\Models\IndexedItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;


class UnifiedSearchService
{
    /**
     * Perform unified search across all services
     */
    public function search(string $query, array $filters = []): array
    {
        $cacheKey = 'search:' . md5($query . ':' . json_encode($filters));
        return Cache::remember($cacheKey, 300, function () use ($query, $filters) {
            return $this->executeSearch($query, $filters);
        });
    }

    private function executeSearch(string $query, array $filters = []): array
    {
        $type = $filters['type'] ?? 'all';
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;

        $results = [
            'web' => [],
            'ecosystem' => [],
            'suggestions' => [],
            'ai_enhanced' => null,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
            ],
        ];

        // Gather suggestions regardless of type
        $results['suggestions'] = $this->getAISuggestions($query);

        // Web search (Handles All, Web, News, Images, etc.)
        if (in_array($type, ['all', 'web', 'news', 'images', 'videos', 'shopping', 'ai'])) {
            $web = $this->searchWeb($query, $page, $perPage, $type);
            $results['web'] = $web['results'];
            $results['meta']['total'] = $web['total'];
            $results['meta']['last_page'] = $web['last_page'];
        }

        // Ecosystem (internal) search
        if ($type === 'all' || $type === 'ecosystem') {
            $results['ecosystem'] = $this->searchEcosystem($query, $type);
        }

        \Log::info('Search execution summary', [
            'query' => $query,
            'web_count' => count($results['web']),
            'ecosystem_count' => count($results['ecosystem']),
            'total_meta' => $results['meta']['total']
        ]);

        // AI enhanced answer (only for complex queries)
        if ($this->shouldGenerateAIAnswer($query)) {
            $results['ai_enhanced'] = $this->generateAIAnswer($query, $results);
        }

        // 5. Train YugaLLM with this search query (async, not cached)
        dispatch(function () use ($query, $results) {
            $this->trainAIWithSearchQuery($query, $results);
        })->onQueue('default');
        
        return $results;
    }

    
    /**
     * Search web pages — uses DuckDuckGo and Wikipedia
     */
    public function searchWeb(string $query, int $page = 1, int $perPage = 20, string $type = 'all'): array
    {
        $query = trim($query);
        if (!$query) return ['results' => [], 'total' => 0];

        // Adjust query based on type for better relevance
        $effectiveQuery = $query;
        if ($type === 'news') $effectiveQuery .= ' news';
        if ($type === 'images') $effectiveQuery .= ' images';
        if ($type === 'videos') $effectiveQuery .= ' videos';
        if ($type === 'shopping') $effectiveQuery .= ' shopping';

        $results = [];
        
        try {
            // Source 1: DuckDuckGo (Free HTML Search)
            $ddgResults = $this->searchDuckDuckGo($effectiveQuery, $perPage);
            foreach ($ddgResults as $res) $results[] = $res;

            // Source 2: Wikipedia (Factual Knowledge)
            if (count($results) < $perPage) {
                $wikiResults = $this->searchWikipedia($query, 5);
                foreach ($wikiResults as $res) $results[] = $res;
            }

            // Deduplicate and slice
            $uniqueResults = [];
            foreach ($results as $res) {
                $url = rtrim($res['url'], '/');
                if (!isset($uniqueResults[$url])) {
                    $uniqueResults[$url] = $res;
                }
            }
            $finalResults = array_values($uniqueResults);
            
            $totalCount = count($finalResults) > 0 ? 100 : 0;
            return [
                'results' => array_slice($finalResults, ($page - 1) * $perPage, $perPage),
                'total' => $totalCount,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($totalCount / $perPage) ?: 1,
            ];

        } catch (\Exception $e) {
            \Log::error('Web search failed', ['error' => $e->getMessage()]);
            return ['results' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'last_page' => 1];
        }
    }

    private function searchDuckDuckGo(string $query, int $limit): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
                ]
            ]);
            
            $url = 'https://duckduckgo.com/lite/?q=' . urlencode($query);
            $response = $client->get($url);
            $html = (string) $response->getBody();
            \Log::info('DDG Scraper HTML received', ['length' => strlen($html)]);
            
            $results = [];
            // Even more robust regex for DDG Lite results
            // Look for links and snippets in the table structure
            preg_match_all(
                '/<a[^>]+class=["\']result-link["\'][^>]+href=["\'](.*?)["\'][^>]*>(.*?)<\/a>.*?<td[^>]+class=["\']result-snippet["\'][^>]*>(.*?)<\/td>/is',
                $html,
                $matches,
                PREG_SET_ORDER
            );

            if (empty($matches)) {
                \Log::warning('DDG Scraper: No primary matches, trying fallback regex');
                // Fallback: More generic matching
                preg_match_all(
                    '/<a[^>]+href=["\'](.*?)["\'][^>]+class=["\']result-link["\'][^>]*>(.*?)<\/a>.*?<td[^>]+class=["\']result-snippet["\'][^>]*>(.*?)<\/td>/is',
                    $html,
                    $matches,
                    PREG_SET_ORDER
                );
            }

            if (empty($matches)) {
                \Log::error('DDG Scraper: All regex failed to match HTML');
                // One more try: just any link with result-link class
                preg_match_all('/class="result-link" href="(.*?)">(.*?)<\/a>/i', $html, $matches_simple);
                \Log::info('DDG Scraper simple link check', ['count' => count($matches_simple[0] ?? [])]);
            }

            \Log::info('DDG Scraper final matches count', ['count' => count($matches)]);

            foreach ($matches as $m) {
                $url = html_entity_decode(trim($m[1]));
                if (str_contains($url, 'duckduckgo.com')) continue;
                
                // Extract domain from URL
                $parsedUrl = parse_url($url);
                $domain = $parsedUrl['host'] ?? 'unknown.com';
                
                $snippet = html_entity_decode(strip_tags($m[3]));
                
                // Filter out common "meta text" from search engines
                $metaPatterns = [
                    '/Missing: .*?\| Must include: .*?/',
                    '/\d+ (hour|min|day)s? ago/',
                    '/... \d+ [a-zA-Z]+ \d+ — /',
                    '/Search results for: /i',
                ];
                $snippet = preg_replace($metaPatterns, '', $snippet);
                $snippet = trim($snippet, " \t\n\r\0\x0B-—");

                $results[] = [
                    'type' => 'web',
                    'title' => html_entity_decode(strip_tags($m[2])),
                    'url' => $url,
                    'snippet' => $snippet,
                    'domain' => $domain,
                    'favicon' => "https://www.google.com/s2/favicons?domain={$domain}",
                ];
                if (count($results) >= $limit) break;
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function searchWikipedia(string $query, int $limit): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 8,
                'headers' => [
                    'User-Agent' => 'YGXONE Search/1.0 (https://ygxone.com; contact@ygxone.com) Guzzle/7'
                ]
            ]);
            $response = $client->get('https://en.wikipedia.org/w/api.php?' . http_build_query([
                'action' => 'query',
                'format' => 'json',
                'prop' => 'extracts|pageimages',
                'generator' => 'search',
                'gsrsearch' => $query,
                'gsrlimit' => $limit,
                'exsentences' => 3,
                'exintro' => 1,
                'explaintext' => 1,
                'piprop' => 'thumbnail',
                'pithumbsize' => 100
            ]));
            $data = json_decode((string) $response->getBody(), true);
            \Log::info('Wikipedia API response received', ['count' => count($data['query']['pages'] ?? [])]);

            $results = [];
            foreach ($data['query']['pages'] ?? [] as $page) {
                $results[] = [
                    'type' => 'web',
                    'title' => $page['title'],
                    'url' => 'https://en.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $page['title'])),
                    'snippet' => $page['extract'] ?? 'No description available.',
                    'domain' => 'wikipedia.org',
                    'favicon' => 'https://www.google.com/s2/favicons?domain=wikipedia.org',
                    'image' => $page['thumbnail']['source'] ?? null,
                ];
            }
            return $results;
        } catch (\Exception $e) {
            \Log::error('Wikipedia search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Search ecosystem content using the unified index
     */
    public function searchEcosystem(string $query, string $type): array
    {
        try {
            $search = IndexedItem::search($query);

            if ($type !== 'all') {
                $search->where('service', $type);
            }

            // Ensure user only sees their own data
            if (auth()->check()) {
                $search->where('user_id', auth()->id());
            }

            $results = $search->take(20)->get();

            return $results->map(fn ($item) => [
                'type' => $item->service,
                'title' => $item->title,
                'snippet' => $this->highlightQuery($item->snippet ?? '', $query),
                'url' => $item->url,
                'metadata' => $item->metadata,
                'icon' => $this->getServiceIcon($item->service, $item->metadata['mime_type'] ?? null),
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('Ecosystem search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Helper: Get service icon
     */
    private function getServiceIcon(string $service, ?string $mimeType = null): string
    {
        if ($service === 'drive' && $mimeType) {
            return $this->getFileIcon($mimeType);
        }

        return match ($service) {
            'mail'     => 'envelope',
            'drive'    => 'cloud',
            'docs'     => 'file-alt',
            'contacts' => 'user',
            'calendar' => 'calendar',
            'chat'     => 'comments',
            default    => 'search',
        };
    }

    
    /**
     * Get AI-powered suggestions (public method)
     */
    public function getAISuggestions(string $query): array
    {
        return [
            'history' => $this->getUserSearchHistory($query),
            'trending' => $this->getTrendingMatches($query),
            'ai' => $this->getAIPredictions($query),
        ];
    }
    
    /**
     * Get AI-powered search suggestions
     */
    private function getAIPredictions(string $query): array
    {
        $ygAiApiUrl = config('services.yg_ai.api_url');
        $apiKey = config('services.yg_ai.api_key');
        
        if (!$ygAiApiUrl || !$apiKey) {
            return $this->getKeywordSuggestions($query);
        }
        
        try {
            $safeQuery = mb_substr(strip_tags($query), 0, 200);
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->timeout(3)->post("{$ygAiApiUrl}/", [
                'action'   => 'brain_chat',
                'question' => 'Suggest 5 related search queries for: ' . $safeQuery . '. Return each suggestion on a new line.',
                'model'    => 'default',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $suggestions = explode("\n", trim($data['answer'] ?? ''));
                return array_filter(array_slice($suggestions, 0, 5));
            }
        } catch (\Exception $e) {
            \Log::error('AI suggestions failed', ['error' => $e->getMessage()]);
        }
        
        // Fallback: simple keyword-based suggestions
        return $this->getKeywordSuggestions($query);
    }
    
    /**
     * Generate AI-enhanced answer for complex queries
     */
    private function generateAIAnswer(string $query, array $results): ?array
    {
        if ($this->isNavigationalQuery($query)) {
            return null;
        }
        
        $ygAiApiUrl = config('services.yg_ai.api_url');
        $apiKey = config('services.yg_ai.api_key');
        
        if (!$ygAiApiUrl || !$apiKey) {
            return null;
        }
        
        try {
            // Gather context from top results — handle empty gracefully
            $context = "";
            if (!empty($results['web'] ?? [])) {
                $context = collect($results['web'])
                    ->take(3)
                    ->map(fn ($r) => strip_tags($r['snippet'] ?? ''))
                    ->implode("\n\n");
            }

            $safeQuery = mb_substr(strip_tags($query), 0, 200);

            $message = $context 
                ? "Context: " . $context . "\n\nQuestion: " . $safeQuery
                : "Question: " . $safeQuery;
                
            $message .= "\n\nInstruction: Provide a concise factual summary. If this is a person or entity, include key facts (Born, Net Worth, Role) in bullet points at the end. Format the response for a Google-style Knowledge Card.";

            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->timeout(15)->post("{$ygAiApiUrl}/", [
                'action'   => 'chat',
                'message'  => $message,
                'model'    => 'default',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'answer' => $data['reply'] ?? $data['answer'] ?? '',
                    'sources' => collect($results['web'])->take(3)->pluck('url')->toArray(),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('AI answer generation failed', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Train YugaLLM with search query data
     */
    private function trainAIWithSearchQuery(string $query, array $results): void
    {
        // Log search query for training
        try {
            DB::table('search_training_data')->insert([
                'query' => $query,
                'result_count' => collect($results)->sum(fn ($r) => is_array($r) ? count($r) : 0),
                'had_clicks' => false,
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log search training data', ['error' => $e->getMessage()]);
        }
        
        // If user is authenticated, update search patterns
        if (auth()->check()) {
            try {
                DB::table('user_search_patterns')->updateOrInsert(
                    ['user_id' => auth()->id(), 'query_pattern' => $this->extractPattern($query)],
                    ['frequency' => DB::raw('frequency + 1'), 'last_searched' => now()]
                );
            } catch (\Exception $e) {
                \Log::error('Failed to update search patterns', ['error' => $e->getMessage()]);
            }
        }
        
        // Asynchronously send to YugaLLM for incremental learning
        dispatch(function () use ($query, $results) {
            $this->sendToYugaLLMTraining($query, $results);
        })->onQueue('default');
    }
    
    /**
     * Send search data to YugaLLM for training
     */
    private function sendToYugaLLMTraining(string $query, array $results): void
    {
        $ygAiApiUrl = config('services.yg_ai.api_url');
        $apiKey = config('services.yg_ai.api_key');
        
        if (!$ygAiApiUrl || !$apiKey) {
            return;
        }
        
        // Prepare training data from search results
        $trainingText = $this->prepareTrainingText($query, $results);
        
        try {
            Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->timeout(5)->post("{$ygAiApiUrl}/", [
                'action' => 'learn_text',
                'text' => $trainingText,
                'model' => 'default',
            ]);
        } catch (\Exception $e) {
            \Log::error('YugaLLM training failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Prepare training text from search results
     */
    private function prepareTrainingText(string $query, array $results): string
    {
        $text = "Search Query: {$query}\n\n";
        $text .= "Top Results:\n";
        
        foreach (collect($results['web'])->take(5) as $result) {
            $text .= "- {$result['title']}: {$result['snippet']}\n";
        }
        
        $text .= "\nRelated Searches:\n";
        foreach ($results['suggestions'] as $source => $items) {
            if (is_array($items)) {
                foreach (array_slice($items, 0, 3) as $item) {
                    $text .= "- [{$source}] {$item}\n";
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Highlight search query in text
     */
    private function highlightQuery(string $text, string $query): string
    {
        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }
    
    /**
     * Get keyword-based suggestions (fallback)
     */
    private function getKeywordSuggestions(string $query): array
    {
        $query = trim($query);
        $suggestions = [];
        
        // Natural search patterns
        if (str_word_count($query) <= 2) {
            $suggestions = [
                "{$query} news",
                "{$query} biography",
                "{$query} net worth",
                "{$query} company",
                "latest on {$query}",
            ];
        } else {
            $suggestions = [
                "{$query} meaning",
                "{$query} results",
                "more about {$query}",
                "{$query} explained",
                "{$query} facts",
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Check if query should generate AI answer
     */
    private function shouldGenerateAIAnswer(string $query): bool
    {
        // Don't generate for navigational queries
        if ($this->isNavigationalQuery($query)) {
            return false;
        }
        
        // Generate for questions, how-to queries, or entity searches (short noun phrases)
        if (preg_match('/\b(what|how|why|when|where|who|can|does|is|are|meaning|definition)\b/i', $query)) {
            return true;
        }

        // Also generate for entity-like queries (2-4 words)
        $words = count(explode(' ', trim($query)));
        return $words >= 1 && $words <= 5;
    }
    
    /**
     * Check if query is navigational (looking for specific site)
     */
    private function isNavigationalQuery(string $query): bool
    {
        return preg_match('/^(login|signin|signup|register|dashboard|admin)/i', $query);
    }
    
    /**
     * Extract search pattern for personalization
     */
    private function extractPattern(string $query): string
    {
        // Normalize query to pattern (e.g., "how to deploy laravel" -> "how to *")
        $words = explode(' ', strtolower($query));
        
        if (count($words) > 3) {
            return $words[0] . ' ' . $words[1] . ' *';
        }
        
        return $query;
    }
    
    /**
     * Helper: Get MIME type label
     */
    private function getMimeTypeLabel(string $mimeType): string
    {
        $types = [
            'application/pdf' => 'PDF Document',
            'image/jpeg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'text/plain' => 'Text File',
            'application/msword' => 'Word Document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document',
        ];
        
        return $types[$mimeType] ?? 'File';
    }
    
    /**
     * Get user search history matching query
     */
    public function getUserSearchHistory(string $query): array
    {
        if (!auth()->check()) {
            return [];
        }
        try {
            $safe = '%' . addcslashes($query, '%_\\') . '%';
            return DB::table('search_training_data')
                ->where('user_id', auth()->id())
                ->where('query', 'LIKE', $safe)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->pluck('query')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get trending searches matching query
     */
    public function getTrendingMatches(string $query): array
    {
        try {
            $safe = '%' . addcslashes($query, '%_\\') . '%';
            return DB::table('trending_searches')
                ->where('query', 'LIKE', $safe)
                ->orderBy('count', 'desc')
                ->limit(3)
                ->pluck('query')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Helper: Get file icon
     */
    private function getFileIcon(string $mimeType): string
    {
        if (str_contains($mimeType, 'pdf')) return 'file-pdf';
        if (str_contains($mimeType, 'image')) return 'file-image';
        if (str_contains($mimeType, 'word')) return 'file-word';
        if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) return 'file-excel';
        return 'file';
    }
    
    /**
     * Helper: Format bytes to human-readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
