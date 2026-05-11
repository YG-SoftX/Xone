<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\OAuthApplication;
use App\Services\OAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OAuthApplicationController extends Controller
{
    protected OAuthService $oauthService;

    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * List OAuth applications for a project
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $apps = $project->oauthApplications()
            ->select(['id', 'name', 'client_id', 'is_confidential', 'created_at'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $apps,
        ]);
    }

    /**
     * Create new OAuth application
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => 'required|url',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|in:read,write,delete,admin',
            'is_confidential' => 'boolean',
        ]);

        try {
            $app = $this->oauthService->createApplication($project, $validated);

            return response()->json([
                'success' => true,
                'message' => 'OAuth application created successfully',
                'data' => [
                    'client_id' => $app->client_id,
                    'client_secret' => $app->client_secret,
                    'warning' => 'Store client_secret securely. It will not be shown again.',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create OAuth application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update OAuth application
     */
    public function update(Request $request, OAuthApplication $app): JsonResponse
    {
        $this->authorize('update', $app->project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'redirect_uris' => 'sometimes|array',
            'redirect_uris.*' => 'url',
            'scopes' => 'sometimes|array',
        ]);

        try {
            $app = $this->oauthService->updateApplication($app, $validated);

            return response()->json([
                'success' => true,
                'message' => 'OAuth application updated successfully',
                'data' => $app,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update OAuth application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete OAuth application
     */
    public function destroy(OAuthApplication $app): JsonResponse
    {
        $this->authorize('delete', $app->project);

        $this->oauthService->deleteApplication($app);

        return response()->json([
            'success' => true,
            'message' => 'OAuth application deleted successfully',
        ]);
    }

    /**
     * Regenerate client secret
     */
    public function regenerateSecret(OAuthApplication $app): JsonResponse
    {
        $this->authorize('update', $app->project);

        try {
            $newSecret = $this->oauthService->regenerateSecret($app);

            return response()->json([
                'success' => true,
                'message' => 'Client secret regenerated successfully',
                'data' => [
                    'client_secret' => $newSecret,
                    'warning' => 'Store new secret securely. It will not be shown again.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate secret',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
