<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnifiedSearchService
{
    /**
     * Perform a unified search across the YG ecosystem.
     */
    public function search(string $query, array $options = []): array
    {
        $type = $options['type'] ?? 'all';
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = min(50, max(10, (int) ($options['per_page'] ?? 20)));

        try {
            // Search local indexed items
            $indexedQuery = DB::table('indexed_items')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%")
                      ->orWhere('metadata', 'LIKE', "%{$query}%");
                });

            // Filter by type if specified
            if ($type !== 'all' && $type !== 'web') {
                $indexedQuery->where('service', $type);
            }

            $total = $indexedQuery->count();
            $lastPage = max(1, (int) ceil($total / $perPage));

            $items = $indexedQuery
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('indexed_items', 'relevance_score'), function ($q) {
                    $q->orderBy('relevance_score', 'desc');
                })
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Build ecosystem results from indexed items
            $ecosystem = [];
            $web = [];

            foreach ($items as $item) {
                $result = [
                    'id'        => $item->id,
                    'title'     => $item->title,
                    'url'       => $item->url,
                    'snippet'   => Str::limit(strip_tags($item->content ?? ''), 300),
                    'type'      => $item->service ?? 'web',
                    'domain'    => parse_url($item->url, PHP_URL_HOST) ?? '',
                    'icon'      => $this->getServiceIcon($item->service),
                    'metadata'  => [
                        'date' => $item->created_at ?? now(),
                    ],
                ];

                if (in_array($item->service ?? 'web', ['mail', 'drive', 'docs', 'contacts', 'calendar', 'chat'])) {
                    $ecosystem[] = $result;
                } else {
                    $web[] = $result;
                }
            }

            // Generate AI-enhanced answer via cache (or fallback)
            $aiEnhanced = $this->getAIEnhancedAnswer($query, $items);

            // Generate suggestions
            $suggestions = $this->getSuggestions($query);

            return [
                'web'         => $web,
                'ecosystem'   => $ecosystem,
                'ai_enhanced' => $aiEnhanced,
                'meta'        => [
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'total'     => $total,
                    'last_page' => $lastPage,
                ],
                'suggestions' => $suggestions,
            ];
        } catch (\Exception $e) {
            Log::error('UnifiedSearchService::search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'web'         => [],
                'ecosystem'   => [],
                'ai_enhanced' => null,
                'meta'        => [
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'total'     => 0,
                    'last_page' => 1,
                ],
                'suggestions' => [],
            ];
        }
    }

    /**
     * Get AI-powered search suggestions (autocomplete).
     */
    public function getAISuggestions(string $query): array
    {
        try {
            // Get suggestions from trending searches + indexed items
            $trending = DB::table('trending_searches')
                ->where('query', 'LIKE', "%{$query}%")
                ->orderBy('count', 'desc')
                ->limit(5)
                ->pluck('query')
                ->toArray();

            $fromIndex = DB::table('indexed_items')
                ->where('title', 'LIKE', "%{$query}%")
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('indexed_items', 'relevance_score'), function ($q) {
                    $q->orderBy('relevance_score', 'desc');
                })
                ->orderBy('title')
                ->limit(5)
                ->pluck('title')
                ->toArray();

            $suggestions = array_unique(array_merge($trending, $fromIndex));

            return [
                'suggestions' => array_slice($suggestions, 0, 10),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get AI suggestions', ['query' => $query, 'error' => $e->getMessage()]);
            return ['suggestions' => []];
        }
    }

    /**
     * Generate or retrieve cached AI-enhanced answer for the query.
     */
    private function getAIEnhancedAnswer(string $query, iterable $items): ?array
    {
        $cacheKey = 'ai_answer_' . md5($query);

        return Cache::remember($cacheKey, 3600, function () use ($query, $items) {
            try {
                // Build a concise answer from available content
                $context = '';
                $count = 0;
                foreach ($items as $item) {
                    if ($count >= 3) break;
                    $context .= strip_tags($item->content ?? $item->title ?? '') . ' ';
                    $count++;
                }

                if (empty(trim($context))) {
                    $context = "Information about {$query}.";
                }

                $answer = Str::limit(trim($context), 500);

                return [
                    'answer'      => $answer ?: "I found information related to \"{$query}\" in the YG ecosystem. Try refining your search for more specific results.",
                    'sources'     => count($items) . ' indexed items found',
                    'confidence'  => count($items) > 0 ? 'high' : 'low',
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Generate related search suggestions.
     */
    private function getSuggestions(string $query): array
    {
        try {
            $related = DB::table('trending_searches')
                ->where('query', '!=', $query)
                ->where('count', '>', 1)
                ->orderBy('count', 'desc')
                ->limit(6)
                ->pluck('query')
                ->toArray();

            return [
                'ai'  => array_slice($related, 0, 3),
                'web' => array_slice($related, 3, 3),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the appropriate FontAwesome icon for a service type.
     */
    private function getServiceIcon(?string $service): string
    {
        return match ($service) {
            'mail'     => 'envelope',
            'drive'    => 'hard-drive',
            'docs'     => 'file-alt',
            'contacts' => 'address-book',
            'calendar' => 'calendar',
            'chat'     => 'comment',
            default    => 'globe',
        };
    }
}
