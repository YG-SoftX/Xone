<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\QuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiQuota
{
    protected $quotaService;

    public function __construct(QuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header
        $apiKey = $request->header('X-API-Key') ?? $request->header('Authorization');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide your API key in the X-API-Key header',
            ], 401);
        }

        // Remove "Bearer " prefix if present
        $apiKey = str_replace('Bearer ', '', $apiKey);

        // Find API key
        $apiToken = ApiKey::where('key', $apiKey)
            ->with('project')
            ->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not valid',
            ], 401);
        }

        // Check if API key is active
        if (!$apiToken->is_active) {
            return response()->json([
                'error' => 'API key disabled',
                'message' => 'This API key has been disabled',
            ], 403);
        }

        // Check if project is active
        if ($apiToken->project && !$apiToken->project->is_active) {
            return response()->json([
                'error' => 'Project inactive',
                'message' => 'The associated project is no longer active',
            ], 403);
        }

        // Check quota limits
        $quotaCheck = $this->quotaService->checkQuota($apiToken);
        
        if (!$quotaCheck['allowed']) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => $quotaCheck['message'],
                'quota' => [
                    'limit' => $quotaCheck['limit'],
                    'used' => $quotaCheck['used'],
                    'remaining' => 0,
                    'reset_at' => $quotaCheck['reset_at'],
                ],
            ], 429); // Too Many Requests
        }

        // Increment usage counter
        $this->quotaService->incrementUsage($apiToken);

        // Add quota info to request for downstream use
        $request->merge([
            'api_key' => $apiToken,
            'quota_remaining' => $quotaCheck['remaining'],
        ]);

        return $next($request);
    }
}
