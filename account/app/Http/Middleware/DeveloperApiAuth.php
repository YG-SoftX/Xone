<?php

namespace App\Http\Middleware;

use App\Models\ApiCredential;
use App\Models\DeveloperProject;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Developer Platform API Authentication Middleware.
 * 
 * Validates API credentials from X-API-Key header and enforces:
 * - Credential existence and active status
 * - Expiration check
 * - IP/referrer restrictions
 * - Project quota limits
 * - Usage logging
 * 
 * Usage:
 *   GET /api/sso/validate
 *   X-API-Key: yg_sk_abc123...
 */
class DeveloperApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') 
            ?? $request->header('X-YG-API-Key')
            ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Missing API key',
                'message' => 'Provide your API key via X-API-Key header or api_key query parameter.',
                'docs' => 'https://account.ygxone.com/developer/docs',
            ], 401);
        }

        // Find credential by hashed key
        $hashedKey = hash('sha256', $apiKey);
        $credential = ApiCredential::where('identifier', $apiKey)
            ->orWhere('secret', $hashedKey)
            ->with(['project', 'project.quotas'])
            ->first();

        if (!$credential) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not recognized.',
            ], 401);
        }

        // Check if credential is active
        if (!$credential->is_active) {
            return response()->json([
                'error' => 'API key disabled',
                'message' => 'This API key has been disabled. Contact the project owner.',
            ], 403);
        }

        // Check expiration
        if ($credential->isExpired()) {
            return response()->json([
                'error' => 'API key expired',
                'message' => 'This API key expired on ' . $credential->expires_at->format('Y-m-d H:i:s'),
            ], 403);
        }

        // Check project is active
        if (!$credential->project->is_active) {
            return response()->json([
                'error' => 'Project disabled',
                'message' => 'The project associated with this API key has been disabled.',
            ], 403);
        }

        // Check restrictions
        $ip = $request->ip();
        $referrer = $request->header('Referer') ?? $request->header('Origin');
        $userAgent = $request->userAgent();

        if (!$credential->matchesRestrictions($ip, $referrer, $userAgent)) {
            return response()->json([
                'error' => 'Request restricted',
                'message' => 'This request does not match the configured restrictions for this API key.',
            ], 403);
        }

        // Check quota
        $product = $this->guessProductFromEndpoint($request);
        if ($product && $credential->project->isQuotaExceeded($product)) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => 'Your project has exceeded its API quota. Upgrade your plan or wait for the next reset period.',
                'quota' => [
                    'daily_remaining' => 0,
                    'monthly_remaining' => 0,
                ],
            ], 429)->header('Retry-After', '3600');
        }

        // Attach resolved credential to request for downstream controllers
        $request->attributes->set('api_credential', $credential);
        $request->attributes->set('developer_project', $credential->project);

        // Set user resolver to project owner (for auth-dependent downstream code)
        $request->setUserResolver(fn() => $credential->project->owner);

        // Add rate limit header
        $rateLimit = 60; // per minute default
        if ($product) {
            $quota = $credential->project->quotas()->where('product_id', $product->id)->first();
            if ($quota) {
                $rateLimit = $quota->rate_limit_per_minute;
            }
        }

        // Check rate limit (simple - could use Redis for production)
        $rateKey = "api_rate:{$credential->id}:" . now()->format('Hi');
        $currentRate = cache()->get($rateKey, 0);
        if ($currentRate >= $rateLimit) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "Rate limit of {$rateLimit} requests per minute exceeded.",
            ], 429)->header('Retry-After', '60');
        }

        cache()->increment($rateKey);
        cache()->put($rateKey, $currentRate + 1, now()->addMinute());

        // Record usage after response is sent (deferred)
        $startTime = microtime(true);
        $response = $next($request);
        $responseTime = round((microtime(true) - $startTime) * 1000);

        // Log usage asynchronously
        if ($product) {
            $credential->recordUsage(
                $request->path(),
                $request->method(),
                $response->getStatusCode(),
                $responseTime
            );
        }

        // Add response headers
        $response->headers->set('X-RateLimit-Limit', $rateLimit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $rateLimit - $currentRate - 1));
        $response->headers->set('X-Response-Time', "{$responseTime}ms");
        $response->headers->set('X-Project-ID', $credential->project->project_id);

        return $response;
    }

    /**
     * Guess which API product this request is for based on the endpoint.
     */
    protected function guessProductFromEndpoint(Request $request): ?\App\Models\ApiProduct
    {
        $path = $request->path();

        $productMap = [
            'api/sso' => 'sso',
            'api/mail' => 'mail',
            'api/drive' => 'drive',
            'api/pay' => 'pay',
            'api/meet' => 'meet',
        ];

        foreach ($productMap as $prefix => $productName) {
            if (str_starts_with($path, $prefix)) {
                return \App\Models\ApiProduct::where('name', $productName)->first();
            }
        }

        return null;
    }
}
