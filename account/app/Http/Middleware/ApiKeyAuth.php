<?php

namespace App\Http\Middleware;

use App\Models\ThirdPartyApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to authenticate API requests using Bearer tokens or API keys.
 * Usage:
 *   - Bearer token: Authorization: Bearer yg_sk_xxx
 *   - API key header: X-API-Key: yg_sk_xxx
 */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, string $guard = 'sanctum'): Response
    {
        // Try Sanctum bearer token first — validate the token, don't just check presence
        if ($request->bearerToken()) {
            $hashedToken = hash('sha256', $request->bearerToken());
            $token = \Laravel\Sanctum\PersonalAccessToken::where('token', $hashedToken)->first();

            if (!$token || $token->expires_at?->isPast()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or expired Bearer token.',
                ], 401);
            }

            $token->forceFill(['last_used_at' => now()])->save();
            $request->setUserResolver(fn () => $token->tokenable);
            $request->setLaravelSession(null);
            return $next($request);
        }

        // Try X-API-Key header (for service-to-service auth)
        $apiKey = $request->header('X-API-Key') ?? $request->header('X-YG-API-Key');

        if ($apiKey) {
            // Check if it's a service key format (yg_sk_*)
            if (str_starts_with($apiKey, 'yg_sk_') || str_starts_with($apiKey, 'yg_cs_')) {
                // Look up the token by hashed value
                $hashedKey = hash('sha256', $apiKey);
                $token = \Laravel\Sanctum\PersonalAccessToken::where('token', $hashedKey)->first();

                if ($token && !$token->expires_at?->isPast()) {
                    // Touch last used
                    $token->touch();
                    $request->setUserResolver(fn() => $token->tokenable);
                    return $next($request);
                }
            }

            // Check if it's a third-party app client_id + client_secret
            $clientSecret = $request->header('X-Client-Secret');
            if ($clientSecret) {
                $app = ThirdPartyApp::where('client_id', $apiKey)->first();
                if ($app && $app->verifySecret($clientSecret) && $app->is_active) {
                    // For app-level auth, use the app owner as the authenticated user
                    $request->setUserResolver(fn() => $app->user);
                    return $next($request);
                }
            }
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Valid API key or Bearer token required.',
        ], 401);
    }
}
