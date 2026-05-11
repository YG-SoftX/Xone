<?php

namespace App\Jobs;

use App\Models\ApiUsageLog;
use App\Models\ProjectQuota;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateUsage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Execute the job - aggregate daily usage and reset counters
     */
    public function handle(): void
    {
        \Log::info('Starting daily usage aggregation job');

        try {
            // Get yesterday's date
            $yesterday = now()->subDay()->toDateString();
            $today = now()->toDateString();

            // 1. Update daily quotas for all projects
            $this->updateDailyQuotas($yesterday, $today);

            // 2. Check monthly resets
            $this->checkMonthlyResets();

            // 3. Generate daily summary reports
            $this->generateDailyReports($yesterday);

            \Log::info('Daily usage aggregation completed successfully');
        } catch (\Exception $e) {
            \Log::error('Daily usage aggregation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('AggregateUsage job permanently failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Update daily quota counters and reset if new day
     */
    protected function updateDailyQuotas(string $yesterday, string $today): void
    {
        // Aggregate all yesterday's usage in one query, keyed by project+product
        $usageCounts = ApiUsageLog::whereDate('created_at', $yesterday)
            ->selectRaw('project_id, product_id, COUNT(*) as total')
            ->groupBy('project_id', 'product_id')
            ->get()
            ->keyBy(fn($row) => "{$row->project_id}_{$row->product_id}");

        $quotas = ProjectQuota::all();

        foreach ($quotas as $quota) {
            $yesterdayUsage = $usageCounts->get("{$quota->project_id}_{$quota->product_id}")?->total ?? 0;

            // If it's a new day, reset daily counter
            if ($quota->daily_reset_date && \Carbon\Carbon::parse($quota->daily_reset_date)->format('Y-m-d') !== $today) {
                $quota->update([
                    'daily_used' => 0,
                    'daily_reset_date' => $today,
                ]);

                \Log::info("Daily quota reset for project {$quota->project_id}, product {$quota->product_id}");
            }
        }
    }

    /**
     * Check and reset monthly quotas if needed
     */
    protected function checkMonthlyResets(): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $quotas = ProjectQuota::whereMonth('monthly_reset_date', '<', $currentMonth)
            ->orWhereYear('monthly_reset_date', '<', $currentYear)
            ->get();

        foreach ($quotas as $quota) {
            $quota->update([
                'monthly_used' => 0,
                'monthly_reset_date' => now()->toDateString(),
            ]);

            \Log::info("Monthly quota reset for project {$quota->project_id}, product {$quota->product_id}");
        }
    }

    /**
     * Generate daily usage reports
     */
    protected function generateDailyReports(string $date): void
    {
        // Get top projects by usage
        $topProjects = ApiUsageLog::whereDate('created_at', $date)
            ->selectRaw('project_id, COUNT(*) as total_requests')
            ->groupBy('project_id')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();

        // Get error rates by project
        $errorRates = ApiUsageLog::whereDate('created_at', $date)
            ->selectRaw('
                project_id,
                COUNT(*) as total,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
            ')
            ->groupBy('project_id')
            ->get()
            ->map(function($item) {
                return [
                    'project_id' => $item->project_id,
                    'total_requests' => $item->total,
                    'errors' => $item->errors,
                    'error_rate' => $item->total > 0 
                        ? round(($item->errors / $item->total) * 100, 2) 
                        : 0,
                ];
            });

        // Log summary
        \Log::info('Daily usage report generated', [
            'date' => $date,
            'total_requests' => ApiUsageLog::whereDate('created_at', $date)->count(),
            'unique_projects' => ApiUsageLog::whereDate('created_at', $date)
                ->distinct('project_id')
                ->count('project_id'),
            'top_projects' => $topProjects,
        ]);
    }
}
