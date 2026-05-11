<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ApiKey;
use Illuminate\Support\Str;

class ProjectService
{
    /**
     * Create a new project
     */
    public function createProject($user, array $data): Project
    {
        $project = $user->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'settings' => $data['settings'] ?? [],
        ]);

        // Generate default API key
        $this->generateDefaultApiKey($project);

        return $project;
    }

    /**
     * Update project
     */
    public function updateProject(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'description' => $data['description'] ?? $project->description,
            'settings' => $data['settings'] ?? $project->settings,
        ]);

        return $project;
    }

    /**
     * Archive project
     */
    public function archiveProject(Project $project): bool
    {
        return $project->update(['status' => 'archived']);
    }

    /**
     * Restore archived project
     */
    public function restoreProject(Project $project): bool
    {
        return $project->update(['status' => 'active']);
    }

    /**
     * Delete project permanently
     */
    public function deleteProject(Project $project): bool
    {
        // Check if project has active subscriptions
        if ($project->hasActiveSubscription()) {
            throw new \Exception('Cannot delete project with active subscription. Cancel subscription first.');
        }

        return $project->delete();
    }

    /**
     * Generate default API key for project
     */
    protected function generateDefaultApiKey(Project $project): ApiKey
    {
        return $project->apiKeys()->create([
            'key' => 'yg_' . bin2hex(random_bytes(32)),
            'name' => 'Default API Key',
            'rate_limit' => 1000,
            'restrictions' => [],
        ]);
    }

    /**
     * Get project statistics
     */
    public function getProjectStats(Project $project): array
    {
        return [
            'total_api_keys' => $project->apiKeys()->count(),
            'active_api_keys' => $project->apiKeys()->active()->count(),
            'oauth_apps' => $project->oauthApplications()->count(),
            'play_store_apps' => $project->playStoreApps()->count(),
            'total_spend' => $project->total_spend,
            'api_calls_today' => $project->api_calls_today,
            'team_members' => $project->teamMembers()->count(),
            'webhook_endpoints' => $project->webhookEndpoints()->count(),
        ];
    }
}
