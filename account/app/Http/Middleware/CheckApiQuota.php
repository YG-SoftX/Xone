<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiCredential;
use App\Models\ProjectQuota;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiQuota
{
    /**
     * Handle an incoming request to enforce API quotas and credentials
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API credential from request
        $credential = $this->extractCredential($request);

        if (!$credential) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Valid API credential required',
                'code' => 'INVALID_CREDENTIAL',
            ], 401);
        }

        // Check if credential is active
        if (!$credential->is_active) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'API credential has been deactivated',
                'code' => 'CREDENTIAL_INACTIVE',
            ], 403);
        }

        // Check if credential is expired
        if ($credential->isExpired()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'API credential has expired',
                'code' => 'CREDENTIAL_EXPIRED',
                'expires_at' => $credential->expires_at->toIso8601String(),
            ], 403);
        }

        // Check IP restrictions
        if ($credential->hasIpRestriction() && !$credential->isIpAllowed($request->ip())) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Your IP address is not allowed for this credential',
                'code' => 'IP_NOT_ALLOWED',
            ], 403);
        }

        // Get project and check quotas
        $project = $credential->project;
        
        if (!$project || !$project->is_active) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Project is inactive or deleted',
                'code' => 'PROJECT_INACTIVE',
            ], 403);
        }

        // Determine which product/API is being accessed
        $productId = $this->extractProductId($request);
        
        if ($productId) {
            // Check quota for specific product
            $quota = ProjectQuota::where('project_id', $project->id)
                ->where('product_id', $productId)
                ->first();

            if ($quota) {
                // Check daily quota
                if ($quota->isDailyQuotaExceeded()) {
                    return response()->json([
                        'error' => 'Too Many Requests',
                        'message' => 'Daily API quota exceeded',
                        'code' => 'DAILY_QUOTA_EXCEEDED',
                        'daily_limit' => $quota->daily_limit,
                        'daily_used' => $quota->daily_used,
                        'resets_at' => \Carbon\Carbon::parse($quota->daily_reset_date)->addDay()->toIso8601String(),
                    ], 429);
                }

                // Check monthly quota
                if ($quota->isMonthlyQuotaExceeded()) {
                    return response()->json([
                        'error' => 'Too Many Requests',
                        'message' => 'Monthly API quota exceeded',
                        'code' => 'MONTHLY_QUOTA_EXCEEDED',
                        'monthly_limit' => $quota->monthly_limit,
                        'monthly_used' => $quota->monthly_used,
                        'resets_at' => \Carbon\Carbon::parse($quota->monthly_reset_date)->addMonth()->toIso8601String(),
                    ], 429);
                }

                // Increment usage counters
                $quota->incrementUsage();
            }
        }

        // Increment credential usage counter
        $credential->incrementUsage();

        // Log the API request for analytics
        $this->logApiRequest($request, $credential, $project, $productId);

        // Add credential info to request for downstream use
        $request->merge([
            'api_credential' => $credential,
            'api_project' => $project,
        ]);

        return $next($request);
    }

    /**
     * Extract API credential from request.
     * Accepts Bearer token or X-API-Key header only — never query parameters.
     */
    protected function extractCredential(Request $request): ?ApiCredential
    {
        $token = null;

        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        } elseif ($request->header('X-API-Key')) {
            $token = $request->header('X-API-Key');
        }

        if (!$token) {
            return null;
        }

        return ApiCredential::where('identifier', $token)
            ->with('project')
            ->first();
    }

    /**
     * Extract product ID from request path or headers
     */
    protected function extractProductId(Request $request): ?int
    {
        // Method 1: From request header
        if ($request->header('X-YG-Product')) {
            $productName = $request->header('X-YG-Product');
            $product = \App\Models\ApiProduct::where('name', $productName)->first();
            return $product?->id;
        }

        // Method 2: From URL path pattern /api/{product}/...
        $path = $request->path();
        $segments = explode('/', $path);
        
        if (count($segments) >= 2) {
            $potentialProduct = $segments[1]; // e.g., /api/sso/...
            
            // Map common product names
            $productMap = [
                'sso' => 'account',
                'account' => 'account',
                'mail' => 'mail',
                'pay' => 'pay',
                'drive' => 'drive',
                'meet' => 'meet',
                'docx' => 'docx',
            ];

            if (isset($productMap[$potentialProduct])) {
                $product = \App\Models\ApiProduct::where('name', $productMap[$potentialProduct])->first();
                return $product?->id;
            }
        }

        return null;
    }

    /**
     * Log API request for analytics
     */
    protected function logApiRequest(Request $request, ApiCredential $credential, $project, ?int $productId): void
    {
        try {
            $sensitiveHeaders = ['authorization', 'x-api-key', 'x-yg-api-key', 'cookie', 'x-client-secret'];
            $safeHeaders = collect($request->headers->all())
                ->except($sensitiveHeaders)
                ->toArray();

            \App\Models\ApiUsageLog::create([
                'credential_id' => $credential->id,
                'project_id' => $project->id,
                'product_id' => $productId,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => null,
                'response_time_ms' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_metadata' => [
                    'query_params' => $request->query(),
                    'headers' => $safeHeaders,
                ],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't fail the request if logging fails
            \Log::warning('Failed to log API request: ' . $e->getMessage());
        }
    }
}
