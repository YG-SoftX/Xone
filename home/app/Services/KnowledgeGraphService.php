<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KnowledgeGraphService - Cross-page entity relationship mapping
 * 
 * Builds a knowledge graph by:
 * 1. Extracting entities (people, organizations, locations, concepts) from visited pages
 * 2. Tracking relationships between entities
 * 3. Visualizing connections as interactive network graph
 * 4. Enabling entity-based navigation and discovery
 * 
 * cPanel compatible - uses MySQL for persistence, D3.js for visualization
 */
class KnowledgeGraphService
{
    /**
     * Extract and store entities from page content
     */
    public function extractEntities(string $html, string $url): array
    {
        $entities = $this->parseEntities($html);
        
        foreach ($entities as $entity) {
            $this->storeEntity($entity, $url);
        }
        
        return $entities;
    }
    
    /**
     * Get knowledge graph data for visualization
     */
    public function getGraphData(int $limit = 50): array
    {
        try {
            // Get entities
            $entities = DB::table('knowledge_entities')
                ->where('session_id', session()->getId())
                ->orderBy('mentions', 'desc')
                ->limit($limit)
                ->get();
            
            // Get relationships
            $relationships = DB::table('knowledge_relationships')
                ->join('knowledge_entities as source', 'knowledge_relationships.source_entity_id', '=', 'source.id')
                ->join('knowledge_entities as target', 'knowledge_relationships.target_entity_id', '=', 'target.id')
                ->where('source.session_id', session()->getId())
                ->select('source.name as source', 'target.name as target', 'knowledge_relationships.type', 'knowledge_relationships.strength')
                ->limit($limit * 2)
                ->get();
            
            // Format for D3.js force-directed graph
            $nodes = $entities->map(fn($e) => [
                'id' => $e->name,
                'type' => $e->type,
                'mentions' => $e->mentions,
                'first_seen_url' => $e->first_seen_url,
            ])->values()->toArray();
            
            $links = $relationships->map(fn($r) => [
                'source' => $r->source,
                'target' => $r->target,
                'type' => $r->type,
                'strength' => $r->strength,
            ])->values()->toArray();
            
            return [
                'nodes' => $nodes,
                'links' => $links,
                'total_entities' => count($nodes),
                'total_relationships' => count($links),
            ];
        } catch (\Exception $e) {
            Log::warning("KnowledgeGraph: Failed to get graph data: " . $e->getMessage());
            return ['nodes' => [], 'links' => [], 'total_entities' => 0, 'total_relationships' => 0];
        }
    }
    
