<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * DeepResearchService - Multi-page research with synthesis
 * 
 * Performs comprehensive research by:
 * 1. Searching for relevant sources
 * 2. Visiting multiple pages in parallel (curl_multi)
 * 3. Extracting structured data from each page
 * 4. Using LLM to synthesize findings
 * 5. Generating citations with source links
 * 6. Creating executive summary + detailed sections
 * 
 * Designed for cPanel shared hosting - uses only PHP/cURL, no Node.js
 */
class DeepResearchService
{
    private BrowserProxyService $proxy;
    private AgentLLMService $llm;
    private CitationTracker $citationTracker;
    
    /** Max pages to visit during research */
    private int $maxPages = 8;
    
    /** Timeout per page fetch (seconds) */
    private int $pageTimeout = 10;
    
    public function __construct()
    {
        $this->proxy = app(BrowserProxyService::class);
        $this->llm = app(AgentLLMService::class);
        $this->citationTracker = app(CitationTracker::class);
    }
    
    /**
     * Execute deep research on a query
     */
    public function research(string $query, array $options = []): array
    {
        $startTime = microtime(true);
        
        $config = array_merge([
            'max_pages' => $this->maxPages,
            'include_summary' => true,
            'include_citations' => true,
            'format' => 'markdown',
        ], $options);
        
        try {
            Log::info("DeepResearch: Starting research for '{$query}'");
            
            // Step 1: Find sources
            $sources = $this->findSources($query, $config['max_pages']);
            
            if (empty($sources)) {
                return [
                    'success' => false,
                    'error' => 'No relevant sources found',
                    'query' => $query,
                ];
            }
            
            Log::info("DeepResearch: Found " . count($sources) . " sources");
            
            // Step 2: Fetch pages in parallel
            $pageContents = $this->fetchPagesParallel($sources);
            
            // Step 3: Extract data
            $extractedData = [];
            foreach ($pageContents as $url => $content) {
                if (!empty($content['html'])) {
                    $extracted = $this->extractPageData($content['html'], $url);
                    $extractedData[] = [
                        'url' => $url,
                        'title' => $content['title'] ?? '',
                        'content' => $extracted,
                    ];
                    
                    $this->citationTracker->addSource($url, $content['title'] ?? '', 'research');
                }
            }
            
            // Step 4: Synthesize
            $synthesis = $this->synthesizeFindings($query, $extractedData);
            
            // Step 5: Generate report
            $report = $this->generateReport($query, $synthesis, $extractedData);
            
            $elapsed = round(microtime(true) - $startTime, 2);
            
            return [
                'success' => true,
                'query' => $query,
                'report' => $report,
                'sources_count' => count($extractedData),
                'elapsed_seconds' => $elapsed,
                'citations' => $config['include_citations'] ? $this->citationTracker->getRecent(10) : [],
            ];
            
        } catch (Exception $e) {
            Log::error("DeepResearch failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query,
            ];
        }
    }
    
    private function findSources(string $query, int $maxResults): array
    {
        $sources = [];
        
        // Try YG ecosystem search
        try {
            $searchService = app(\App\Services\UnifiedSearchService::class);
            $results = $searchService->search($query, ['per_page' => $maxResults]);
            
            foreach ($results as $type => $items) {
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (isset($item['url']) && !in_array($item['url'], array_column($sources, 'url'))) {
                            $sources[] = [
                                'url' => $item['url'],
                                'title' => $item['title'] ?? '',
                                'source' => 'yg_ecosystem',
                            ];
                            
                            if (count($sources) >= $maxResults) break 2;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("YG search failed: " . $e->getMessage());
        }
        
        // External search if needed
        if (count($sources) < $maxResults) {
            $externalSources = $this->searchDuckDuckGo($query, $maxResults - count($sources));
            $sources = array_merge($sources, $externalSources);
        }
        
        return array_slice($sources, 0, $maxResults);
    }
    
    private function searchDuckDuckGo(string $query, int $count): array
    {
        $sources = [];
        
        try {
            $encodedQuery = urlencode($query);
            $response = Http::timeout(5)->get("https://api.duckduckgo.com/?q={$encodedQuery}&format=json");
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['AbstractURL'])) {
                    $sources[] = [
                        'url' => $data['AbstractURL'],
                        'title' => $data['Heading'] ?? $query,
                        'source' => 'duckduckgo',
                    ];
                }
                
                if (!empty($data['RelatedTopics'])) {
                    foreach (array_slice($data['RelatedTopics'], 0, $count) as $topic) {
                        if (isset($topic['FirstURL'])) {
                            $sources[] = [
                                'url' => $topic['FirstURL'],
                                'title' => $topic['Text'] ?? '',
                                'source' => 'duckduckgo',
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning("DuckDuckGo search failed: " . $e->getMessage());
        }
        
        return $sources;
    }
    
    private function fetchPagesParallel(array $sources): array
    {
        $results = [];
        $mh = curl_multi_init();
        $channels = [];
        
        foreach ($sources as $source) {
            $url = $source['url'];
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => $this->pageTimeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YGXONE Research Bot)',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_ENCODING => '',
            ]);
            
            curl_multi_add_handle($mh, $ch);
            $channels[(string)$ch] = $url;
        }
        
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.1);
        } while ($running > 0);
        
