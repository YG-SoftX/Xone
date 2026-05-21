<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FollowUpSuggestionService
 * 
 * Generates context-aware follow-up questions based on browsing history,
 * research results, and agent interactions.
 */
class FollowUpSuggestionService
{
    private AgentLLMService $llmService;
    
    public function __construct(AgentLLMService $llmService)
    {
        $this->llmService = $llmService;
    }
    
    /**
     * Generate follow-up suggestions after completing a task
     * 
     * @param string $task The original task/query
     * @param array $results Task execution results
     * @param string $context Additional context (browsing, research, agent)
     * @return array Array of suggested follow-up questions
     */
    public function generateSuggestions(string $task, array $results = [], string $context = 'browsing'): array
    {
        try {
            // Get recent browsing/research history for context
            $recentHistory = $this->getRecentContext($context);
            
            // Use LLM to generate contextual suggestions
            if ($this->llmService->isConfigured()) {
                return $this->generateWithLLM($task, $results, $recentHistory);
            }
            
            // Fallback: Rule-based suggestions
            return $this->generateRuleBased($task, $results, $recentHistory);
            
        } catch (\Exception $e) {
            Log::error("Follow-up suggestion generation failed: " . $e->getMessage());
            return $this->getDefaultSuggestions();
        }
    }
    
    /**
     * Generate suggestions using LLM for better contextual awareness
     */
    private function generateWithLLM(string $task, array $results, array $recentHistory): array
    {
        $prompt = "Based on this completed task and context, suggest 3 natural follow-up questions or actions.\n\n" .
            "Task: {$task}\n" .
            "Context: Recent activity includes " . count($recentHistory) . " related items.\n" .
            "Results summary: " . json_encode(array_slice($results, -3)) . "\n\n" .
            "Guidelines:\n" .
            "- Make suggestions specific and actionable\n" .
            "- Build upon the current topic\n" .
            "- Vary the types (explore deeper, compare alternatives, find related)\n" .
            "- Keep each suggestion under 80 characters\n" .
            "- Return as JSON array\n\n" .
            "Example format: [\"Show me more details about X\", \"Compare this with Y\", \"Find related articles\"]";

        $response = $this->llmService->callLLM($prompt, [
            'max_tokens' => 200,
            'temperature' => 0.7,
        ]);
        
        // Parse JSON response
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $suggestions = json_decode($matches[0], true);
            if (is_array($suggestions) && !empty($suggestions)) {
                return array_map(function($s) {
                    return [
                        'text' => substr(strip_tags($s), 0, 80),
                        'type' => $this->classifySuggestion($s),
                        'confidence' => 0.9,
                    ];
                }, $suggestions);
            }
        }
        
        return $this->getDefaultSuggestions();
    }
    
    /**
     * Rule-based fallback when LLM is unavailable
     */
    private function generateRuleBased(string $task, array $results, array $recentHistory): array
    {
        $suggestions = [];
        $lowerTask = strtolower($task);
        
        // Pattern 1: Research queries → Suggest deeper exploration
        if (strpos($lowerTask, 'research') !== false || strpos($lowerTask, 'find') !== false) {
            $suggestions[] = [
                'text' => 'Show me more details about this',
                'type' => 'deepen',
                'confidence' => 0.8,
            ];
            $suggestions[] = [
                'text' => 'Find related sources',
                'type' => 'expand',
                'confidence' => 0.7,
            ];
        }
        
        // Pattern 2: Comparison queries → Suggest alternatives
        if (strpos($lowerTask, 'compare') !== false || strpos($lowerTask, 'vs') !== false) {
            $suggestions[] = [
                'text' => 'Compare with other options',
                'type' => 'compare',
                'confidence' => 0.85,
            ];
            $suggestions[] = [
                'text' => 'Show pros and cons',
                'type' => 'analyze',
                'confidence' => 0.75,
            ];
        }
        
        // Pattern 3: Price/product queries → Suggest shopping actions
        if (strpos($lowerTask, 'price') !== false || strpos($lowerTask, 'buy') !== false) {
            $suggestions[] = [
                'text' => 'Find best deals',
                'type' => 'shop',
                'confidence' => 0.9,
            ];
            $suggestions[] = [
                'text' => 'Check reviews',
                'type' => 'review',
                'confidence' => 0.8,
            ];
        }
        
        // Pattern 4: General browsing → Suggest navigation
        if (empty($suggestions)) {
            $suggestions[] = [
                'text' => 'What else can I help with?',
                'type' => 'general',
                'confidence' => 0.6,
            ];
            $suggestions[] = [
                'text' => 'Summarize this page',
                'type' => 'summarize',
                'confidence' => 0.7,
            ];
        }
        
        // Add one from recent history if available
        if (!empty($recentHistory)) {
            $lastItem = end($recentHistory);
            $suggestions[] = [
                'text' => 'Continue from: ' . substr($lastItem['title'] ?? 'previous page', 0, 40),
                'type' => 'continue',
                'confidence' => 0.65,
            ];
        }
        
        return array_slice($suggestions, 0, 3);
    }
    
    /**
     * Classify suggestion type for UI rendering
     */
    private function classifySuggestion(string $text): string
    {
        $lower = strtolower($text);
        
        if (strpos($lower, 'compare') !== false) return 'compare';
        if (strpos($lower, 'details') !== false || strpos($lower, 'more') !== false) return 'deepen';
        if (strpos($lower, 'find') !== false || strpos($lower, 'search') !== false) return 'search';
        if (strpos($lower, 'summary') !== false) return 'summarize';
        if (strpos($lower, 'price') !== false || strpos($lower, 'deal') !== false) return 'shop';
        
        return 'general';
    }
    
    /**
     * Get recent context from browsing/research history
     */
    private function getRecentContext(string $contextType, int $limit = 5): array
    {
        $sessionId = session()->getId();
        
        switch ($contextType) {
            case 'research':
                // Get recent research reports
                return DB::table('research_reports')
                    ->where('session_id', $sessionId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get(['query', 'created_at'])
                    ->toArray();
                    
            case 'agent':
                // Get recent agent sessions
                return DB::table('agent_sessions')
                    ->where('session_id', $sessionId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get(['task_description', 'success'])
                    ->toArray();
                    
            default:
                // Get recent citations (browsing history proxy)
                return DB::table('browser_citations')
                    ->where('session_id', $sessionId)
                    ->orderBy('last_accessed_at', 'desc')
                    ->limit($limit)
                    ->get(['url', 'title', 'last_accessed_at'])
                    ->toArray();
        }
    }
    
    /**
     * Default fallback suggestions
     */
    private function getDefaultSuggestions(): array
    {
        return [
            [
                'text' => 'Tell me more about this',
                'type' => 'deepen',
                'confidence' => 0.5,
            ],
            [
                'text' => 'Find related information',
                'type' => 'expand',
                'confidence' => 0.5,
            ],
            [
                'text' => 'What can I do next?',
                'type' => 'general',
                'confidence' => 0.5,
            ],
        ];
    }
    
    /**
     * Store user feedback on suggestions (for learning)
     */
    public function trackSelection(string $taskId, int $selectedIndex, bool $helpful): void
    {
        try {
            // Could store in a suggestions_feedback table for ML training
            Log::info("Suggestion feedback", [
                'task_id' => $taskId,
                'selected_index' => $selectedIndex,
                'helpful' => $helpful,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to track suggestion feedback: " . $e->getMessage());
        }
    }
}