    /**
     * Find all connections for a specific entity
     */
    public function findConnections(string $entityName): array
    {
        try {
            $entity = DB::table('knowledge_entities')
                ->where('name', $entityName)
                ->where('session_id', session()->getId())
                ->first();
            
            if (!$entity) {
                return [];
            }
            
            // Get related entities
            $connections = DB::table('knowledge_relationships')
                ->join('knowledge_entities as other', function($join) use ($entity) {
                    $join->on('knowledge_relationships.target_entity_id', '=', 'other.id')
                         ->where('knowledge_relationships.source_entity_id', '=', $entity->id);
                })
                ->orWhere(function($query) use ($entity) {
                    $query->join('knowledge_entities as other', 'knowledge_relationships.source_entity_id', '=', 'other.id')
                          ->where('knowledge_relationships.target_entity_id', '=', $entity->id);
                })
                ->select('other.name', 'other.type', 'knowledge_relationships.type as relationship_type', 'knowledge_relationships.strength')
                ->get();
            
            return [
                'entity' => [
                    'name' => $entity->name,
                    'type' => $entity->type,
                    'mentions' => $entity->mentions,
                    'urls' => json_decode($entity->related_urls, true) ?? [],
                ],
                'connections' => $connections->toArray(),
            ];
        } catch (\Exception $e) {
            Log::warning("KnowledgeGraph: Failed to find connections: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search entities by name or type
     */
    public function searchEntities(string $query, string $type = null): array
    {
        try {
            $dbQuery = DB::table('knowledge_entities')
                ->where('session_id', session()->getId())
                ->where('name', 'LIKE', "%{$query}%");
            
            if ($type) {
                $dbQuery->where('type', $type);
            }
            
            return $dbQuery->orderBy('mentions', 'desc')
                ->limit(20)
                ->get()
                ->map(fn($e) => [
                    'name' => $e->name,
                    'type' => $e->type,
                    'mentions' => $e->mentions,
                    'first_seen_url' => $e->first_seen_url,
                ])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear session knowledge graph
     */
    public function clearSession(): void
    {
        try {
            DB::table('knowledge_entities')->where('session_id', session()->getId())->delete();
            DB::table('knowledge_relationships')
                ->join('knowledge_entities', 'knowledge_relationships.source_entity_id', '=', 'knowledge_entities.id')
                ->where('knowledge_entities.session_id', session()->getId())
                ->delete();
        } catch (\Exception $e) {
            Log::warning("KnowledgeGraph: Failed to clear session: " . $e->getMessage());
        }
    }
    
    // ── Private Methods ───────────────────────────────────────────────────────
    
    /**
     * Parse entities from HTML using simple pattern matching
     * For production, integrate with NLP service (spaCy, Stanford NER, etc.)
     */
    private function parseEntities(string $html): array
    {
        $entities = [];
        
        // Strip HTML tags
        $text = strip_tags($html);
        
        // Extract potential person names (capitalized words in sequence)
        if (preg_match_all('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,2})\b/', $text, $matches)) {
            foreach ($matches[1] as $name) {
                // Filter out common false positives
                if (!$this->isCommonPhrase($name)) {
                    $entities[] = [
                        'name' => $name,
                        'type' => 'person',
                        'confidence' => 0.7,
                    ];
                }
            }
        }
        
        // Extract organizations (words ending with Inc, Corp, Ltd, etc.)
        if (preg_match_all('/\b([A-Z][a-zA-Z\s]+(?:Inc|Corp|Ltd|LLC|Company|Corporation|Organization))\b/', $text, $matches)) {
            foreach ($matches[1] as $org) {
                $entities[] = [
                    'name' => trim($org),
                    'type' => 'organization',
                    'confidence' => 0.85,
                ];
            }
        }
        
        // Extract locations (cities, countries - capitalized after prepositions)
        if (preg_match_all('/\b(?:in|from|to|at)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\b/', $text, $matches)) {
            foreach ($matches[1] as $location) {
                $entities[] = [
                    'name' => $location,
                    'type' => 'location',
                    'confidence' => 0.65,
                ];
            }
        }
        
        // Extract dates/times
        if (preg_match_all('/\b(\d{4}[-\/]\d{2}[-\/]\d{2}|\w+\s+\d{1,2},?\s+\d{4})\b/', $text, $matches)) {
            foreach ($matches[1] as $date) {
                $entities[] = [
                    'name' => $date,
                    'type' => 'date',
                    'confidence' => 0.9,
                ];
            }
        }
        
        // Deduplicate
        $unique = [];
        foreach ($entities as $entity) {
            $key = strtolower($entity['name']);
            if (!isset($unique[$key]) || $entity['confidence'] > $unique[$key]['confidence']) {
                $unique[$key] = $entity;
            }
        }
        
        return array_values($unique);
    }
    
    private function isCommonPhrase(string $text): bool
    {
        $commonPhrases = [
            'The United States', 'The European Union', 'The World Bank',
            'New York', 'Los Angeles', 'San Francisco',
            'Artificial Intelligence', 'Machine Learning',
        ];
        
        foreach ($commonPhrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function storeEntity(array $entity, string $url): void
    {
        try {
            // Check if entity exists
            $existing = DB::table('knowledge_entities')
                ->where('name', $entity['name'])
                ->where('session_id', session()->getId())
                ->first();
            
            if ($existing) {
                // Update mention count and URLs
                $relatedUrls = json_decode($existing->related_urls, true) ?? [];
                if (!in_array($url, $relatedUrls)) {
                    $relatedUrls[] = $url;
                }
                
                DB::table('knowledge_entities')
                    ->where('id', $existing->id)
                    ->update([
                        'mentions' => DB::raw('mentions + 1'),
                        'last_seen_at' => now(),
                        'related_urls' => json_encode(array_slice($relatedUrls, 0, 10)),
                    ]);
            } else {
                // Insert new entity
                DB::table('knowledge_entities')->insert([
                    'session_id' => session()->getId(),
                    'user_id' => auth()->id(),
                    'name' => $entity['name'],
                    'type' => $entity['type'],
                    'confidence' => $entity['confidence'],
                    'mentions' => 1,
                    'first_seen_url' => $url,
                    'related_urls' => json_encode([$url]),
                    'created_at' => now(),
                    'last_seen_at' => now(),
                ]);
            }
            
            // Create relationships with other entities from same page
            $this->createRelationships($entity['name'], $url);
            
        } catch (\Exception $e) {
            Log::warning("KnowledgeGraph: Failed to store entity: " . $e->getMessage());
        }
    }
    
    private function createRelationships(string $entityName, string $url): void
    {
        try {
            // Get all entities from this URL
            $pageEntities = DB::table('knowledge_entities')
                ->where('session_id', session()->getId())
                ->whereJsonContains('related_urls', $url)
                ->pluck('id', 'name')
                ->toArray();
            
            // Create relationships between co-occurring entities
            foreach ($pageEntities as $otherName => $otherId) {
                if ($otherName !== $entityName) {
                    // Check if relationship exists
                    $existing = DB::table('knowledge_relationships')
                        ->join('knowledge_entities as source', 'knowledge_relationships.source_entity_id', '=', 'source.id')
                        ->join('knowledge_entities as target', 'knowledge_relationships.target_entity_id', '=', 'target.id')
                        ->where('source.name', $entityName)
                        ->where('target.name', $otherName)
                        ->where('source.session_id', session()->getId())
                        ->first();
                    
                    if ($existing) {
                        // Strengthen existing relationship
                        DB::table('knowledge_relationships')
                            ->where('id', $existing->id)
                            ->update([
                                'strength' => DB::raw('strength + 1'),
                                'co_occurrence_count' => DB::raw('co_occurrence_count + 1'),
                            ]);
                    } else {
                        // Create new relationship
                        $sourceId = DB::table('knowledge_entities')
                            ->where('name', $entityName)
                            ->where('session_id', session()->getId())
                            ->value('id');
                        
                        if ($sourceId) {
                            DB::table('knowledge_relationships')->insert([
                                'source_entity_id' => $sourceId,
                                'target_entity_id' => $otherId,
                                'type' => 'co_occurrence',
                                'strength' => 1,
                                'co_occurrence_count' => 1,
                                'context_url' => $url,
                                'created_at' => now(),
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("KnowledgeGraph: Failed to create relationships: " . $e->getMessage());
        }
    }
}
