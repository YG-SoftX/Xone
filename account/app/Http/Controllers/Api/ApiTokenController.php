<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    /**
     * List all API tokens for the authenticated user.
     * GET /api/tokens
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tokens = $user->tokens()->orderBy('created_at', 'desc')->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities ?? ['*'],
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
            ];
        });

        return response()->json(['tokens' => $tokens]);
    }

    /**
     * Create a new API token.
     * POST /api/tokens
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = $request->user();
        $abilities = $request->input('abilities', ['*']);

        $token = $user->createToken(
            $request->input('name'),
            $abilities,
            $request->input('expires_at') ? new \DateTime($request->input('expires_at')) : null
        );

        ActivityLog::record(
            $user->id,
            'Created API token: ' . $request->input('name'),
            'API',
            '🔑',
            $request->ip()
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'message' => 'Token created successfully. Save this token — it will not be shown again.',
        ], 201);
    }

    /**
     * Revoke an API token.
     * DELETE /api/tokens/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $token = $user->tokens()->where('id', $id)->firstOrFail();
        $tokenName = $token->name;
        $token->delete();

        ActivityLog::record(
            $user->id,
            'Revoked API token: ' . $tokenName,
            'API',
            '🔒',
            $request->ip()
        );

        return response()->json(['message' => 'Token revoked.']);
    }

    /**
     * Generate a scoped service-to-service API key.
     * POST /api/tokens/service-key
     * Only for admin users.
     */
    public function generateServiceKey(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin' && $user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized. Admin only.'], 403);
        }

        $request->validate([
            'service_name' => 'required|string|max:255',
            'scopes' => 'array',
            'expires_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $scopes = $request->input('scopes', ['sso:initiate', 'sso:validate', 'user:read']);
        $expiresDays = $request->input('expires_days', 365);
        $expiresAt = now()->addDays($expiresDays);

        // Generate a prefixed service key
        $plainToken = 'yg_sk_' . Str::random(48);

        // Store in personal_access_tokens with metadata
        $tokenable = $user;
        $token = $tokenable->tokens()->create([
            'name' => 'Service: ' . $request->input('service_name'),
            'token' => hash('sha256', $plainToken),
            'abilities' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        ActivityLog::record(
            $user->id,
            'Generated service key for: ' . $request->input('service_name'),
            'API',
            '🔑',
            $request->ip()
        );

        return response()->json([
            'key' => $plainToken,
            'scopes' => $scopes,
            'expires_at' => $expiresAt->toISOString(),
            'message' => 'Save this key — it will not be shown again.',
        ], 201);
    }
}
