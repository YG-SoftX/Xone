<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnomalyDetector;

class DetectAnomalies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:detect-anomalies 
                            {--job= : Specific job name to analyze}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run ML-based anomaly detection on cron job metrics';

    protected AnomalyDetector $detector;

    public function __construct(AnomalyDetector $detector)
    {
        parent::__construct();
        $this->detector = $detector;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Running anomaly detection...');
        $this->newLine();

        if ($this->option('job')) {
            // Analyze specific job
            $job = \App\Models\CronJob::where('name', $this->option('job'))->first();
            
            if (!$job) {
                $this->error("Job not found: {$this->option('job')}");
                return Command::FAILURE;
            }

            $anomalies = $this->detector->detectJobAnomalies($job);
            $riskScore = $this->detector->calculateRiskScore($job);

            $this->displayJobAnalysis($job, $anomalies, $riskScore);
        } else {
            // Analyze all jobs
            $allAnomalies = $this->detector->detectAllAnomalies();
            $stats = $this->detector->getAnomalyStats();

            $this->displayOverallStats($stats);

            if (!empty($allAnomalies)) {
                foreach ($allAnomalies as $jobName => $anomalies) {
                    $this->warn("\n📊 Job: {$jobName}");
                    
                    foreach ($anomalies as $anomaly) {
                        $this->displayAnomaly($anomaly);
                    }
                }
            } else {
                $this->info('✅ No anomalies detected across all jobs');
            }
        }

        if ($this->option('json')) {
            $this->outputAsJson($allAnomalies ?? []);
        }

        return Command::SUCCESS;
    }

    protected function displayJobAnalysis($job, array $anomalies, array $riskScore): void
    {
        $this->info("Job: {$job->name}");
        $this->line("Success Rate: {$job->success_rate}%");
        $this->line("Total Runs: {$job->total_runs}");
        $this->line("Failed Runs: {$job->failed_runs}");
        $this->newLine();

        $this->info("Risk Assessment:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Risk Level', strtoupper($riskScore['risk_level'])],
                ['Risk Score', $riskScore['score']],
                ['Anomalies Detected', $riskScore['anomalies_count']],
            ]
        );

        if (!empty($anomalies)) {
            $this->warn("\n⚠️  Anomalies Found:");
            foreach ($anomalies as $anomaly) {
                $this->displayAnomaly($anomaly);
            }
        }
    }

    protected function displayOverallStats(array $stats): void
    {
        $this->info('📈 Overall Anomaly Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Jobs Analyzed', $stats['total_jobs_analyzed']],
                ['Critical Anomalies', $stats['critical_anomalies']],
                ['High Anomalies', $stats['high_anomalies']],
                ['Medium Anomalies', $stats['medium_anomalies']],
                ['Low Anomalies', $stats['low_anomalies']],
            ]
        );

        if (!empty($stats['most_affected_jobs'])) {
            $this->warn("\n🔴 Most Affected Jobs:");
            foreach ($stats['most_affected_jobs'] as $job) {
                $this->line("  - {$job['job_name']}: {$job['anomaly_count']} anomalies");
            }
        }
    }

    protected function displayAnomaly(array $anomaly): void
    {
        $severityEmoji = match($anomaly['severity']) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '⚡',
            'low' => 'ℹ️',
            default => '📢',
        };

        $this->line("  {$severityEmoji} [{$anomaly['severity']}] {$anomaly['type']}");
        $this->line("     Confidence: " . ($anomaly['confidence'] * 100) . "%");
        $this->line("     {$anomaly['details']['description']}");
        $this->line("     💡 Action: {$anomaly['recommended_action']}");
        $this->newLine();
    }

    protected function outputAsJson(array $data): void
    {
        echo json_encode([
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ], JSON_PRETTY_PRINT);
    }
}
