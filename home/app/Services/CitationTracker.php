<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CitationTracker - Smart citation tracking system
 * 
 * Automatically tracks all sources visited during browsing/research sessions
 * Generates citations in multiple formats (APA, MLA, Chicago)
 * Provides floating citation panel UI data
 * 
 * cPanel compatible - uses MySQL database for persistence
 */
class CitationTracker
{
    /**
     * Add a source to citation tracker
     */
    public function addSource(string $url, string $title = '', string $context = 'browsing'): void
    {
        try {
            // Check if already tracked
            $existing = DB::table('browser_citations')
                ->where('url', $url)
                ->where('session_id', session()->getId())
                ->first();
            
            if ($existing) {
                // Update visit count and last accessed
                DB::table('browser_citations')
                    ->where('id', $existing->id)
                    ->update([
                        'visit_count' => DB::raw('visit_count + 1'),
                        'last_accessed_at' => now(),
                    ]);
            } else {
                // Extract metadata
                $metadata = $this->extractMetadata($url, $title);
                
                // Insert new citation
                DB::table('browser_citations')->insert([
                    'session_id' => session()->getId(),
                    'user_id' => auth()->id(),
                    'url' => $url,
                    'title' => $metadata['title'],
                    'author' => $metadata['author'],
                    'published_date' => $metadata['published_date'],
                    'site_name' => $metadata['site_name'],
                    'context' => $context,
                    'visit_count' => 1,
                    'created_at' => now(),
                    'last_accessed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("CitationTracker: Failed to add source: " . $e->getMessage());
        }
    }
    
    /**
     * Get recent citations for current session
     */
    public function getRecent(int $limit = 10): array
    {
        try {
            return DB::table('browser_citations')
                ->where('session_id', session()->getId())
                ->orderBy('last_accessed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn($citation) => [
                    'id' => $citation->id,
                    'url' => $citation->url,
                    'title' => $citation->title,
                    'author' => $citation->author,
                    'site_name' => $citation->site_name,
                    'published_date' => $citation->published_date,
                    'context' => $citation->context,
                    'visit_count' => $citation->visit_count,
                    'accessed_at' => $citation->last_accessed_at,
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::warning("CitationTracker: Failed to get recent: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate formatted citation in specified style
     */
    public function generateCitation(int $citationId, string $style = 'apa'): string
    {
        try {
            $citation = DB::table('browser_citations')
                ->where('id', $citationId)
                ->first();
            
            if (!$citation) {
                return '';
            }
            
            return match ($style) {
                'apa' => $this->formatAPA($citation),
                'mla' => $this->formatMLA($citation),
                'chicago' => $this->formatChicago($citation),
                default => $this->formatAPA($citation),
            };
        } catch (\Exception $e) {
            Log::warning("CitationTracker: Failed to generate citation: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Export all session citations in specified format
     */
    public function exportCitations(string $style = 'apa'): string
    {
        $citations = $this->getRecent(100);
        
        if (empty($citations)) {
            return 'No citations to export.';
        }
        
        $output = [];
        foreach ($citations as $citation) {
            $formatted = match ($style) {
                'apa' => $this->formatAPA((object)$citation),
                'mla' => $this->formatMLA((object)$citation),
                'chicago' => $this->formatChicago((object)$citation),
                default => $this->formatAPA((object)$citation),
            };
            $output[] = $formatted;
        }
        
        return implode("\n\n", $output);
    }
    
    /**
     * Clear session citations
     */
    public function clearSession(): void
    {
        try {
            DB::table('browser_citations')
                ->where('session_id', session()->getId())
                ->delete();
        } catch (\Exception $e) {
            Log::warning("CitationTracker: Failed to clear session: " . $e->getMessage());
        }
    }
    
    /**
     * Get citation statistics
     */
    public function getStats(): array
    {
        try {
            $stats = DB::table('browser_citations')
                ->where('session_id', session()->getId())
                ->selectRaw('
                    COUNT(*) as total_sources,
                    SUM(visit_count) as total_visits,
                    COUNT(DISTINCT context) as contexts_used
                ')
                ->first();
            
            return [
                'total_sources' => $stats->total_sources ?? 0,
                'total_visits' => $stats->total_visits ?? 0,
                'contexts_used' => $stats->contexts_used ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_sources' => 0,
                'total_visits' => 0,
                'contexts_used' => 0,
            ];
        }
    }
    
    // ── Private Methods ───────────────────────────────────────────────────────
    
    private function extractMetadata(string $url, string $title = ''): array
    {
        // Try to fetch OpenGraph metadata
        $metadata = [
            'title' => $title,
            'author' => null,
            'published_date' => null,
            'site_name' => parse_url($url, PHP_URL_HOST),
        ];
        
        try {
            // Quick fetch to extract meta tags
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 2,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YGXONE Citation Bot)',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $html = curl_exec($ch);
            curl_close($ch);
            
            if ($html) {
                // Extract author
                if (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $metadata['author'] = $matches[1];
                }
                
                // Extract published date
                if (preg_match('/<meta[^>]+property=["\']article:published_time["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $metadata['published_date'] = $matches[1];
                } elseif (preg_match('/<meta[^>]+name=["\']date["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $metadata['published_date'] = $matches[1];
                }
                
                // Extract site name
                if (preg_match('/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $metadata['site_name'] = $matches[1];
                }
                
                // Extract title if not provided
                if (empty($metadata['title']) && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                    $metadata['title'] = $matches[1];
                }
            }
        } catch (\Exception $e) {
            // Silently fail - use defaults
        }
        
        return $metadata;
    }
    
    private function formatAPA(object $citation): string
    {
        $parts = [];
        
        // Author
        if ($citation->author) {
            $parts[] = $citation->author . '.';
        }
        
        // Date
        if ($citation->published_date) {
            $date = date('Y, F d', strtotime($citation->published_date));
            $parts[] = "({$date}).";
        } else {
            $parts[] = '(n.d.).';
        }
        
        // Title
        $parts[] = "<i>{$citation->title}</i>.";
        
        // Site name
        if ($citation->site_name) {
            $parts[] = $citation->site_name . '.';
        }
        
        // URL
        $parts[] = "Retrieved from {$citation->url}";
        
        return implode(' ', $parts);
    }
    
    private function formatMLA(object $citation): string
    {
        $parts = [];
        
        // Author
        if ($citation->author) {
            $parts[] = $citation->author . '.';
        }
        
        // Title
        $parts[] = "\"{$citation->title}.\"";
        
        // Site name
        if ($citation->site_name) {
            $parts[] = "<i>{$citation->site_name}</i>,";
        }
        
        // Date
        if ($citation->published_date) {
            $date = date('d M. Y', strtotime($citation->published_date));
            $parts[] = "{$date},";
        }
        
        // URL
        $parts[] = $citation->url . '.';
        
        return implode(' ', $parts);
    }
    
    private function formatChicago(object $citation): string
    {
        $parts = [];
        
        // Author
        if ($citation->author) {
            $parts[] = $citation->author . ',';
        }
        
        // Title
        $parts[] = "<i>\"{$citation->title},\"</i>";
        
        // Site name
        if ($citation->site_name) {
            $parts[] = $citation->site_name . ',';
        }
        
        // Date
        if ($citation->published_date) {
            $date = date('F d, Y', strtotime($citation->published_date));
            $parts[] = "{$date},";
        }
        
        // URL
        $parts[] = $citation->url . '.';
        
        return implode(' ', $parts);
    }
}
