<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * List all projects for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()->projects()
            ->withCount(['apiKeys', 'oauthApplications', 'playStoreApps'])
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Create a new project
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        try {
            $project = $this->projectService->createProject($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project->load('apiKeys'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project details
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $stats = $this->projectService->getProjectStats($project);

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project->load(['apiKeys', 'oauthApplications', 'teamMembers']),
                'statistics' => $stats,
            ],
        ]);
    }

    /**
     * Update project
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        try {
            $project = $this->projectService->updateProject($project, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete project
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        try {
            $this->projectService->deleteProject($project);

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archive project
     */
    public function archive(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->archiveProject($project);

        return response()->json([
            'success' => true,
            'message' => 'Project archived successfully',
        ]);
    }

    /**
     * Restore archived project
     */
    public function restore(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->restoreProject($project);

        return response()->json([
            'success' => true,
            'message' => 'Project restored successfully',
        ]);
    }
}
