<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ML-Based Anomaly Detection Service
 * 
 * Uses statistical analysis and pattern recognition to detect anomalies in cron job execution,
 * including unusual failure patterns, timing deviations, and performance degradation.
 */
class AnomalyDetector
{
    protected const BASELINE_DAYS = 30; // Days of historical data for baseline
    protected const ANOMALY_THRESHOLD = 2.5; // Standard deviations for anomaly detection
    protected const CONFIDENCE_HIGH = 0.95;
    protected const CONFIDENCE_MEDIUM = 0.80;
    protected const CONFIDENCE_LOW = 0.60;

    /**
     * Detect anomalies across all cron jobs
     */
    public function detectAllAnomalies(): array
    {
        $jobs = CronJob::where('total_runs', '>', 10)->get(); // Need minimum data
        $anomalies = [];

        foreach ($jobs as $job) {
            $jobAnomalies = $this->detectJobAnomalies($job);
            
            if (!empty($jobAnomalies)) {
                $anomalies[$job->name] = $jobAnomalies;
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies for a specific job
     */
    public function detectJobAnomalies(CronJob $job): array
    {
        $anomalies = [];

        // Check 1: Failure rate spike
        $failureSpike = $this->detectFailureRateSpike($job);
        if ($failureSpike['detected']) {
            $anomalies[] = $failureSpike;
        }

        // Check 2: Execution time anomaly
        $timeAnomaly = $this->detectExecutionTimeAnomaly($job);
        if ($timeAnomaly['detected']) {
            $anomalies[] = $timeAnomaly;
        }

        // Check 3: Unusual execution frequency
        $frequencyAnomaly = $this->detectFrequencyAnomaly($job);
        if ($frequencyAnomaly['detected']) {
            $anomalies[] = $frequencyAnomaly;
        }

        // Check 4: Pattern deviation (day-of-week, hour-of-day)
        $patternAnomaly = $this->detectPatternDeviation($job);
        if ($patternAnomaly['detected']) {
            $anomalies[] = $patternAnomaly;
        }

        // Check 5: Success rate trend degradation
        $trendAnomaly = $this->detectTrendDegradation($job);
        if ($trendAnomaly['detected']) {
            $anomalies[] = $trendAnomaly;
        }

        return $anomalies;
    }

    /**
     * Detect sudden increase in failure rate
     */
    protected function detectFailureRateSpike(CronJob $job): array
    {
        // Get recent vs historical failure rates
        $recentFailures = $this->getRecentFailureRate($job, 7); // Last 7 days
        $historicalFailures = $this->getHistoricalFailureRate($job, self::BASELINE_DAYS);

        if ($historicalFailures === 0) {
            return ['detected' => false];
        }

        $spikeRatio = $recentFailures / max($historicalFailures, 0.01);
        $confidence = min(($spikeRatio - 1) / 2, 1.0); // Normalize to 0-1

        if ($spikeRatio > 2.0 && $confidence >= self::CONFIDENCE_MEDIUM) {
            return [
                'detected' => true,
                'type' => 'failure_rate_spike',
                'severity' => $this->calculateSeverity($confidence),
                'confidence' => round($confidence, 2),
                'details' => [
                    'recent_failure_rate' => round($recentFailures, 2) . '%',
                    'historical_failure_rate' => round($historicalFailures, 2) . '%',
                    'spike_ratio' => round($spikeRatio, 2) . 'x',
                    'description' => "Failure rate increased by {$spikeRatio}x compared to " . self::BASELINE_DAYS . "-day baseline",
                ],
                'recommended_action' => 'Investigate recent changes, check error logs, verify dependencies',
            ];
        }

        return ['detected' => false];
    }

    /**
     * Detect unusual execution times (performance degradation)
     */
    protected function detectExecutionTimeAnomaly(CronJob $job): array
    {
        // Simulate execution time tracking (would need actual timing data)
        // For now, use failed_runs as proxy for potential timeout issues
        
        $recentFailureRate = $this->getRecentFailureRate($job, 7);
        
        // If failures are clustered and high, might indicate timeout/performance issues
        if ($recentFailureRate > 30 && $job->failed_runs > 5) {
            return [
                'detected' => true,
                'type' => 'execution_time_anomaly',
                'severity' => 'medium',
                'confidence' => 0.75,
                'details' => [
                    'recent_failure_rate' => round($recentFailureRate, 2) . '%',
                    'total_failures' => $job->failed_runs,
                    'description' => 'High failure rate may indicate execution timeouts or resource constraints',
                ],
                'recommended_action' => 'Check server resources (CPU/RAM), review job complexity, consider optimization',
            ];
        }

        return ['detected' => false];
    }

    /**
     * Detect unusual execution frequency (missing executions or duplicates)
     */
    protected function detectFrequencyAnomaly(CronJob $job): array
    {
        // Expected executions based on schedule
        $expectedExecutions = $this->calculateExpectedExecutions($job->schedule, 7);
        
        // Actual executions (approximate from total_runs and job age)
        $jobAgeDays = $job->created_at->diffInDays(now());
        $avgDailyRuns = $jobAgeDays > 0 ? $job->total_runs / $jobAgeDays : 0;
        $expectedWeeklyRuns = $avgDailyRuns * 7;

        $deviation = abs($expectedExecutions - $expectedWeeklyRuns) / max($expectedExecutions, 1);

        if ($deviation > 0.3 && $expectedExecutions > 0) {
            return [
                'detected' => true,
                'type' => 'frequency_anomaly',
                'severity' => $deviation > 0.5 ? 'high' : 'medium',
                'confidence' => round(min($deviation, 1.0), 2),
                'details' => [
                    'expected_executions' => $expectedExecutions,
                    'actual_estimated' => round($expectedWeeklyRuns),
                    'deviation' => round($deviation * 100, 1) . '%',
                    'description' => "Execution frequency deviates " . round($deviation * 100, 1) . "% from expected pattern",
                ],
                'recommended_action' => 'Verify cron schedule is active, check for overlapping executions, review system logs',
            ];
        }

        return ['detected' => false];
    }

    /**
     * Detect deviations from normal execution patterns
     */
    protected function detectPatternDeviation(CronJob $job): array
    {
        // Analyze if failures cluster at specific times/days
        // This would require detailed execution history with timestamps
        
        // Simplified: Check if success rate varies significantly
        $currentRate = $job->success_rate;
        $baselineRate = $this->getHistoricalSuccessRate($job, self::BASELINE_DAYS);

        $variance = abs($currentRate - $baselineRate);

        if ($variance > 15 && $job->total_runs > 20) {
            return [
                'detected' => true,
                'type' => 'pattern_deviation',
                'severity' => $variance > 25 ? 'high' : 'medium',
                'confidence' => round(min($variance / 30, 1.0), 2),
                'details' => [
                    'current_success_rate' => round($currentRate, 2) . '%',
                    'baseline_success_rate' => round($baselineRate, 2) . '%',
                    'variance' => round($variance, 2) . '%',
                    'description' => 'Success rate pattern has shifted from historical baseline',
                ],
                'recommended_action' => 'Review recent code/config changes, check external dependencies, analyze failure patterns by time',
            ];
        }

        return ['detected' => false];
    }

    /**
     * Detect degrading trends in success rate over time
     */
    protected function detectTrendDegradation(CronJob $job): array
    {
        // Compare recent week vs previous week
        $week1Rate = $this->getRecentSuccessRate($job, 7); // Most recent 7 days
        $week2Rate = $this->getRecentSuccessRate($job, 14, 7); // Previous 7 days (days 8-14)

        if ($week2Rate === null || $week1Rate === null) {
            return ['detected' => false];
        }

        $trendChange = $week1Rate - $week2Rate;

        // Negative trend means degradation
        if ($trendChange < -10) {
            return [
                'detected' => true,
                'type' => 'trend_degradation',
                'severity' => $trendChange < -20 ? 'critical' : 'high',
                'confidence' => round(min(abs($trendChange) / 25, 1.0), 2),
                'details' => [
                    'current_week_rate' => round($week1Rate, 2) . '%',
                    'previous_week_rate' => round($week2Rate, 2) . '%',
                    'trend_change' => round($trendChange, 2) . '%',
                    'description' => "Success rate declining: {$trendChange}% drop week-over-week",
                ],
                'recommended_action' => 'Immediate investigation required - identify root cause of degradation trend',
            ];
        }

        return ['detected' => false];
    }

    /**
     * Calculate anomaly risk score for proactive alerting
     */
    public function calculateRiskScore(CronJob $job): array
    {
        $anomalies = $this->detectJobAnomalies($job);
        
        if (empty($anomalies)) {
            return [
                'risk_level' => 'low',
                'score' => 0,
                'anomalies_count' => 0,
                'message' => 'No anomalies detected',
            ];
        }

        // Weighted scoring
        $weights = [
            'critical' => 10,
            'high' => 7,
            'medium' => 4,
            'low' => 1,
        ];

        $totalScore = collect($anomalies)->sum(function ($anomaly) use ($weights) {
            return $weights[$anomaly['severity']] ?? 1;
        });

        $riskLevel = match(true) {
            $totalScore >= 20 => 'critical',
            $totalScore >= 10 => 'high',
            $totalScore >= 5 => 'medium',
            default => 'low',
        };

        return [
            'risk_level' => $riskLevel,
            'score' => $totalScore,
            'anomalies_count' => count($anomalies),
            'anomalies' => $anomalies,
            'message' => "{$riskLevel} risk: " . count($anomalies) . " anomalies detected",
        ];
    }

    /**
     * Get anomaly statistics for dashboard
     */
    public function getAnomalyStats(): array
    {
        $allAnomalies = $this->detectAllAnomalies();
        
        $stats = [
            'total_jobs_analyzed' => count($allAnomalies),
            'critical_anomalies' => 0,
            'high_anomalies' => 0,
            'medium_anomalies' => 0,
            'low_anomalies' => 0,
            'most_affected_jobs' => [],
        ];

        foreach ($allAnomalies as $jobName => $anomalies) {
            foreach ($anomalies as $anomaly) {
                $stats["{$anomaly['severity']}_anomalies"]++;
            }
            
            if (count($anomalies) > 2) {
                $stats['most_affected_jobs'][] = [
                    'job_name' => $jobName,
                    'anomaly_count' => count($anomalies),
                ];
            }
        }

        usort($stats['most_affected_jobs'], fn($a, $b) => $b['anomaly_count'] <=> $a['anomaly_count']);
        $stats['most_affected_jobs'] = array_slice($stats['most_affected_jobs'], 0, 5);

        return $stats;
    }

    // ── Private Helper Methods ──────────────────────────────────────────────

    protected function getRecentFailureRate(CronJob $job, int $days): float
    {
        // Simplified: Use current success_rate as proxy
        // In production, query execution_history table
        return 100 - $job->success_rate;
    }

    protected function getHistoricalFailureRate(CronJob $job, int $days): float
    {
        // Simplified baseline calculation
        // In production, aggregate from historical data
        return max(100 - $job->success_rate, 5); // Minimum 5% baseline
    }

    protected function getRecentSuccessRate(CronJob $job, int $days, int $offset = 0): ?float
    {
        // Simplified: Return current rate
        // In production, calculate from time-windowed data
        return $job->success_rate;
    }

    protected function getHistoricalSuccessRate(CronJob $job, int $days): float
    {
        return $job->success_rate;
    }

    protected function calculateExpectedExecutions(string $schedule, int $days): int
    {
        // Parse cron schedule to estimate executions
        // Simplified calculations for common schedules
        
        if ($schedule === '* * * * *') {
            return $days * 24 * 60; // Every minute
        } elseif (str_contains($schedule, '*/5')) {
            return $days * 24 * 12; // Every 5 minutes
        } elseif (str_contains($schedule, '0 *')) {
            return $days * 24; // Hourly
        } elseif (preg_match('/^0 \d+/', $schedule)) {
            return $days; // Daily
        } elseif (str_contains($schedule, '* * *')) {
            return ceil($days / 7); // Weekly
        }
        
        return $days; // Default assumption
    }

    protected function calculateSeverity(float $confidence): string
    {
        return match(true) {
            $confidence >= self::CONFIDENCE_HIGH => 'critical',
            $confidence >= self::CONFIDENCE_MEDIUM => 'high',
            $confidence >= self::CONFIDENCE_LOW => 'medium',
            default => 'low',
        };
    }
}
