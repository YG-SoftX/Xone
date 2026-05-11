<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiKeyController extends Controller
{
    protected ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * List API keys for a project
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $keys = $project->apiKeys()
            ->select(['id', 'name', 'rate_limit', 'last_used_at', 'expires_at', 'created_at'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $keys,
        ]);
    }

    /**
     * Generate new API key
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'restrictions' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $apiKey = $this->apiKeyService->generateApiKey($project, $validated);

            // Get the plain key from cache (shown only once)
            $plainKey = cache('api_key_temp_' . $apiKey->id);

            return response()->json([
                'success' => true,
                'message' => 'API key generated successfully',
                'data' => [
                    'key' => $plainKey,
                    'warning' => 'Store this key securely. It will not be shown again.',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate API key',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke API key
     */
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('delete', $apiKey->project);

        $this->apiKeyService->revokeApiKey($apiKey);

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully',
        ]);
    }

    /**
     * Rotate API key
     */
    public function rotate(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey->project);

        try {
            $newKey = $this->apiKeyService->rotateApiKey($apiKey);
            $plainKey = cache('api_key_temp_' . $newKey->id);

            return response()->json([
                'success' => true,
                'message' => 'API key rotated successfully',
                'data' => [
                    'key' => $plainKey,
                    'warning' => 'Store this key securely. It will not be shown again.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rotate API key',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get remaining requests for API key
     */
    public function remaining(ApiKey $apiKey): JsonResponse
    {
        $remaining = $this->apiKeyService->getRemainingRequests($apiKey);

        return response()->json([
            'success' => true,
            'data' => [
                'remaining' => $remaining,
                'limit' => $apiKey->rate_limit,
            ],
        ]);
    }
}
