<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\DeveloperProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CredentialController extends Controller
{
    /**
     * List credentials for a project
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        
        // Verify user has access to project
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $credentials = ApiCredential::where('project_id', $project->id)
            ->select('id', 'project_id', 'type', 'name', 'identifier', 
                    'scopes', 'restrictions', 'expires_at', 'last_used_at', 
                    'total_requests', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
            ],
            'credentials' => $credentials->map(function($cred) {
                return [
                    'id' => $cred->id,
                    'type' => $cred->type,
                    'name' => $cred->name,
                    'identifier' => $cred->identifier,
                    'scopes' => $cred->scopes,
                    'restrictions' => $cred->restrictions,
                    'expires_at' => $cred->expires_at?->toIso8601String(),
                    'last_used_at' => $cred->last_used_at?->toIso8601String(),
                    'total_requests' => $cred->total_requests,
                    'is_active' => $cred->is_active,
                    'is_expired' => $cred->isExpired(),
                    'created_at' => $cred->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $credentials->currentPage(),
                'per_page' => $credentials->perPage(),
                'total' => $credentials->total(),
                'last_page' => $credentials->lastPage(),
            ],
        ]);
    }

    /**
     * Create new API credential
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($projectId);

        $validated = $request->validate([
            'type' => 'required|in:api_key,oauth_client,service_account,webhook_secret',
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|max:100',
            'restrictions' => 'nullable|array',
            'restrictions.ip_addresses' => 'nullable|array',
            'restrictions.ip_addresses.*' => 'ip',
            'restrictions.referrers' => 'nullable|array',
            'restrictions.referrers.*' => 'url',
            'restrictions.apps' => 'nullable|array',
            'restrictions.apps.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Generate unique identifier based on type
        $identifier = $this->generateIdentifier($validated['type']);
        
        // Generate secret (will be hashed and never shown again)
        $secret = match($validated['type']) {
            'api_key' => 'yg_api_' . Str::random(40),
            'oauth_client' => 'yg_oauth_' . Str::random(40),
            'service_account' => 'yg_sa_' . Str::random(40),
            'webhook_secret' => 'yg_wh_' . Str::random(40),
        };

        $credential = ApiCredential::create([
            'project_id' => $project->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'identifier' => $identifier,
            'secret' => Hash::make($secret), // Hash the secret
            'scopes' => $validated['scopes'] ?? [],
            'restrictions' => $validated['restrictions'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'created_by_ip' => $request->ip(),
            'created_by_ua' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Credential created successfully. Save your secret key now - it will not be shown again!',
            'credential' => [
                'id' => $credential->id,
                'type' => $credential->type,
                'name' => $credential->name,
                'identifier' => $credential->identifier,
                'secret' => $secret, // ONLY time secret is shown
                'scopes' => $credential->scopes,
                'restrictions' => $credential->restrictions,
                'expires_at' => $credential->expires_at?->toIso8601String(),
                'is_active' => $credential->is_active,
                'created_at' => $credential->created_at->toIso8601String(),
            ],
            'warning' => 'Store this secret securely. It cannot be retrieved once you leave this page.',
        ], 201);
    }

    /**
     * Rotate credential secret
     */
    public function rotate(Request $request, $id)
    {
        $user = Auth::user();
        
        $credential = ApiCredential::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        // Generate new secret
        $newSecret = match($credential->type) {
            'api_key' => 'yg_api_' . Str::random(40),
            'oauth_client' => 'yg_oauth_' . Str::random(40),
            'service_account' => 'yg_sa_' . Str::random(40),
            'webhook_secret' => 'yg_wh_' . Str::random(40),
        };

        $credential->update([
            'secret' => Hash::make($newSecret),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Credential rotated successfully. Old secret is now invalid.',
            'credential' => [
                'id' => $credential->id,
                'identifier' => $credential->identifier,
                'new_secret' => $newSecret, // ONLY time new secret is shown
            ],
            'warning' => 'Update all applications using this credential immediately. The old secret no longer works.',
        ]);
    }

    /**
     * Toggle credential active status
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::user();
        
        $credential = ApiCredential::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        $newStatus = !$credential->is_active;
        $credential->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Credential activated' : 'Credential deactivated',
            'is_active' => $credential->is_active,
        ]);
    }

    /**
     * Delete credential
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $credential = ApiCredential::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        $credentialName = $credential->name;
        $credential->delete();

        return response()->json([
            'success' => true,
            'message' => "Credential '{$credentialName}' deleted successfully",
        ]);
    }

    /**
     * Generate unique identifier based on credential type
     */
    protected function generateIdentifier(string $type): string
    {
        $prefix = match($type) {
            'api_key' => 'ygk',
            'oauth_client' => 'ygo',
            'service_account' => 'ygs',
            'webhook_secret' => 'ygw',
        };

        return $prefix . '_' . Str::lower(Str::random(24));
    }
}
