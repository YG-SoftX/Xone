<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class YgSearchService
{
    protected $ygAiUrl;
    protected $ygAiApiKey;

    public function __construct()
    {
        // YG AI service URL (from config or .env)
        $this->ygAiUrl = config('services.yg_ai.url', env('YG_AI_URL', 'https://ai.ygxone.com'));
        $this->ygAiApiKey = config('services.yg_ai.api_key', env('YG_AI_API_KEY', ''));
    }

    /**
     * Perform intelligent web search with Gemini AI
     */
    public function searchWeb(string $query, array $options = []): array
    {
        $cacheKey = 'yg_search:web:' . md5($query . ':' . json_encode($options));
        
        return Cache::remember($cacheKey, 600, function () use ($query, $options) {
            return $this->executeWebSearch($query, $options);
        });
    }

    /**
     * Execute web search with YG AI enhancement
     */
    private function executeWebSearch(string $query, array $options = []): array
    {
        try {
            // Step 1: Get initial results from multiple sources via YG AI
            $searchResults = $this->fetchFromYgAi($query, $options);
            
            // Step 2: Enhance with YG AI intelligence
            $enhancedResults = $this->enhanceWithYgAi($query, $searchResults);
            
            // Step 3: Generate AI summary using YG AI
            $aiSummary = $this->generateAiSummary($query, $enhancedResults);
            
            // Step 4: Get related searches from YG AI
            $relatedSearches = $this->getRelatedSearches($query);
            
            // Step 5: Generate autocomplete suggestions via YG AI
            $suggestions = $this->getAutocompleteSuggestions($query);
            
            return [
                'query' => $query,
                'results' => $enhancedResults,
                'ai_summary' => $aiSummary,
                'related_searches' => $relatedSearches,
                'suggestions' => $suggestions,
                'total_results' => count($enhancedResults),
                'search_time' => number_format(microtime(true) - LARAVEL_START, 3),
            ];
        } catch (\Exception $e) {
            Log::error('YG Search Error: ' . $e->getMessage());
            
            return [
                'query' => $query,
                'results' => [],
                'ai_summary' => null,
                'error' => 'Search temporarily unavailable',
            ];
        }
    }

    /**
     * Fetch results from YG AI search API
     */
    private function fetchFromYgAi(string $query, array $options = []): array
    {
        try {
            // Call YG AI search endpoint
            $response = Http::timeout(10)->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->ygAiApiKey,
            ])->post("{$this->ygAiUrl}/api/", [
                'action' => 'web_search',
                'query' => $query,
                'tab' => $options['tab'] ?? 'all',
                'page' => $options['page'] ?? 1,
                'sources' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['ok']) && $data['ok']) {
                    return $this->transformYgAiResults($data);
                }
            }
            
            Log::warning('YG AI search failed: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('YG AI API call failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Transform YG AI response into standard format
     */
    private function transformYgAiResults(array $data): array
    {
        $results = [];
        
        // Extract organic results
        if (isset($data['organic'])) {
            foreach ($data['organic'] as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'url' => $item['link'] ?? '',
                    'snippet' => $item['snippet'] ?? '',
                    'source_type' => 'web',
                    'display_url' => $item['displayed_link'] ?? '',
                    'favicon' => $item['favicon'] ?? null,
                ];
            }
        }
        
        // Extract knowledge panel if available
        if (isset($data['knowledge_panel'])) {
            $results[] = [
                'title' => 'Knowledge Panel',
                'type' => 'knowledge_panel',
                'data' => $data['knowledge_panel'],
                'source_type' => 'knowledge',
            ];
        }
        
        // Extract related questions
        if (isset($data['related_questions'])) {
            foreach ($data['related_questions'] as $question) {
                $results[] = [
                    'title' => $question['question'] ?? '',
                    'snippet' => $question['answer'] ?? '',
                    'source_type' => 'related_question',
                ];
            }
        }
        
        return $results;
    }

    /**
     * Enhance search results with YG AI intelligence
     */
    private function enhanceWithYgAi(string $query, array $results): array
    {
        if (empty($results)) {
            return $results;
        }

        try {
            // Use YG AI to rank and score results
            $response = Http::timeout(5)->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->ygAiApiKey,
            ])->post("{$this->ygAiUrl}/api/", [
                'action' => 'rank_results',
                'query' => $query,
                'results' => collect($results)->take(10)->map(function($r) {
                    return [
                        'title' => $r['title'],
                        'url' => $r['url'],
                        'snippet' => substr($r['snippet'] ?? '', 0, 200),
                    ];
                })->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['ranked_results'])) {
                    // Apply AI ranking
                    $rankedUrls = $data['ranked_results'];
                    $urlOrder = array_flip($rankedUrls);
                    
                    usort($results, function($a, $b) use ($urlOrder) {
                        $posA = $urlOrder[$a['url']] ?? 999;
                        $posB = $urlOrder[$b['url']] ?? 999;
                        return $posA - $posB;
                    });
                    
                    // Add AI scores
                    foreach ($results as &$result) {
                        $result['ai_score'] = $data['scores'][$result['url']] ?? 50;
                        $result['is_top_source'] = $data['top_sources'][$result['url']] ?? false;
                    }
                    
                    return $results;
                }
            }
        } catch (\Exception $e) {
            Log::warning('YG AI enhancement failed: ' . $e->getMessage());
        }

        // Fallback: basic scoring
        return $this->applyBasicScoring($results);
    }

    /**
     * Apply basic scoring without AI
     */
    private function applyBasicScoring(array $results): array
    {
        foreach ($results as &$result) {
            // Simple scoring based on position and source type
            $result['ai_score'] = max(10, 100 - (array_search($result, $results) * 10));
            $result['is_top_source'] = false;
        }
        
        return $results;
    }

    /**
     * Generate AI-powered summary using YG AI
     */
    private function generateAiSummary(string $query, array $results): ?string
    {
        if (empty($results)) {
            return null;
        }

        try {
            $topResults = collect($results)->take(5)->map(function($r) {
                return "- {$r['title']}: " . substr($r['snippet'] ?? '', 0, 150);
            })->join("\n");

            $response = Http::timeout(5)->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->ygAiApiKey,
            ])->post("{$this->ygAiUrl}/api/", [
                'action' => 'summarize_search',
                'query' => $query,
                'results' => $topResults,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['summary'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('AI summary generation failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get related searches using YG AI
     */
    private function getRelatedSearches(string $query): array
    {
        try {
            $response = Http::timeout(3)->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->ygAiApiKey,
            ])->post("{$this->ygAiUrl}/api/", [
                'action' => 'related_searches',
                'query' => $query,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['related_searches']) && is_array($data['related_searches'])) {
                    return array_slice($data['related_searches'], 0, 8);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Related searches generation failed: ' . $e->getMessage());
        }

        return $this->getDefaultRelatedSearches($query);
    }

    /**
     * Default related searches (fallback)
     */
    private function getDefaultRelatedSearches(string $query): array
    {
        $words = explode(' ', $query);
        $related = [];
        
        // Generate variations
        $related[] = $query . ' tutorial';
        $related[] = $query . ' examples';
        $related[] = 'best ' . $query;
        $related[] = $query . ' vs alternatives';
        $related[] = 'how to ' . $query;
        $related[] = $query . ' 2026';
        $related[] = $query . ' review';
        $related[] = $query . ' guide';
        
        return array_slice($related, 0, 8);
    }

    /**
     * Get autocomplete suggestions from YG AI
     */
    private function getAutocompleteSuggestions(string $query): array
    {
        try {
            $response = Http::timeout(2)->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->ygAiApiKey,
            ])->post("{$this->ygAiUrl}/suggest.php", [
                'q' => $query,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['suggestions']) && is_array($data['suggestions'])) {
                    return array_slice($data['suggestions'], 0, 6);
                }
            }
        } catch (\Exception $e) {
            // Silent fail for autocomplete
        }

        return $this->getDefaultSuggestions($query);
    }

    /**
     * Default autocomplete suggestions
     */
    private function getDefaultSuggestions(string $query): array
    {
        $commonTerms = [
            'tutorial', 'examples', 'guide', 'tips', 'best',
            'review', '2026', 'free', 'online', 'download'
        ];
        
        return array_map(fn($term) => "$query $term", array_slice($commonTerms, 0, 6));
    }

    /**
     * Search YG Ecosystem (Mail, Drive, Docs, etc.)
     */
    public function searchEcosystem(string $query, string $userId): array
    {
        // This would integrate with other YG services via their APIs
        // For now, placeholder implementation
        
        return [
            'mail' => [],      // Search YG Mail
            'drive' => [],     // Search YG Drive
            'docs' => [],      // Search YG DocX
            'calendar' => [],  // Search YG Calendar
            'contacts' => [],  // Search YG Contacts
        ];
    }

    /**
     * Track search analytics
     */
    public function trackSearch(string $query, array $results, ?string $userId = null): void
    {
        // Log search for analytics and improvement
        // Store in database for ML training
        
        DB::table('search_analytics')->insert([
            'query' => $query,
            'user_id' => $userId,
            'results_count' => count($results),
            'search_time_ms' => round((microtime(true) - LARAVEL_START) * 1000),
            'created_at' => now(),
        ]);
    }
}
