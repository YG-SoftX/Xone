<?php

namespace App\Http\Controllers;

use App\Services\UnifiedSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnifiedSearchController extends Controller
{
    protected $searchService;

    public function __construct(UnifiedSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Perform unified search across all services
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:200',
            'type' => 'nullable|string|in:all,mail,drive,docs,xcel,contacts,calendar,chat,forms',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $filters = [
            'type' => $request->input('type', 'all'),
            'limit' => $request->input('limit', 10),
        ];

        try {
            $results = $this->searchService->search($query, $filters);

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get search suggestions/autocomplete
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 5);

        try {
            $suggestions = $this->searchService->getSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
            ], 500);
        }
    }

    /**
     * Index content from external service (webhook endpoint)
     */
    public function indexContent(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string|in:mail,drive,docs,xcel,contacts,calendar,chat,forms',
            'item_type' => 'required|string',
            'item_id' => 'required|integer',
            'user_id' => 'nullable|integer',
            'title' => 'nullable|string',
            'content' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $data = [
                'title' => $request->input('title', ''),
                'content' => $request->input('content', ''),
                'metadata' => $request->input('metadata', []),
            ];

            $this->searchService->indexContent(
                $request->input('service'),
                $request->input('item_type'),
                $request->input('item_id'),
                $data,
                $request->input('user_id')
            );

            return response()->json([
                'success' => true,
                'message' => 'Content indexed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Indexing failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove content from search index
     */
    public function removeContent(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string',
            'item_id' => 'required|integer',
            'user_id' => 'nullable|integer',
        ]);

        try {
            $this->searchService->removeContent(
                $request->input('service'),
                $request->input('item_id'),
                $request->input('user_id')
            );

            return response()->json([
                'success' => true,
                'message' => 'Content removed from index',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Removal failed',
            ], 500);
        }
    }

    /**
     * Rebuild search index for a service (admin only)
     */
    public function rebuildIndex(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string|in:mail,drive,docs,xcel,contacts,calendar,chat,forms',
        ]);

        // TODO: Add admin authorization check
        
        try {
            $count = $this->searchService->rebuildIndex($request->input('service'));

            return response()->json([
                'success' => true,
                'message' => "Indexed {$count} items",
                'indexed_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rebuild failed',
            ], 500);
        }
    }
}
