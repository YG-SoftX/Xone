<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * SourceComparisonService
 * 
 * Provides side-by-side comparison of multiple sources/pages.
 * Highlights differences, similarities, and provides similarity scoring.
 */
class SourceComparisonService
{
    /**
     * Compare two pages/sources
     * 
     * @param array $source1 First source data
     * @param array $source2 Second source data
     * @return array Comparison results
     */
    public function compareSources(array $source1, array $source2): array
    {
        try {
            // Extract text content
            $text1 = $this->extractText($source1['html'] ?? '');
            $text2 = $this->extractText($source2['html'] ?? '');
            
            // Calculate similarity
            $similarityScore = $this->calculateSimilarity($text1, $text2);
            
            // Find differences
            $differences = $this->findDifferences($text1, $text2);
            
            // Extract common topics
            $commonTopics = $this->findCommonTopics($text1, $text2);
            
            // Compare metadata
            $metadataComparison = $this->compareMetadata($source1, $source2);
            
            return [
                'similarity_score' => round($similarityScore * 100, 2),
                'differences' => $differences,
                'common_topics' => $commonTopics,
                'metadata_comparison' => $metadataComparison,
                'summary' => $this->generateComparisonSummary($similarityScore, count($differences), count($commonTopics)),
            ];
            
        } catch (\Exception $e) {
            Log::error("Source comparison failed: " . $e->getMessage());
            return [
                'error' => 'Comparison failed',
                'similarity_score' => 0,
            ];
        }
    }
    
    /**
     * Compare multiple sources (3+)
     * 
     * @param array $sources Array of source data
     * @return array Multi-source comparison
     */
    public function compareMultipleSources(array $sources): array
    {
        try {
            $comparisons = [];
            $count = count($sources);
            
            // Compare each pair
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $key = "{$i}_vs_{$j}";
                    $comparisons[$key] = $this->compareSources($sources[$i], $sources[$j]);
                }
            }
            
            // Find commonalities across all sources
            $allTexts = array_map(fn($s) => $this->extractText($s['html'] ?? ''), $sources);
            $commonAcrossAll = $this->findCommonAcrossAll($allTexts);
            
            return [
                'pairwise_comparisons' => $comparisons,
                'common_across_all' => $commonAcrossAll,
                'source_count' => $count,
            ];
            
        } catch (\Exception $e) {
            Log::error("Multi-source comparison failed: " . $e->getMessage());
            return ['error' => 'Comparison failed'];
        }
    }
    
    /**
     * Extract clean text from HTML
     */
    private function extractText(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        
        // Strip tags
        $text = strip_tags($html);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Calculate text similarity using cosine similarity
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        // Tokenize
        $tokens1 = $this->tokenize($text1);
        $tokens2 = $this->tokenize($text2);
        
        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }
        
        // Get unique tokens
        $allTokens = array_unique(array_merge(array_keys($tokens1), array_keys($tokens2)));
        
        // Calculate vectors
        $vector1 = [];
        $vector2 = [];
        
        foreach ($allTokens as $token) {
            $vector1[] = $tokens1[$token] ?? 0;
            $vector2[] = $tokens2[$token] ?? 0;
        }
        
        // Cosine similarity
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] ** 2;
            $magnitude2 += $vector2[$i] ** 2;
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Tokenize text into word frequencies
     */
    private function tokenize(string $text): array
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Extract words
        preg_match_all('/\b[a-z]{3,}\b/', $text, $matches);
        
        $words = $matches[0] ?? [];
        
        // Count frequencies
        return array_count_values($words);
    }
    
    /**
     * Find differences between texts
     */
    private function findDifferences(string $text1, string $text2): array
    {
        $sentences1 = $this->splitIntoSentences($text1);
        $sentences2 = $this->splitIntoSentences($text2);
        
        $onlyInFirst = array_diff($sentences1, $sentences2);
        $onlyInSecond = array_diff($sentences2, $sentences1);
        
        return [
            'only_in_source_1' => array_slice(array_values($onlyInFirst), 0, 10),
            'only_in_source_2' => array_slice(array_values($onlyInSecond), 0, 10),
            'unique_sentences_1' => count($onlyInFirst),
            'unique_sentences_2' => count($onlyInSecond),
        ];
    }
    
    /**
     * Split text into sentences
     */
    private function splitIntoSentences(string $text): array
    {
        preg_match_all('/[^.!?]+[.!?]+/', $text, $matches);
        return array_map('trim', $matches[0] ?? []);
    }
    
    /**
     * Find common topics using keyword extraction
     */
    private function findCommonTopics(string $text1, string $text2): array
    {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);
        
        // Find intersection
        $common = array_intersect($keywords1, $keywords2);
        
        return array_slice(array_values($common), 0, 15);
    }
    
    /**
     * Extract important keywords
     */
    private function extractKeywords(string $text): array
    {
        $frequencies = $this->tokenize($text);
        
        // Sort by frequency
        arsort($frequencies);
        
        // Return top keywords
        return array_slice(array_keys($frequencies), 0, 30);
    }
    
    /**
     * Find topics common across all texts
     */
    private function findCommonAcrossAll(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }
        
        $keywordSets = array_map(fn($t) => $this->extractKeywords($t), $texts);
        
        // Find intersection of all sets
        $common = $keywordSets[0];
        
        for ($i = 1; $i < count($keywordSets); $i++) {
            $common = array_intersect($common, $keywordSets[$i]);
        }
        
        return array_values($common);
    }
    
    /**
     * Compare metadata between sources
     */
    private function compareMetadata(array $source1, array $source2): array
    {
        return [
            'title' => [
                'source_1' => $source1['title'] ?? 'N/A',
                'source_2' => $source2['title'] ?? 'N/A',
                'same' => ($source1['title'] ?? '') === ($source2['title'] ?? ''),
            ],
            'author' => [
                'source_1' => $source1['author'] ?? 'Unknown',
                'source_2' => $source2['author'] ?? 'Unknown',
                'same' => ($source1['author'] ?? '') === ($source2['author'] ?? ''),
            ],
            'date' => [
                'source_1' => $source1['date'] ?? 'Unknown',
                'source_2' => $source2['date'] ?? 'Unknown',
                'same' => ($source1['date'] ?? '') === ($source2['date'] ?? ''),
            ],
            'word_count' => [
                'source_1' => str_word_count($source1['html'] ?? ''),
                'source_2' => str_word_count($source2['html'] ?? ''),
            ],
        ];
    }
    
    /**
     * Generate human-readable comparison summary
     */
    private function generateComparisonSummary(float $similarity, int $diffCount, int $commonCount): string
    {
        if ($similarity > 80) {
            return "These sources are very similar ({$similarity}% match). They cover mostly the same topics with {$diffCount} key differences.";
        } elseif ($similarity > 50) {
            return "These sources share significant overlap ({$similarity}% match) with {$commonCount} common topics and {$diffCount} differences.";
        } elseif ($similarity > 20) {
            return "These sources have some overlap ({$similarity}% match) but focus on different aspects with {$diffCount} unique points each.";
        } else {
            return "These sources are quite different ({$similarity}% match), covering distinct topics with minimal overlap.";
        }
    }
}
