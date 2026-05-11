<?php

namespace App\Services;

use App\Models\SearchIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedSearchService
{
    /**
     * Perform unified search across all ecosystem services
     * 
     * @param string $query Search query
     * @param array $filters Optional filters (type, date_range, service)
     * @param int $userId User ID for personalized results
     * @return array Aggregated search results
     */
    public function search(string $query, array $filters = [], ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        if (!$userId || empty($query)) {
            return [
                'results' => [],
                'total' => 0,
                'suggestions' => [],
            ];
        }

        $results = [
            'mail' => [],
            'drive' => [],
            'docs' => [],
            'xcel' => [],
            'contacts' => [],
            'calendar' => [],
            'chat' => [],
            'forms' => [],
        ];

        $type = $filters['type'] ?? 'all';
        $limit = $filters['limit'] ?? 10;

        // Search each service based on filter
        if ($type === 'all' || $type === 'mail') {
            $results['mail'] = $this->searchMail($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'drive') {
            $results['drive'] = $this->searchDrive($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'docs') {
            $results['docs'] = $this->searchDocs($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'xcel') {
            $results['xcel'] = $this->searchXcel($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'contacts') {
            $results['contacts'] = $this->searchContacts($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'calendar') {
            $results['calendar'] = $this->searchCalendar($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'chat') {
            $results['chat'] = $this->searchChat($query, $userId, $limit);
        }

        if ($type === 'all' || $type === 'forms') {
            $results['forms'] = $this->searchForms($query, $userId, $limit);
        }

        // Get AI-powered suggestions
        $suggestions = $this->getSuggestions($query);

        // Log search for analytics and AI training
        $this->logSearchQuery($query, $userId, $results);

        return [
            'results' => $results,
            'total' => array_sum(array_map('count', $results)),
            'suggestions' => $suggestions,
            'query' => $query,
        ];
    }

    /**
     * Index content from any service into unified search index
     */
    public function indexContent(string $service, string $itemType, int $itemId, array $data, ?int $userId = null): void
    {
        try {
            $userId = $userId ?? auth()->id();
            
            SearchIndex::updateOrCreate(
                [
                    'user_id' => $userId,
                    'service' => $service,
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                ],
                [
                    'title' => $data['title'] ?? '',
                    'content' => $data['content'] ?? '',
                    'metadata' => json_encode($data['metadata'] ?? []),
                    'indexed_at' => now(),
                ]
            );

            Log::info("Content indexed: {$service}.{$itemType}#{$itemId}");
        } catch (\Exception $e) {
            Log::error("Failed to index content: {$service}.{$itemType}#{$itemId}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove content from search index
     */
    public function removeContent(string $service, int $itemId, ?int $userId = null): void
    {
        try {
            $userId = $userId ?? auth()->id();
            
            SearchIndex::where([
                'user_id' => $userId,
                'service' => $service,
                'item_id' => $itemId,
            ])->delete();

            Log::info("Content removed from index: {$service}#{$itemId}");
        } catch (\Exception $e) {
            Log::error("Failed to remove content from index", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get search suggestions/autocomplete
     */
    public function getSuggestions(string $partialQuery, int $limit = 5): array
    {
        if (strlen($partialQuery) < 2) {
            return [];
        }

        try {
            $userId = auth()->id();
            
            // Get recent searches by this user
            $recentSearches = DB::table('search_history')
                ->where('user_id', $userId)
                ->where('query', 'LIKE', "{$partialQuery}%")
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->pluck('query')
                ->toArray();

            // Get popular searches across platform
            $popularSearches = DB::table('search_history')
                ->where('query', 'LIKE', "{$partialQuery}%")
                ->groupBy('query')
                ->orderByRaw('COUNT(*) DESC')
                ->limit($limit)
                ->pluck('query')
                ->toArray();

            // Get direct content title matches (AI Smart Suggestions)
            $contentMatches = SearchIndex::where('user_id', $userId)
                ->where('title', 'LIKE', "{$partialQuery}%")
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->pluck('title')
                ->toArray();

            return array_unique(array_merge($contentMatches, $recentSearches, $popularSearches));
        } catch (\Exception $e) {
            Log::error("Failed to get search suggestions", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Rebuild search index for a specific service
     */
    public function rebuildIndex(string $service, ?int $userId = null): int
    {
        $indexed = 0;

        switch ($service) {
            case 'mail':
                $indexed = $this->rebuildMailIndex($userId);
                break;
            case 'drive':
                $indexed = $this->rebuildDriveIndex($userId);
                break;
            case 'docs':
                $indexed = $this->rebuildDocsIndex($userId);
                break;
            // Add other services as needed
        }

        Log::info("Search index rebuilt for {$service}: {$indexed} items indexed");
        return $indexed;
    }

    // ========== PRIVATE METHODS ==========

    /**
     * Search mail messages
     */
    private function searchMail(string $query, int $userId, int $limit): array
    {
        try {
            // Use full-text search if available, fallback to LIKE
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'mail')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'mail',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => $this->extractSnippet($item->content, 150),
                        'icon' => 'envelope',
                        'url' => "/mail/{$item->item_id}",
                        'metadata' => json_decode($item->metadata, true),
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Mail search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search Drive files
     */
    private function searchDrive(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'drive')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $metadata = json_decode($item->metadata, true);
                    return [
                        'type' => 'drive',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => $metadata['description'] ?? $this->extractSnippet($item->content, 150),
                        'icon' => $this->getFileIcon($metadata['mime_type'] ?? ''),
                        'url' => "/drive/file/{$item->item_id}",
                        'metadata' => $metadata,
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Drive search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search DocX documents
     */
    private function searchDocs(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'docs')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'docs',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => $this->extractSnippet($item->content, 150),
                        'icon' => 'file-alt',
                        'url' => "/docs/{$item->item_id}/edit",
                        'metadata' => json_decode($item->metadata, true),
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Docs search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search Xcel spreadsheets
     */
    private function searchXcel(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'xcel')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'xcel',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => 'Spreadsheet',
                        'icon' => 'file-excel',
                        'url' => "/xcel/{$item->item_id}/edit",
                        'metadata' => json_decode($item->metadata, true),
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Xcel search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search contacts
     */
    private function searchContacts(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'contacts')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $metadata = json_decode($item->metadata, true);
                    return [
                        'type' => 'contacts',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => $metadata['email'] ?? $metadata['phone'] ?? '',
                        'icon' => 'address-book',
                        'url' => "/contacts/{$item->item_id}",
                        'metadata' => $metadata,
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Contacts search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search calendar events
     */
    private function searchCalendar(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'calendar')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $metadata = json_decode($item->metadata, true);
                    return [
                        'type' => 'calendar',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => $metadata['start_time'] ?? '',
                        'icon' => 'calendar',
                        'url' => "/calendar/event/{$item->item_id}",
                        'metadata' => $metadata,
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Calendar search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search chat messages
     */
    private function searchChat(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'chat')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'chat',
                        'id' => $item->item_id,
                        'title' => 'Chat Message',
                        'snippet' => $this->extractSnippet($item->content, 150),
                        'icon' => 'comments',
                        'url' => "/chat",
                        'metadata' => json_decode($item->metadata, true),
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Chat search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search forms
     */
    private function searchForms(string $query, int $userId, int $limit): array
    {
        try {
            $results = SearchIndex::where('user_id', $userId)
                ->where('service', 'forms')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->orderBy('indexed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'forms',
                        'id' => $item->item_id,
                        'title' => $item->title,
                        'snippet' => 'Form',
                        'icon' => 'clipboard-list',
                        'url' => "/collect/forms/{$item->item_id}",
                        'metadata' => json_decode($item->metadata, true),
                    ];
                })
                ->toArray();

            return $results;
        } catch (\Exception $e) {
            Log::error("Forms search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract text snippet with query highlighting
     */
    private function extractSnippet(string $content, int $length = 150): string
    {
        if (empty($content)) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($content);
        
        // Truncate to length
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length) . '...';
        }

        return $text;
    }

    /**
     * Get file icon based on MIME type
     */
    private function getFileIcon(string $mimeType): string
    {
        $icons = [
            'application/pdf' => 'file-pdf',
            'image/' => 'file-image',
            'video/' => 'file-video',
            'audio/' => 'file-audio',
            'application/zip' => 'file-archive',
            'text/' => 'file-alt',
        ];

        foreach ($icons as $type => $icon) {
            if (strpos($mimeType, $type) === 0) {
                return $icon;
            }
        }

        return 'file';
    }

    /**
     * Log search query for analytics and AI training
     */
    private function logSearchQuery(string $query, int $userId, array $results): void
    {
        try {
            DB::table('search_history')->insert([
                'user_id' => $userId,
                'query' => $query,
                'result_count' => array_sum(array_map('count', $results)),
                'filters' => json_encode([]),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't fail search if logging fails
            Log::warning("Failed to log search query", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rebuild mail search index
     */
    private function rebuildMailIndex(?int $userId): int
    {
        // Implementation would query mail_messages table and index them
        // This is a placeholder - actual implementation depends on YG Mail schema
        return 0;
    }

    /**
     * Rebuild Drive search index
     */
    private function rebuildDriveIndex(?int $userId): int
    {
        // Implementation would query drive_files table and index them
        return 0;
    }

    /**
     * Rebuild Docs search index
     */
    private function rebuildDocsIndex(?int $userId): int
    {
        // Implementation would query documents table and index them
        return 0;
    }
}
