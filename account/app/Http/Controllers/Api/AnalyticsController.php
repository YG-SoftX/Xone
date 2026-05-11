<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProject;
use App\Models\ApiUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get analytics overview for a project
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        // Date range (default: last 30 days)
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);
        $endDate = now();

        // Total requests
        $totalRequests = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Success rate
        $successfulRequests = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status_code', '>=', 200)
            ->where('status_code', '<', 400)
            ->count();

        $successRate = $totalRequests > 0 
            ? round(($successfulRequests / $totalRequests) * 100, 2) 
            : 100;

        // Error rate
        $errorRate = 100 - $successRate;

        // Average response time
        $avgResponseTime = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        // Requests by product
        $requestsByProduct = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->join('api_products', 'api_usage_logs.product_id', '=', 'api_products.id')
            ->selectRaw('api_products.name, api_products.display_name, COUNT(*) as count')
            ->groupBy('api_products.id', 'api_products.name', 'api_products.display_name')
            ->orderByDesc('count')
            ->get();

        // Requests by endpoint (top 10)
        $topEndpoints = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('endpoint, method, COUNT(*) as count, AVG(response_time_ms) as avg_response_time')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Status code distribution
        $statusDistribution = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE 
                    WHEN status_code BETWEEN 200 AND 299 THEN "2xx Success"
                    WHEN status_code BETWEEN 300 AND 399 THEN "3xx Redirect"
                    WHEN status_code BETWEEN 400 AND 499 THEN "4xx Client Error"
                    WHEN status_code BETWEEN 500 AND 599 THEN "5xx Server Error"
                    ELSE "Other"
                END as category,
                COUNT(*) as count
            ')
            ->groupBy('category')
            ->get();

        // Daily usage trend (last N days)
        $dailyTrend = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->date => $item->count];
            });

        // Fill in missing dates with 0
        $completeTrend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $completeTrend[$date] = $dailyTrend[$date] ?? 0;
        }

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
            ],
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'overview' => [
                'total_requests' => $totalRequests,
                'success_rate' => $successRate,
                'error_rate' => $errorRate,
                'avg_response_time_ms' => round($avgResponseTime ?? 0, 2),
                'requests_per_day' => round($totalRequests / max($days, 1), 2),
            ],
            'by_product' => $requestsByProduct,
            'top_endpoints' => $topEndpoints,
            'status_distribution' => $statusDistribution,
            'daily_trend' => $completeTrend,
        ]);
    }

    /**
     * Export analytics data as CSV
     */
    public function export(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $logs = ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, now()])
            ->with(['product', 'credential'])
            ->orderBy('created_at', 'desc')
            ->limit(10000) // Limit export size
            ->get();

        // Generate CSV
        $csvData = "Timestamp,Endpoint,Method,Status Code,Response Time (ms),Product,Credential Type,IP Address\n";
        
        foreach ($logs as $log) {
            $csvData .= sprintf(
                "%s,%s,%s,%d,%d,%s,%s,%s\n",
                $log->created_at->toIso8601String(),
                $log->endpoint,
                $log->method,
                $log->status_code ?? 0,
                $log->response_time_ms ?? 0,
                $log->product?->name ?? 'N/A',
                $log->credential?->type ?? 'N/A',
                $log->ip_address ?? 'N/A'
            );
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="analytics_' . $project->project_id . '_' . now()->format('Y-m-d') . '.csv"');
    }

    /**
     * Get real-time usage stats (last hour)
     */
    public function realtime($projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $oneHourAgo = now()->subHour();

        // Requests in last hour
        $recentRequests = ApiUsageLog::where('project_id', $project->id)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        // Errors in last hour
        $recentErrors = ApiUsageLog::where('project_id', $project->id)
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status_code', '>=', 400)
            ->count();

        // Current RPS (requests per second)
        $rps = $recentRequests / 3600;

        return response()->json([
            'success' => true,
            'realtime' => [
                'requests_last_hour' => $recentRequests,
                'errors_last_hour' => $recentErrors,
                'current_rps' => round($rps, 2),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
