<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * CronJob Monitoring Service
 * 
 * Tracks cron job execution, health metrics, and provides alerts for failures.
 */
class CronJobMonitor
{
    /**
     * Record cron job execution start
     */
    public function startExecution(string $jobName): void
    {
        $job = CronJob::where('name', $jobName)->first();
        
        if ($job) {
            $job->update([
                'status' => 'running',
                'last_run_at' => now(),
            ]);
            
            Log::channel('cron')->info("Cron job started: {$jobName}");
        }
    }

    /**
     * Record successful cron job execution
     */
    public function recordSuccess(string $jobName, string $output = ''): void
    {
        $job = CronJob::where('name', $jobName)->first();
        
        if ($job) {
            $job->recordSuccess($output);
            
            Log::channel('cron')->info("Cron job completed successfully: {$jobName}", [
                'duration' => $job->last_run_at?->diffInSeconds(now()),
                'total_runs' => $job->total_runs,
                'success_rate' => $job->success_rate,
            ]);
        }
    }

    /**
     * Record failed cron job execution
     */
    public function recordFailure(string $jobName, string $error = ''): void
    {
        $job = CronJob::where('name', $jobName)->first();
        
        if ($job) {
            $job->recordFailure($error);
            
            Log::channel('cron')->error("Cron job failed: {$jobName}", [
                'error' => $error,
                'failed_runs' => $job->failed_runs,
                'success_rate' => $job->success_rate,
            ]);
            
            // Trigger automated incident response
            try {
                $responder = app(\App\Services\IncidentResponder::class);
                $responder->handleFailure($job, $error);
            } catch (\Exception $e) {
                Log::error("Incident responder failed: {$e->getMessage()}");
            }
            
            // Send alert if failure rate is high (legacy fallback)
            if ($job->success_rate < 70) {
                $this->sendAlert($job, 'High failure rate detected');
            }
        }
    }

    /**
     * Get health status of all cron jobs
     */
    public function getHealthStatus(): array
    {
        $jobs = CronJob::all();
        
        return [
            'total_jobs' => $jobs->count(),
            'enabled_jobs' => $jobs->where('is_enabled', true)->count(),
            'disabled_jobs' => $jobs->where('is_enabled', false)->count(),
            'healthy_jobs' => $jobs->filter(fn($job) => $job->is_healthy)->count(),
            'unhealthy_jobs' => $jobs->filter(fn($job) => !$job->is_healthy)->count(),
            'failed_jobs' => $jobs->where('status', 'failed')->count(),
            'average_success_rate' => $jobs->avg('success_rate') ?? 100,
            'jobs_needing_attention' => $jobs->filter(function($job) {
                return $job->success_rate < 90 || $job->status === 'failed';
            })->map(fn($job) => [
                'name' => $job->name,
                'status' => $job->status,
                'success_rate' => $job->success_rate,
                'last_run' => $job->last_run_at?->diffForHumans(),
            ])->values(),
        ];
    }

    /**
     * Check for stale cron jobs (not run in expected timeframe)
     */
    public function checkStaleJobs(): array
    {
        $staleJobs = [];
        $now = now();
        
        foreach (CronJob::where('is_enabled', true)->get() as $job) {
            if (!$job->last_run_at) {
                $staleJobs[] = [
                    'name' => $job->name,
                    'reason' => 'Never executed',
                    'schedule' => $job->schedule,
                ];
                continue;
            }
            
            // Calculate expected interval based on cron schedule
            $expectedInterval = $this->calculateExpectedInterval($job->schedule);
            $timeSinceLastRun = $job->last_run_at->diffInMinutes($now);
            
            if ($timeSinceLastRun > $expectedInterval * 2) { // Allow 2x tolerance
                $staleJobs[] = [
                    'name' => $job->name,
                    'reason' => "Last run {$timeSinceLastRun} minutes ago (expected every ~{$expectedInterval} minutes)",
                    'schedule' => $job->schedule,
                    'last_run_at' => $job->last_run_at->toDateTimeString(),
                ];
            }
        }
        
        return $staleJobs;
    }

    /**
     * Generate cPanel setup instructions
     */
    public function generateSetupInstructions(string $cpanelUsername): string
    {
        $instructions = "# ╔══════════════════════════════════════════════════════╗\n";
        $instructions .= "# ║  YG Account - cPanel Cron Job Setup               ║\n";
        $instructions .= "# ║  Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $instructions .= "# ╚══════════════════════════════════════════════════════╝\n\n";
        
        $instructions .= "Login to cPanel → Advanced → Cron Jobs\n\n";
        $instructions .= "Add the following commands:\n\n";
        
        foreach (CronJob::where('is_enabled', true)->get() as $job) {
            $instructions .= "# {$job->description}\n";
            $instructions .= "# Schedule: {$job->schedule}\n";
            
            $command = str_replace('YOUR_USERNAME', $cpanelUsername, $job->command);
            $instructions .= "{$command}\n\n";
        }
        
        return $instructions;
    }

    /**
     * Send alert notification (email, Slack, Discord, etc.)
     */
    protected function sendAlert(CronJob $job, string $message): void
    {
        // Log the alert
        Log::warning("Cron Job Alert: {$job->name} - {$message}", [
            'job_id' => $job->id,
            'success_rate' => $job->success_rate,
            'failed_runs' => $job->failed_runs,
        ]);

        // Send email notification if configured
        if (config('mail.admin_email')) {
            try {
                Mail::to(config('mail.admin_email'))
                    ->send(new \App\Mail\CronJobFailedNotification(
                        $job,
                        $message,
                        $job->failed_runs
                    ));
            } catch (\Exception $e) {
                Log::error("Failed to send email notification: {$e->getMessage()}");
            }
        }

        // Send webhook notifications (Slack/Discord)
        try {
            $webhookNotifier = app(\App\Services\WebhookNotifier::class);
            $webhookNotifier->sendAlert($job, $message, 'failure');
        } catch (\Exception $e) {
            Log::error("Failed to send webhook notification: {$e->getMessage()}");
        }

        // For backup jobs, trigger verification
        if (str_contains($job->name, 'backup')) {
            try {
                $verifier = app(\App\Services\BackupVerifier::class);
                $verification = $verifier->verifyLatestBackup();
                
                if (!$verification['verified']) {
                    Log::error("Backup verification failed after job execution", [
                        'job' => $job->name,
                        'errors' => $verification['errors'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Backup verification error: {$e->getMessage()}");
            }
        }
    }

    /**
     * Calculate expected interval in minutes from cron expression
     */
    protected function calculateExpectedInterval(string $schedule): int
    {
        // Simplified calculation for common patterns
        if ($schedule === '* * * * *') {
            return 1; // Every minute
        } elseif (preg_match('/^(\*|\d+)\/(\d+) \* \* \* \*$/', $schedule, $matches)) {
            return (int)$matches[2]; // Every N minutes
        } elseif (preg_match('/^0 \* \* \* \*$/', $schedule)) {
            return 60; // Every hour
        } elseif (preg_match('/^0 0 \* \* \*$/', $schedule)) {
            return 1440; // Daily
        } elseif (preg_match('/^0 0 \* \* 0$/', $schedule)) {
            return 10080; // Weekly
        }
        
        return 60; // Default to 1 hour
    }
}
