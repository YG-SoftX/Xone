<?php

namespace App\Jobs;

use App\Models\AppModule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AggregateMetricsJob
 * 
 * Aggregates ecosystem-wide metrics for dashboard display.
 * Collects statistics from all services and stores in summary tables.
 */
class AggregateMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    protected string $period; // 'hourly' or 'daily'

    /**
     * Create a new job instance.
     */
    public function __construct(string $period)
    {
        $this->period = $period;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting metrics aggregation", ['period' => $this->period]);

        try {
            DB::transaction(function () {
                $metrics = [
                    'total_users' => $this->getTotalUsers(),
                    'active_users' => $this->getActiveUsers(),
                    'total_revenue' => $this->getTotalRevenue(),
                    'active_subscriptions' => $this->getActiveSubscriptions(),
                    'service_count' => $this->getServiceCount(),
                    'healthy_services' => $this->getHealthyServices(),
                    'total_transactions' => $this->getTotalTransactions(),
                    'api_requests' => $this->getApiRequests(),
                ];

                // Store aggregated metrics
                DB::table('ecosystem_metrics')->insert([
                    'period' => $this->period,
                    'metric_date' => now(),
                    'data' => json_encode($metrics),
                    'created_at' => now(),
                ]);

                Log::info("Metrics aggregation completed", [
                    'period' => $this->period,
                    'metrics' => $metrics,
                ]);
            });

        } catch (\Exception $e) {
            Log::error("Metrics aggregation failed", [
                'period' => $this->period,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get total users across ecosystem.
     */
    protected function getTotalUsers(): int
    {
        // Query YG Account service or shared database
        return \App\Models\User::count();
    }

    /**
     * Get active users (last 30 days).
     */
    protected function getActiveUsers(): int
    {
        return \App\Models\User::where('last_active_at', '>=', now()->subDays(30))->count();
    }

    /**
     * Get total revenue this month.
     */
    protected function getTotalRevenue(): float
    {
        return \App\Models\Subscription::whereMonth('created_at', now()->month)
            ->sum('amount');
    }

    /**
     * Get active subscription count.
     */
    protected function getActiveSubscriptions(): int
    {
        return \App\Models\Subscription::where('status', 'active')->count();
    }

    /**
     * Get total service count.
     */
    protected function getServiceCount(): int
    {
        return AppModule::count();
    }

    /**
     * Get healthy service count.
     */
    protected function getHealthyServices(): int
    {
        return AppModule::where('status', 'healthy')->count();
    }

    /**
     * Get total transaction count.
     */
    protected function getTotalTransactions(): int
    {
        // This would query payment service
        return 0; // Placeholder
    }

    /**
     * Get API request count.
     */
    protected function getApiRequests(): int
    {
        // This would query API gateway logs
        return 0; // Placeholder
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("AggregateMetricsJob permanently failed", [
            'period' => $this->period,
            'error' => $exception->getMessage(),
        ]);
    }
}
