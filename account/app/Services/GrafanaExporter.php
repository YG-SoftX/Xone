<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Grafana Analytics Integration Service
 * 
 * Exports cron job metrics to Grafana via InfluxDB or Prometheus for advanced visualization.
 */
class GrafanaExporter
{
    protected ?string $influxDbUrl;
    protected ?string $influxDbToken;
    protected ?string $influxDbBucket;
    protected ?string $influxDbOrg;

    public function __construct()
    {
        $this->influxDbUrl = config('services.grafana.influxdb_url', 'http://localhost:8086');
        $this->influxDbToken = config('services.grafana.influxdb_token', '');
        $this->influxDbBucket = config('services.grafana.influxdb_bucket', 'cron_metrics');
        $this->influxDbOrg = config('services.grafana.influxdb_org', 'yg_account');
    }

    /**
     * Export all cron job metrics to InfluxDB
     */
    public function exportMetrics(): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Grafana/InfluxDB not configured',
                ];
            }

            $jobs = CronJob::all();
            $exportedCount = 0;

            foreach ($jobs as $job) {
                $this->exportJobMetrics($job);
                $exportedCount++;
            }

            Log::channel('cron')->info("Exported {$exportedCount} cron job metrics to InfluxDB");

            return [
                'success' => true,
                'exported_count' => $exportedCount,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to export metrics: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export single job metrics
     */
    protected function exportJobMetrics(CronJob $job): void
    {
        $lineProtocol = $this->buildLineProtocol($job);
        
        Http::withHeaders([
            'Authorization' => "Token {$this->influxDbToken}",
            'Content-Type' => 'text/plain',
        ])
        ->timeout(5)
        ->post("{$this->influxDbUrl}/api/v2/write?bucket={$this->influxDbBucket}&org={$this->influxDbOrg}", $lineProtocol);
    }

    /**
     * Build InfluxDB line protocol format
     */
    protected function buildLineProtocol(CronJob $job): string
    {
        $measurement = 'cron_job_metrics';
        $tags = [
            "job_name={$job->name}",
            "status={$job->status}",
            "is_enabled=" . ($job->is_enabled ? 'true' : 'false'),
            "is_system=" . ($job->is_system ? 'true' : 'false'),
        ];

        $fields = [
            "total_runs={$job->total_runs}",
            "failed_runs={$job->failed_runs}",
            "success_rate={$job->success_rate}",
            "last_run_timestamp=" . ($job->last_run_at?->timestamp ?? 0),
        ];

        $timestamp = now()->timestamp * 1000000000; // Nanoseconds

        return "{$measurement}," . implode(',', $tags) . " " . implode(',', $fields) . " {$timestamp}";
    }

    /**
     * Get dashboard URL for quick access
     */
    public function getDashboardUrl(): ?string
    {
        $baseUrl = config('services.grafana.dashboard_url');
        
        if (empty($baseUrl)) {
            return null;
        }

        return $baseUrl . '/d/cron-jobs/cron-job-monitoring';
    }

    /**
     * Test connection to InfluxDB
     */
    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => "Token {$this->influxDbToken}",
            ])
            ->timeout(5)
            ->get("{$this->influxDbUrl}/api/v2/buckets");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connection successful'];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if Grafana integration is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->influxDbUrl) && !empty($this->influxDbToken);
    }

    /**
     * Export custom metric
     */
    public function exportCustomMetric(string $metricName, array $tags, array $fields): bool
    {
        try {
            $tagString = implode(',', array_map(fn($k, $v) => "{$k}={$v}", array_keys($tags), $tags));
            $fieldString = implode(',', array_map(fn($k, $v) => "{$k}={$v}", array_keys($fields), $fields));
            $timestamp = now()->timestamp * 1000000000;

            $lineProtocol = "{$metricName},{$tagString} {$fieldString} {$timestamp}";

            Http::withHeaders([
                'Authorization' => "Token {$this->influxDbToken}",
                'Content-Type' => 'text/plain',
            ])
            ->timeout(5)
            ->post("{$this->influxDbUrl}/api/v2/write?bucket={$this->influxDbBucket}&org={$this->influxDbOrg}", $lineProtocol);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to export custom metric: ' . $e->getMessage());
            return false;
        }
    }
}
