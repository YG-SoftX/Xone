<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\ApiUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotaService
{
    /**
     * Check if API key has remaining quota
     */
    public function checkQuota(ApiKey $apiKey): array
    {
        $project = $apiKey->project;
        
        if (!$project) {
            return [
                'allowed' => false,
                'message' => 'No associated project found',
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'reset_at' => null,
            ];
        }

        // Get quota limits from project plan
        $dailyLimit = $project->daily_request_limit ?? 1000;
        $monthlyLimit = $project->monthly_request_limit ?? 30000;

        // Calculate current usage
        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $todayUsage = ApiUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $monthlyUsage = ApiUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Check daily limit
        if ($todayUsage >= $dailyLimit) {
            Log::warning("Daily quota exceeded", [
                'api_key_id' => $apiKey->id,
                'used' => $todayUsage,
                'limit' => $dailyLimit,
            ]);

            return [
                'allowed' => false,
                'message' => "Daily API quota exceeded ({$todayUsage}/{$dailyLimit})",
                'limit' => $dailyLimit,
                'used' => $todayUsage,
                'remaining' => 0,
                'reset_at' => now()->endOfDay(),
            ];
        }

        // Check monthly limit
        if ($monthlyUsage >= $monthlyLimit) {
            Log::warning("Monthly quota exceeded", [
                'api_key_id' => $apiKey->id,
                'used' => $monthlyUsage,
                'limit' => $monthlyLimit,
            ]);

            return [
                'allowed' => false,
                'message' => "Monthly API quota exceeded ({$monthlyUsage}/{$monthlyLimit})",
                'limit' => $monthlyLimit,
                'used' => $monthlyUsage,
                'remaining' => 0,
                'reset_at' => now()->endOfMonth(),
            ];
        }

        return [
            'allowed' => true,
            'message' => 'OK',
            'limit' => $dailyLimit,
            'used' => $todayUsage,
            'remaining' => $dailyLimit - $todayUsage,
            'reset_at' => now()->endOfDay(),
        ];
    }

    /**
     * Increment API usage counter
     */
    public function incrementUsage(ApiKey $apiKey): void
    {
        try {
            ApiUsage::create([
                'api_key_id' => $apiKey->id,
                'project_id' => $apiKey->project_id,
                'endpoint' => request()->path(),
                'method' => request()->method(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'response_time_ms' => null, // Will be updated after response
                'status_code' => null, // Will be updated after response
                'created_at' => now(),
            ]);

            // Update project total usage
            if ($apiKey->project) {
                $apiKey->project->increment('total_requests');
                $apiKey->project->increment('requests_today');
            }
        } catch (\Exception $e) {
            Log::error("Failed to record API usage", [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get usage statistics for a project
     */
    public function getProjectUsage(int $projectId, string $period = 'day'): array
    {
        $query = ApiUsage::where('project_id', $projectId);

        switch ($period) {
            case 'hour':
                $query->where('created_at', '>=', now()->subHour());
                break;
            case 'day':
                $query->where('created_at', '>=', now()->startOfDay());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            default:
                $query->where('created_at', '>=', now()->startOfDay());
        }

        $totalRequests = $query->count();
        $successfulRequests = (clone $query)->where('status_code', '<', 400)->count();
        $failedRequests = (clone $query)->where('status_code', '>=', 400)->count();
        $avgResponseTime = (clone $query)->avg('response_time_ms') ?? 0;

        // Top endpoints
        $topEndpoints = (clone $query)
            ->select('endpoint', DB::raw('count(*) as count'))
            ->groupBy('endpoint')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Hourly breakdown (last 24 hours)
        $hourlyBreakdown = ApiUsage::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDay())
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        return [
            'period' => $period,
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $failedRequests,
            'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            'avg_response_time_ms' => round($avgResponseTime, 2),
            'top_endpoints' => $topEndpoints,
            'hourly_breakdown' => $hourlyBreakdown,
        ];
    }

    /**
     * Reset daily counters at midnight
     */
    public function resetDailyCounters(): int
    {
        try {
            return DB::table('projects')
                ->update(['requests_today' => 0]);
        } catch (\Exception $e) {
            Log::error("Failed to reset daily counters", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Clean old usage logs (older than 90 days)
     */
    public function cleanupOldLogs(): int
    {
        try {
            return ApiUsage::where('created_at', '<', now()->subDays(90))->delete();
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old usage logs", [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
