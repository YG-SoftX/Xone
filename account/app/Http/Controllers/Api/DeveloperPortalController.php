<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThirdPartyApp;
use App\Models\ThirdPartyAuthLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeveloperPortalController extends Controller
{
    /**
     * List all registered third-party apps for the user.
     * GET /api/developer/apps
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $apps = $user->thirdPartyApps()->orderBy('created_at', 'desc')->get()->map(function ($app) {
            return [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'description' => $app->description,
                'website_url' => $app->website_url,
                'redirect_uri' => $app->redirect_uri,
                'client_id' => $app->client_id,
                'scopes' => $app->scopes ?? ['sso'],
                'is_active' => $app->is_active,
                'last_used_at' => $app->last_used_at?->diffForHumans(),
                'total_auth_requests' => $app->total_auth_requests,
                'total_api_calls' => $app->total_api_calls,
                'created_at' => $app->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json(['apps' => $apps]);
    }

    /**
     * Register a new third-party app.
     * POST /api/developer/apps
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:third_party_apps,slug',
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url',
            'redirect_uri' => 'required|url',
            'scopes' => 'array',
        ]);

        $user = $request->user();

        $app = ThirdPartyApp::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'description' => $request->input('description'),
            'website_url' => $request->input('website_url'),
            'redirect_uri' => $request->input('redirect_uri'),
            'scopes' => $request->input('scopes', ['sso']),
        ]);

        ActivityLog::record(
            $user->id,
            'Registered third-party app: ' . $app->name,
            'Developer',
            '📱',
            $request->ip()
        );

        return response()->json([
            'app' => $this->formatAppResponse($app),
            'message' => 'App registered successfully. Save your client_secret — it will not be shown again.',
        ], 201);
    }

    /**
     * Show a specific third-party app.
     * GET /api/developer/apps/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $app = $user->thirdPartyApps()->findOrFail($id);

        return response()->json([
            'app' => $this->formatAppResponse($app),
        ]);
    }

    /**
     * Update a third-party app.
     * PUT /api/developer/apps/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $app = $user->thirdPartyApps()->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url',
            'redirect_uri' => 'sometimes|url',
            'scopes' => 'array',
            'is_active' => 'boolean',
        ]);

        $app->update($request->only([
            'name', 'description', 'website_url', 'redirect_uri', 'scopes', 'is_active',
        ]));

        ActivityLog::record(
            $user->id,
            'Updated third-party app: ' . $app->name,
            'Developer',
            '📝',
            $request->ip()
        );

        return response()->json([
            'app' => $this->formatAppResponse($app),
            'message' => 'App updated successfully.',
        ]);
    }

    /**
     * Delete a third-party app.
     * DELETE /api/developer/apps/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $app = $user->thirdPartyApps()->findOrFail($id);
        $appName = $app->name;
        $app->delete();

        ActivityLog::record(
            $user->id,
            'Deleted third-party app: ' . $appName,
            'Developer',
            '🗑️',
            $request->ip()
        );

        return response()->json(['message' => 'App deleted successfully.']);
    }

    /**
     * Rotate the client secret.
     * POST /api/developer/apps/{id}/rotate-secret
     */
    public function rotateSecret(Request $request, $id)
    {
        $user = $request->user();
        $app = $user->thirdPartyApps()->findOrFail($id);

        $newSecret = 'yg_cs_' . Str::random(48);
        $app->client_secret = hash('sha256', $newSecret);
        $app->save();

        ActivityLog::record(
            $user->id,
            'Rotated secret for app: ' . $app->name,
            'Developer',
            '🔑',
            $request->ip()
        );

        return response()->json([
            'client_id' => $app->client_id,
            'client_secret' => $newSecret,
            'message' => 'Secret rotated. Update your integration immediately — the old secret is no longer valid.',
        ]);
    }

    /**
     * Get stats for a third-party app.
     * GET /api/developer/apps/{id}/stats
     */
    public function stats(Request $request, $id)
    {
        $user = $request->user();
        $app = $user->thirdPartyApps()->findOrFail($id);

        $authLogs = $app->authLogs();

        $recentActivity = ThirdPartyAuthLog::where('app_id', $app->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'event_type' => $log->event_type,
                    'user_id' => $log->user_id,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        $stats = [
            'total_auth_requests' => $app->total_auth_requests,
            'total_api_calls' => $app->total_api_calls,
            'total_validations_today' => $authLogs->where('event_type', 'sso_validated')
                ->whereDate('created_at', today())->count(),
            'total_validations_this_week' => $authLogs->where('event_type', 'sso_validated')
                ->whereDate('created_at', '>=', now()->subWeek())->count(),
            'unique_users' => $authLogs->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'recent_activity' => $recentActivity,
        ];

        return response()->json(['stats' => $stats]);
    }

    /**
     * Format app response with sensitive data handling.
     */
    protected function formatAppResponse(ThirdPartyApp $app): array
    {
        return [
            'id' => $app->id,
            'name' => $app->name,
            'slug' => $app->slug,
            'description' => $app->description,
            'website_url' => $app->website_url,
            'redirect_uri' => $app->redirect_uri,
            'client_id' => $app->client_id,
            'scopes' => $app->scopes ?? ['sso'],
            'is_active' => $app->is_active,
            'last_used_at' => $app->last_used_at?->diffForHumans(),
            'total_auth_requests' => $app->total_auth_requests,
            'total_api_calls' => $app->total_api_calls,
            'created_at' => $app->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $app->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
