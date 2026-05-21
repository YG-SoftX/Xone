<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DeveloperToolsService
 * 
 * Provides developer tools functionality similar to Chrome DevTools.
 * Includes network monitoring, console logging, element inspection, and performance profiling.
 */
class DeveloperToolsService
{
    /**
     * Log network request/response
     * 
     * @param array $requestData Request details
     * @param array $responseData Response details
     * @param float $duration Request duration in ms
     */
    public function logNetworkRequest(array $requestData, array $responseData, float $duration): void
    {
        try {
            DB::table('dev_tools_logs')->insert([
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'log_type' => 'network',
                'log_data' => json_encode([
                    'method' => $requestData['method'] ?? 'GET',
                    'url' => $requestData['url'] ?? '',
                    'status' => $responseData['status'] ?? 0,
                    'type' => $responseData['type'] ?? 'xhr',
                    'size' => $responseData['size'] ?? 0,
                    'duration' => round($duration, 2),
                    'timestamp' => now()->toIso8601String(),
                ]),
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log network request: " . $e->getMessage());
        }
    }
    
    /**
     * Log console message
     * 
     * @param string $level Console level (log, warn, error, info, debug)
     * @param mixed $message Message content
     * @param array $context Additional context
     */
    public function logConsole(string $level, $message, array $context = []): void
    {
        try {
            DB::table('dev_tools_logs')->insert([
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'log_type' => 'console',
                'log_data' => json_encode([
                    'level' => $level,
                    'message' => is_string($message) ? $message : json_encode($message),
                    'context' => $context,
                    'timestamp' => now()->toIso8601String(),
                ]),
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log console message: " . $e->getMessage());
        }
    }
    
    /**
     * Get recent network requests
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Network logs
     */
    public function getNetworkLogs(int $limit = 50): array
    {
        try {
            return DB::table('dev_tools_logs')
                ->where('session_id', session()->getId())
                ->where('log_type', 'network')
                ->orderBy('logged_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($log) {
                    $data = json_decode($log->log_data, true);
                    return [
                        'id' => $log->id,
                        'method' => $data['method'],
                        'url' => $data['url'],
                        'status' => $data['status'],
                        'type' => $data['type'],
                        'size' => $data['size'],
                        'duration' => $data['duration'],
                        'timestamp' => $data['timestamp'],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get network logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent console logs
     */
    public function getConsoleLogs(int $limit = 50): array
    {
        try {
            return DB::table('dev_tools_logs')
                ->where('session_id', session()->getId())
                ->where('log_type', 'console')
                ->orderBy('logged_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($log) {
                    $data = json_decode($log->log_data, true);
                    return [
                        'id' => $log->id,
                        'level' => $data['level'],
                        'message' => $data['message'],
                        'context' => $data['context'] ?? [],
                        'timestamp' => $data['timestamp'],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get console logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyze page elements structure
     * 
     * @param string $html Page HTML
     * @return array Element tree structure
     */
    public function analyzeElements(string $html): array
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            
            $structure = $this->buildElementTree($dom->documentElement);
            
            // Count statistics
            $stats = [
                'total_elements' => 0,
                'tags' => [],
                'depth' => 0,
            ];
            
            $this->collectStats($structure, $stats);
            
            return [
                'tree' => $structure,
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to analyze elements: " . $e->getMessage());
            return ['tree' => [], 'stats' => []];
        }
    }
    
    /**
     * Build recursive element tree
     */
    private function buildElementTree(\DOMNode $node, int $depth = 0): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            return !empty($text) ? ['type' => 'text', 'content' => substr($text, 0, 100)] : null;
        }
        
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }
        
        $element = [
            'tag' => strtolower($node->nodeName),
            'attributes' => [],
            'children' => [],
            'depth' => $depth,
        ];
        
        // Extract attributes
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $element['attributes'][$attr->name] = $attr->value;
            }
        }
        
        // Process children
        foreach ($node->childNodes as $child) {
            $childTree = $this->buildElementTree($child, $depth + 1);
            if ($childTree) {
                $element['children'][] = $childTree;
            }
        }
        
        return $element;
    }
    
    /**
     * Collect element statistics
     */
    private function collectStats(array $element, array &$stats): void
    {
        if (!isset($element['tag'])) {
            return;
        }
        
        $stats['total_elements']++;
        $tag = $element['tag'];
        $stats['tags'][$tag] = ($stats['tags'][$tag] ?? 0) + 1;
        $stats['depth'] = max($stats['depth'], $element['depth']);
        
        foreach ($element['children'] ?? [] as $child) {
            $this->collectStats($child, $stats);
        }
    }
    
    /**
     * Measure page performance metrics
     * 
     * @param string $html Page HTML
     * @return array Performance metrics
     */
    public function measurePerformance(string $html): array
    {
        $startTime = microtime(true);
        
        // Parse DOM
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        $parseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Count resources
        $images = $dom->getElementsByTagName('img')->length;
        $scripts = $dom->getElementsByTagName('script')->length;
        $stylesheets = $dom->getElementsByTagName('link')->length;
        
        // Estimate page weight
        $pageWeight = strlen($html);
        
        return [
            'parse_time_ms' => $parseTime,
            'total_elements' => $dom->getElementsByTagName('*')->length,
            'images' => $images,
            'scripts' => $scripts,
            'stylesheets' => $stylesheets,
            'page_weight_kb' => round($pageWeight / 1024, 2),
            'dom_depth' => $this->calculateDomDepth($dom->documentElement),
        ];
    }
    
    /**
     * Calculate maximum DOM depth
     */
    private function calculateDomDepth(\DOMNode $node, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;
        
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $childDepth = $this->calculateDomDepth($child, $currentDepth + 1);
                $maxDepth = max($maxDepth, $childDepth);
            }
        }
        
        return $maxDepth;
    }
    
    /**
     * Clear dev tools logs for session
     */
    public function clearLogs(): bool
    {
        try {
            DB::table('dev_tools_logs')
                ->where('session_id', session()->getId())
                ->delete();
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear logs: " . $e->getMessage());
            return false;
        }
    }
}
