<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AggregateAnalytics Job
 * 
 * Aggregates analytics data from raw logs into summary tables.
 * Runs hourly/daily via scheduler for performance optimization.
 */
class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    protected string $period; // 'hourly', 'daily', 'weekly'
    protected ?string $projectId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $period, ?string $projectId = null)
    {
        $this->period = $period;
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting analytics aggregation', [
            'period' => $this->period,
            'project_id' => $this->projectId,
        ]);

        try {
            DB::transaction(function () {
                match ($this->period) {
                    'hourly' => $this->aggregateHourly(),
                    'daily' => $this->aggregateDaily(),
                    'weekly' => $this->aggregateWeekly(),
                    default => throw new \InvalidArgumentException("Invalid period: {$this->period}"),
                };
            });

            Log::info('Analytics aggregation completed successfully', [
                'period' => $this->period,
                'project_id' => $this->projectId,
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics aggregation failed', [
                'period' => $this->period,
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Aggregate hourly analytics.
     */
    protected function aggregateHourly(): void
    {
        // Aggregate API usage by hour
        DB::statement("
            INSERT INTO api_usage_hourly (
                project_id, endpoint, method, status_code, 
                request_count, avg_response_time, error_count, created_at
            )
            SELECT 
                project_id,
                endpoint,
                method,
                status_code,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_time,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour
            FROM api_usage_logs
            WHERE created_at >= NOW() - INTERVAL 2 HOUR
            AND created_at < NOW() - INTERVAL 1 HOUR
            " . ($this->projectId ? "AND project_id = :project_id" : "") . "
            GROUP BY project_id, endpoint, method, status_code, hour
            ON DUPLICATE KEY UPDATE
                request_count = VALUES(request_count),
                avg_response_time = VALUES(avg_response_time),
                error_count = VALUES(error_count)
        ", array_filter(['project_id' => $this->projectId]));
    }

    /**
     * Aggregate daily analytics.
     */
    protected function aggregateDaily(): void
    {
        // Aggregate daily metrics
        DB::statement("
            INSERT INTO api_usage_daily (
                project_id, date, total_requests, unique_endpoints,
                total_errors, avg_response_time, peak_hour
            )
            SELECT 
                project_id,
                DATE(created_at) as date,
                COUNT(*) as total_requests,
                COUNT(DISTINCT endpoint) as unique_endpoints,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as total_errors,
                AVG(response_time_ms) as avg_response_time,
                HOUR(MAX(CASE WHEN request_count = max_count THEN hour END)) as peak_hour
            FROM api_usage_logs
            CROSS JOIN (
                SELECT DATE(created_at) as date, MAX(cnt) as max_count
                FROM (
                    SELECT DATE(created_at) as date, HOUR(created_at) as hour, COUNT(*) as cnt
                    FROM api_usage_logs
                    WHERE created_at >= CURDATE() - INTERVAL 2 DAY
                    AND created_at < CURDATE() - INTERVAL 1 DAY
                    GROUP BY DATE(created_at), HOUR(created_at)
                ) hourly_counts
                GROUP BY date
            ) peak_hours
            WHERE created_at >= CURDATE() - INTERVAL 2 DAY
            AND created_at < CURDATE() - INTERVAL 1 DAY
            " . ($this->projectId ? "AND project_id = :project_id" : "") . "
            GROUP BY project_id, date
            ON DUPLICATE KEY UPDATE
                total_requests = VALUES(total_requests),
                unique_endpoints = VALUES(unique_endpoints),
                total_errors = VALUES(total_errors),
                avg_response_time = VALUES(avg_response_time),
                peak_hour = VALUES(peak_hour)
        ", array_filter(['project_id' => $this->projectId]));
    }

    /**
     * Aggregate weekly analytics.
     */
    protected function aggregateWeekly(): void
    {
        // Similar to daily but grouped by week
        Log::info('Weekly aggregation placeholder - implement based on requirements');
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics aggregation permanently failed', [
            'period' => $this->period,
            'project_id' => $this->projectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
