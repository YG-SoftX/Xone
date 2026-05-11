<?php

namespace App\Http\Middleware;

use App\Models\ApiCredential;
use App\Models\ProjectQuota;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * QuotaEnforcementMiddleware
 * 
 * Enforces API rate limits and quotas per credential.
 * Checks usage against configured limits before processing requests.
 */
class QuotaEnforcementMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API credential from request
        $credential = $this->getCredential($request);
        
        if (!$credential) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API credentials',
            ], 401);
        }

        // Check if credential is active
        if (!$credential->is_active) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'API credential has been revoked',
            ], 403);
        }

        // Check quota limits
        $quotaCheck = $this->checkQuota($credential);
        
        if (!$quotaCheck['allowed']) {
            return response()->json([
                'error' => 'Rate Limit Exceeded',
                'message' => $quotaCheck['message'],
                'retry_after' => $quotaCheck['retry_after'],
                'limit' => $quotaCheck['limit'],
                'remaining' => 0,
                'reset_at' => $quotaCheck['reset_at'],
            ], 429)->header('Retry-After', (string) $quotaCheck['retry_after']);
        }

        // Add quota headers to response
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Limit', (string) $quotaCheck['limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $quotaCheck['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $quotaCheck['reset_at']->timestamp);

        return $response;
    }

    /**
     * Get API credential from request.
     */
    protected function getCredential(Request $request): ?ApiCredential
    {
        // Try Bearer token first
        $token = $request->bearerToken();
        
        if ($token) {
            return ApiCredential::where('token', hash('sha256', $token))
                ->with('project')
                ->first();
        }

        // Try API key from header
        $apiKey = $request->header('X-API-Key');
        
        if ($apiKey) {
            return ApiCredential::where('api_key', $apiKey)
                ->with('project')
                ->first();
        }

        return null;
    }

    /**
     * Check quota limits for credential.
     */
    protected function checkQuota(ApiCredential $credential): array
    {
        $cacheKey = "quota:{$credential->id}:" . now()->format('Y-m-d-H');
        $dailyCacheKey = "quota:daily:{$credential->id}:" . now()->format('Y-m-d');

        // Get quota configuration
        $quota = ProjectQuota::where('project_id', $credential->project_id)
            ->where('credential_type', $credential->type)
            ->first();

        if (!$quota) {
            // Use default limits if no specific quota configured
            $hourlyLimit = 1000;
            $dailyLimit = 10000;
        } else {
            $hourlyLimit = $quota->hourly_limit ?? 1000;
            $dailyLimit = $quota->daily_limit ?? 10000;
        }

        // Check hourly limit
        $hourlyUsage = Cache::get($cacheKey, 0);
        
        if ($hourlyUsage >= $hourlyLimit) {
            $resetAt = now()->startOfHour()->addHour();
            return [
                'allowed' => false,
                'message' => "Hourly API request limit exceeded ({$hourlyLimit}/hour)",
                'retry_after' => $resetAt->diffInSeconds(now()),
                'limit' => $hourlyLimit,
                'remaining' => 0,
                'reset_at' => $resetAt,
            ];
        }

        // Check daily limit
        $dailyUsage = Cache::get($dailyCacheKey, 0);
        
        if ($dailyUsage >= $dailyLimit) {
            $resetAt = now()->startOfDay()->addDay();
            return [
                'allowed' => false,
                'message' => "Daily API request limit exceeded ({$dailyLimit}/day)",
                'retry_after' => $resetAt->diffInSeconds(now()),
                'limit' => $dailyLimit,
                'remaining' => 0,
                'reset_at' => $resetAt,
            ];
        }

        // Increment usage counters
        Cache::increment($cacheKey);
        Cache::increment($dailyCacheKey);
        
        // Set expiration if not set
        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 1, now()->endOfHour());
        }
        if (!Cache::has($dailyCacheKey)) {
            Cache::put($dailyCacheKey, 1, now()->endOfDay());
        }

        return [
            'allowed' => true,
            'message' => null,
            'retry_after' => 0,
            'limit' => $hourlyLimit,
            'remaining' => max(0, $hourlyLimit - ($hourlyUsage + 1)),
            'reset_at' => now()->startOfHour()->addHour(),
        ];
    }
}