        foreach ($channels as $chKey => $url) {
            $ch = $channels[$chKey];
            $content = curl_multi_getcontent($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $title = null;
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $matches)) {
                $title = trim(strip_tags($matches[1]));
            }
            
            $results[$url] = [
                'html' => $content ?: '',
                'statusCode' => $statusCode,
                'title' => $title,
            ];
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        
        return $results;
    }
    
    private function extractPageData(string $html, string $url): array
    {
        $data = [
            'headings' => [],
            'paragraphs' => [],
            'links' => [],
        ];
        
        libxml_use_internal_errors(true);
        
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        
        if (!$loaded) {
            libxml_clear_errors();
            return $data;
        }
        
        $xpath = new \DOMXPath($dom);
        
        // Extract headings
        for ($level = 1; $level <= 3; $level++) {
            $headings = $xpath->query("//h{$level}");
            foreach ($headings as $heading) {
                $data['headings'][] = [
                    'level' => $level,
                    'text' => trim($heading->textContent),
                ];
            }
        }
        
        // Extract paragraphs
        $paragraphs = $xpath->query('//p');
        $count = 0;
        foreach ($paragraphs as $p) {
            if ($count >= 10) break;
            $text = trim($p->textContent);
            if (strlen($text) > 50) {
                $data['paragraphs'][] = $text;
                $count++;
            }
        }
        
        libxml_clear_errors();
        
        return $data;
    }
    
    private function synthesizeFindings(string $query, array $extractedData): array
    {
        $context = "Research Query: {$query}\n\n";
        $context .= "Sources Analyzed: " . count($extractedData) . "\n\n";
        
        foreach ($extractedData as $idx => $data) {
            $context .= "--- Source " . ($idx + 1) . ": {$data['title']} ---\n";
            $context .= "URL: {$data['url']}\n\n";
            
            if (!empty($data['content']['headings'])) {
                $context .= "Headings:\n";
                foreach (array_slice($data['content']['headings'], 0, 5) as $h) {
                    $context .= "- " . str_repeat('#', $h['level']) . " " . $h['text'] . "\n";
                }
                $context .= "\n";
            }
            
            if (!empty($data['content']['paragraphs'])) {
                $context .= "Content:\n";
                foreach (array_slice($data['content']['paragraphs'], 0, 3) as $p) {
                    $context .= substr($p, 0, 300) . "...\n";
                }
                $context .= "\n";
            }
        }
        
        $systemPrompt = <<<'PROMPT'
You are an expert research analyst. Synthesize information from multiple sources into a comprehensive report.

Guidelines:
1. Start with Executive Summary (2-3 paragraphs)
2. Organize into clear sections with headings
3. Cite sources inline using [Source N] format
4. Highlight key insights and contradictions
5. Include relevant data and statistics
6. End with Key Takeaways (bullet points)
7. Maintain objectivity
8. Use Markdown format
PROMPT;
        
        $userPrompt = "{$context}\n\nPlease synthesize into a comprehensive research report on: {$query}";
        
        $history = [['role' => 'user', 'content' => $userPrompt]];
        $result = $this->llm->callWithTools($systemPrompt, $history);
        
        return [
            'synthesis' => $result['text'] ?? '',
            'error' => $result['error'] ?? null,
        ];
    }
    
    private function generateReport(string $query, array $synthesis, array $extractedData): array
    {
        $report = [
            'title' => "Research Report: {$query}",
            'executive_summary' => '',
            'sections' => [],
            'key_takeaways' => [],
            'sources' => [],
            'metadata' => [
                'query' => $query,
                'sources_analyzed' => count($extractedData),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
        
        $synthesisText = $synthesis['synthesis'] ?? '';
        
        if (!empty($synthesisText)) {
            $sections = preg_split('/^##\s+/m', $synthesisText);
            
            foreach ($sections as $idx => $section) {
                if ($idx === 0) {
                    $report['executive_summary'] = trim($section);
                } else {
                    $lines = explode("\n", trim($section), 2);
                    $heading = $lines[0] ?? "Section " . $idx;
                    $content = $lines[1] ?? '';
                    
                    $report['sections'][] = [
                        'heading' => $heading,
                        'content' => $content,
                    ];
                }
            }
            
            // Extract takeaways
            if (preg_match('/(?:Key Takeaways|Conclusion)[:\s]*(.*?)(?:$|\n\n)/s', $synthesisText, $matches)) {
                preg_match_all('/[-*]\s+(.+?)(?=\n[-*]|\n\n|$)/s', $matches[1], $bulletMatches);
                if (!empty($bulletMatches[1])) {
                    $report['key_takeaways'] = array_map('trim', $bulletMatches[1]);
                }
            }
        }
        
        foreach ($extractedData as $data) {
            $report['sources'][] = [
                'title' => $data['title'],
                'url' => $data['url'],
            ];
        }
        
        return $report;
    }
}
