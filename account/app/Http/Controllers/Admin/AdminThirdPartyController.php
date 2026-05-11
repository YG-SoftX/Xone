<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThirdPartyApp;
use App\Models\ThirdPartyAuthLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminThirdPartyController extends Controller
{
    /**
     * List all third-party apps.
     */
    public function index(Request $request)
    {
        $apps = ThirdPartyApp::with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'slug' => $app->slug,
                    'client_id' => $app->client_id,
                    'redirect_uri' => $app->redirect_uri,
                    'is_active' => $app->is_active,
                    'total_auth_requests' => $app->total_auth_requests,
                    'total_api_calls' => $app->total_api_calls,
                    'owner' => $app->user ? $app->user->name : 'Unknown',
                    'owner_email' => $app->user ? $app->user->email : 'N/A',
                    'created_at' => $app->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Admin/ThirdPartyApps', [
            'apps' => $apps,
        ]);
    }

    /**
     * Show details for a specific app.
     */
    public function show(Request $request, $id)
    {
        $app = ThirdPartyApp::with('user')->findOrFail($id);

        $recentLogs = ThirdPartyAuthLog::where('app_id', $app->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'user_email' => $log->user?->email,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Admin/ThirdPartyAppDetail', [
            'app' => [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'description' => $app->description,
                'website_url' => $app->website_url,
                'redirect_uri' => $app->redirect_uri,
                'client_id' => $app->client_id,
                'client_secret' => $this->maskSecret($app->client_secret),
                'scopes' => $app->scopes ?? ['sso'],
                'is_active' => $app->is_active,
                'total_auth_requests' => $app->total_auth_requests,
                'total_api_calls' => $app->total_api_calls,
                'owner' => $app->user?->name,
                'owner_email' => $app->user?->email,
                'created_at' => $app->created_at->format('Y-m-d H:i:s'),
            ],
            'authLogs' => $recentLogs,
        ]);
    }

    /**
     * Toggle app active status.
     */
    public function toggleStatus(Request $request, $id)
    {
        $app = ThirdPartyApp::findOrFail($id);
        $app->is_active = !$app->is_active;
        $app->save();

        return back()->with('success', $app->name . ($app->is_active ? ' activated.' : ' deactivated.'));
    }

    /**
     * Delete a third-party app.
     */
    public function destroy(Request $request, $id)
    {
        $app = ThirdPartyApp::findOrFail($id);
        $appName = $app->name;
        $app->delete();

        return back()->with('success', $appName . ' deleted.');
    }

    /**
     * Return a masked version of a secret: ••••••••abcd (last 4 chars visible).
     * The raw value is never sent to the frontend.
     */
    private function maskSecret(?string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $visible = min(4, strlen($secret));
        return str_repeat('•', max(0, strlen($secret) - $visible)) . substr($secret, -$visible);
    }

    /**
     * View all auth logs across all apps.
     */
    public function authLogs(Request $request)
    {
        $logs = ThirdPartyAuthLog::with(['app', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'app_name' => $log->app?->name ?? 'Unknown',
                    'event_type' => $log->event_type,
                    'user_email' => $log->user?->email,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Admin/ThirdPartyAuthLogs', [
            'logs' => $logs,
        ]);
    }
}
