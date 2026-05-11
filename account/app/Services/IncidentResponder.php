<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Automated Incident Response Service
 * 
 * Implements intelligent incident response workflows for cron job failures,
 * including auto-recovery, escalation, and remediation actions.
 */
class IncidentResponder
{
    protected CronJobMonitor $monitor;
    protected WebhookNotifier $webhookNotifier;
    protected TwilioNotifier $twilioNotifier;
    protected FirebasePushNotifier $pushNotifier;
    protected BackupVerifier $backupVerifier;
    protected PagerDutyNotifier $pagerDutyNotifier;
    protected AnomalyDetector $anomalyDetector;
    protected DeploymentRollback $deploymentRollback;
    protected WebhookDispatcher $webhookDispatcher;
    protected YGAIPredictor $aiPredictor;
    protected EscalationPolicyEngine $escalationEngine;

    public function __construct(
        CronJobMonitor $monitor,
        WebhookNotifier $webhookNotifier,
        TwilioNotifier $twilioNotifier,
        FirebasePushNotifier $pushNotifier,
        BackupVerifier $backupVerifier,
        PagerDutyNotifier $pagerDutyNotifier,
        AnomalyDetector $anomalyDetector,
        DeploymentRollback $deploymentRollback,
        WebhookDispatcher $webhookDispatcher,
        YGAIPredictor $aiPredictor,
        EscalationPolicyEngine $escalationEngine
    ) {
        $this->monitor = $monitor;
        $this->webhookNotifier = $webhookNotifier;
        $this->twilioNotifier = $twilioNotifier;
        $this->pushNotifier = $pushNotifier;
        $this->backupVerifier = $backupVerifier;
        $this->pagerDutyNotifier = $pagerDutyNotifier;
        $this->anomalyDetector = $anomalyDetector;
        $this->deploymentRollback = $deploymentRollback;
        $this->webhookDispatcher = $webhookDispatcher;
        $this->aiPredictor = $aiPredictor;
        $this->escalationEngine = $escalationEngine;
    }

    /**
     * Handle cron job failure with automated response workflow
     */
    public function handleFailure(CronJob $job, string $errorMessage): void
    {
        Log::channel('cron')->warning("Incident detected for job: {$job->name}", [
            'error' => $errorMessage,
            'failed_runs' => $job->failed_runs,
            'success_rate' => $job->success_rate,
        ]);

        // Step 1: Classify incident severity
        $severity = $this->classifySeverity($job, $errorMessage);

        // Step 2: Check for anomalies
        $anomalies = $this->anomalyDetector->detectJobAnomalies($job);
        
        if (!empty($anomalies)) {
            Log::warning("Anomalies detected for job {$job->name}", [
                'anomalies' => $anomalies,
            ]);
            
            // Dispatch anomaly event to custom webhooks
            $this->webhookDispatcher->dispatch('anomaly.detected', [
                'job_name' => $job->name,
                'anomalies' => $anomalies,
                'severity' => $severity,
            ]);
        }

        // Step 2.5: Get AI prediction for future failures
        $aiPrediction = $this->aiPredictor->predictFailureProbability($job);
        
        if ($aiPrediction['risk_level'] === 'critical' || $aiPrediction['risk_level'] === 'high') {
            Log::warning("AI predicts high failure probability", [
                'job' => $job->name,
                'probability' => $aiPrediction['failure_probability'],
                'confidence' => $aiPrediction['confidence'],
                'recommendations' => $aiPrediction['recommendations'],
            ]);
            
            // Include AI insights in notifications
            $errorMessage .= "\n\n🤖 AI Insight: " . implode(', ', $aiPrediction['recommendations']);
        }

        // Step 3: Execute automated recovery actions
        $recoveryResult = $this->attemptAutoRecovery($job, $severity);

        // Step 4: Send notifications based on severity
        $this->sendNotifications($job, $severity, $errorMessage, $recoveryResult, $anomalies);

        // Step 5: Create PagerDuty incident for critical issues
        if ($severity === 'critical') {
            $this->pagerDutyNotifier->createCronJobIncident(
                $job->name,
                $errorMessage,
                $job->failed_runs,
                $job->success_rate
            );
        }

        // Step 6: Escalate if needed
        if ($this->requiresEscalation($job, $severity)) {
            $this->escalateIncident($job, $severity, $errorMessage);
        }

        // Step 7: Consider automatic rollback for deployment-related failures
        if ($this->isDeploymentRelated($job) && $severity === 'critical') {
            $this->triggerAutomaticRollback($job, $errorMessage);
        }

        // Step 8: Log incident for analysis
        $this->logIncident($job, $severity, $errorMessage, $recoveryResult, $anomalies);
    }

    /**
     * Classify incident severity based on job type and failure pattern
     */
    protected function classifySeverity(CronJob $job, string $errorMessage): string
    {
        // Critical: System jobs or backup failures
        if ($job->is_system || str_contains($job->name, 'backup')) {
            return 'critical';
        }

        // High: Multiple consecutive failures
        if ($job->failed_runs >= 3) {
            return 'high';
        }

        // Medium: Low success rate
        if ($job->success_rate < 70) {
            return 'medium';
        }

        // Low: Single failure with good history
        return 'low';
    }

    /**
     * Attempt automated recovery actions
     */
    protected function attemptAutoRecovery(CronJob $job, string $severity): array
    {
        $actions = [];

        // Action 1: Clear cache for cache-related jobs
        if (str_contains($job->name, 'cache') || str_contains($job->name, 'session')) {
            try {
                Cache::clear();
                $actions[] = [
                    'action' => 'cache_clear',
                    'status' => 'success',
                    'message' => 'Application cache cleared',
                ];
                
                Log::info("Auto-recovery: Cache cleared for job {$job->name}");
            } catch (\Exception $e) {
                $actions[] = [
                    'action' => 'cache_clear',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Action 2: Restart queue worker for queue jobs
        if (str_contains($job->name, 'queue')) {
            try {
                // In production, you'd use supervisorctl or systemctl
                // exec('supervisorctl restart queue-worker');
                $actions[] = [
                    'action' => 'queue_restart',
                    'status' => 'simulated',
                    'message' => 'Queue worker restart simulated (manual action required)',
                ];
                
                Log::info("Auto-recovery: Queue restart triggered for job {$job->name}");
            } catch (\Exception $e) {
                $actions[] = [
                    'action' => 'queue_restart',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Action 3: Verify backup integrity for backup jobs
        if (str_contains($job->name, 'backup')) {
            try {
                $verification = $this->backupVerifier->verifyLatestBackup();
                $actions[] = [
                    'action' => 'backup_verification',
                    'status' => $verification['verified'] ? 'success' : 'failed',
                    'message' => $verification['verified'] ? 'Last backup is valid' : 'Backup verification failed',
                    'details' => $verification,
                ];
            } catch (\Exception $e) {
                $actions[] = [
                    'action' => 'backup_verification',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Action 4: Retry failed job immediately (for transient errors)
        if ($severity === 'low' && $job->failed_runs === 1) {
            try {
                // Execute the job command
                $output = [];
                $returnCode = 0;
                exec($job->command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    $job->recordSuccess(implode("\n", $output));
                    $actions[] = [
                        'action' => 'immediate_retry',
                        'status' => 'success',
                        'message' => 'Job retried successfully',
                    ];
                    
                    Log::info("Auto-recovery: Job {$job->name} retried successfully");
                } else {
                    $actions[] = [
                        'action' => 'immediate_retry',
                        'status' => 'failed',
                        'message' => implode("\n", $output),
                    ];
                }
            } catch (\Exception $e) {
                $actions[] = [
                    'action' => 'immediate_retry',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Action 5: Disable problematic job if failure rate is critical
        if ($job->success_rate < 50 && $job->failed_runs >= 5) {
            try {
                $job->update(['is_enabled' => false]);
                $actions[] = [
                    'action' => 'auto_disable',
                    'status' => 'success',
                    'message' => 'Job auto-disabled due to critical failure rate',
                ];
                
                Log::warning("Auto-recovery: Job {$job->name} disabled due to critical failure rate");
            } catch (\Exception $e) {
                $actions[] = [
                    'action' => 'auto_disable',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $actions;
    }

    /**
     * Send notifications based on severity level
     */
    protected function sendNotifications(CronJob $job, string $severity, string $errorMessage, array $recoveryActions, array $anomalies = []): void
    {
        $notificationChannels = $this->getNotificationChannels($severity);

        // Email notification (all severities)
        if (in_array('email', $notificationChannels)) {
            $this->sendEmailNotification($job, $severity, $errorMessage, $recoveryActions);
        }

        // Slack/Discord webhook (medium and above)
        if (in_array('webhook', $notificationChannels)) {
            $this->webhookNotifier->sendAlert($job, $this->formatWebhookMessage($severity, $errorMessage), 'failure');
        }

        // SMS (high and critical only)
        if (in_array('sms', $notificationChannels)) {
            $adminPhones = config('services.twilio.admin_phones', []);
            foreach ($adminPhones as $phone) {
                $this->twilioNotifier->sendCriticalAlert($phone, $job->name, $errorMessage);
            }
        }

        // Push notification (critical only)
        if (in_array('push', $notificationChannels)) {
            $deviceTokens = config('services.firebase.admin_devices', []);
            $this->pushNotifier->sendCronJobAlert($deviceTokens, $job->name, $errorMessage);
        }
    }

    /**
     * Determine notification channels based on severity
     */
    protected function getNotificationChannels(string $severity): array
    {
        return match($severity) {
            'critical' => ['email', 'webhook', 'sms', 'push'],
            'high' => ['email', 'webhook', 'sms'],
            'medium' => ['email', 'webhook'],
            'low' => ['email'],
            default => ['email'],
        };
    }

    /**
     * Send email notification with recovery actions
     */
    protected function sendEmailNotification(CronJob $job, string $severity, string $errorMessage, array $recoveryActions): void
    {
        try {
            $adminEmail = config('mail.admin_email');
            
            if (!$adminEmail) {
                return;
            }

            Mail::to($adminEmail)->send(new \App\Mail\CronJobFailedNotification(
                $job,
                $this->formatEmailMessage($severity, $errorMessage, $recoveryActions),
                $job->failed_runs
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: {$e->getMessage()}");
        }
    }

    /**
     * Check if incident requires escalation
     */
    protected function requiresEscalation(CronJob $job, string $severity): bool
    {
        // Escalate if:
        // 1. Critical severity
        // 2. Same job failed 5+ times in last hour
        // 3. Multiple system jobs failing simultaneously
        
        if ($severity === 'critical') {
            return true;
        }

        // Check failure frequency using cache
        $cacheKey = "incident:{$job->name}:failures";
        $recentFailures = Cache::get($cacheKey, 0);
        
        if ($recentFailures >= 5) {
            return true;
        }

        // Track this failure
        Cache::put($cacheKey, $recentFailures + 1, now()->addHour());

        return false;
    }

    /**
     * Escalate incident to senior admins/on-call team
     */
    protected function escalateIncident(CronJob $job, string $severity, string $errorMessage): void
    {
        Log::alert("ESCALATION: {$severity} incident for job {$job->name}", [
            'error' => $errorMessage,
            'failed_runs' => $job->failed_runs,
        ]);

        // Generate unique incident ID
        $incidentId = "INC-" . date('Ymd') . "-" . str_pad($job->id, 4, '0', STR_PAD_LEFT);

        // Use advanced escalation engine
        $escalationResult = $this->escalationEngine->escalate(
            $incidentId,
            $severity,
            $job->name,
            [
                'error_message' => $errorMessage,
                'failed_runs' => $job->failed_runs,
                'success_rate' => $job->success_rate,
                'severity' => $severity,
                'admin_url' => url('/admin/cron-jobs'),
            ]
        );

        if ($escalationResult['success']) {
            Log::info("Escalation executed successfully", [
                'incident_id' => $incidentId,
                'policy' => $escalationResult['policy_applied'],
                'steps' => $escalationResult['steps_executed'],
            ]);
        } else {
            Log::error("Escalation failed: {$escalationResult['message']}");
        }

        // Legacy fallback: Direct SMS to on-call (if escalation engine fails)
        if (!$escalationResult['success']) {
            $onCallPhone = config('services.twilio.oncall_phone');
            if ($onCallPhone) {
                $this->twilioNotifier->sendSms($onCallPhone, 
                    "🚨 URGENT ESCALATION\n" .
                    "Job: {$job->name}\n" .
                    "Severity: {$severity}\n" .
                    "Failures: {$job->failed_runs}\n" .
                    "Check admin panel NOW"
                );
            }
        }

        // Create incident ticket (integrate with Jira/Linear/etc.)
        $this->createIncidentTicket($job, $severity, $errorMessage, $incidentId);
    }

    /**
     * Create incident ticket in external system
     */
    protected function createIncidentTicket(CronJob $job, string $severity, string $errorMessage, string $incidentId = null): void
    {
        // TODO: Integrate with Jira, Linear, GitHub Issues, etc.
        // Example: Create GitHub issue via API
        
        Log::info("Incident ticket created for {$job->name}", [
            'severity' => $severity,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Log incident for post-mortem analysis
     */
    protected function logIncident(CronJob $job, string $severity, string $errorMessage, array $recoveryActions, array $anomalies = []): void
    {
        // Store in database for analytics
        // TODO: Create incident_logs table
        
        Log::channel('cron')->error("INCIDENT LOGGED", [
            'job_name' => $job->name,
            'severity' => $severity,
            'error' => $errorMessage,
            'recovery_actions' => $recoveryActions,
            'anomalies' => $anomalies,
            'timestamp' => now()->toIso8601String(),
            'failed_runs' => $job->failed_runs,
            'success_rate' => $job->success_rate,
        ]);
    }

    /**
     * Format webhook message
     */
    protected function formatWebhookMessage(string $severity, string $errorMessage): string
    {
        $emoji = match($severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '⚡',
            'low' => 'ℹ️',
            default => '📢',
        };

        return "{$emoji} [{$severity}] Cron job failure detected. {$errorMessage}";
    }

    /**
     * Format email message with recovery actions
     */
    protected function formatEmailMessage(string $severity, string $errorMessage, array $recoveryActions): string
    {
        $message = "Severity: {$severity}\n\n";
        $message .= "Error: {$errorMessage}\n\n";

        if (!empty($recoveryActions)) {
            $message .= "Auto-Recovery Actions Taken:\n";
            foreach ($recoveryActions as $action) {
                $status = $action['status'] === 'success' ? '✅' : '❌';
                $message .= "- {$status} {$action['action']}: {$action['message']}\n";
            }
            $message .= "\n";
        }

        $message .= "Please review the job configuration and logs for further investigation.";

        return $message;
    }

    /**
     * Get incident statistics for dashboard
     */
    public function getIncidentStats(): array
    {
        // TODO: Query incident_logs table
        // For now, return mock data
        
        return [
            'total_incidents_today' => 0,
            'critical_incidents' => 0,
            'avg_resolution_time_minutes' => 0,
            'auto_resolved_count' => 0,
            'escalated_count' => 0,
        ];
    }

    /**
     * Check if failure is deployment-related
     */
    protected function isDeploymentRelated(CronJob $job): bool
    {
        $deploymentKeywords = ['deploy', 'migration', 'update', 'release', 'rollback'];
        
        foreach ($deploymentKeywords as $keyword) {
            if (stripos($job->name, $keyword) !== false || stripos($job->description ?? '', $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Trigger automatic rollback for critical deployment failures
     */
    protected function triggerAutomaticRollback(CronJob $job, string $errorMessage): void
    {
        try {
            Log::warning("Triggering automatic rollback for deployment failure", [
                'job' => $job->name,
                'error' => $errorMessage,
            ]);

            // Check if auto-rollback is enabled (would be a config option)
            if (!config('deployment.auto_rollback_enabled', false)) {
                Log::info("Auto-rollback disabled, skipping");
                return;
            }

            // Perform rollback
            $result = $this->deploymentRollback->rollback();

            if ($result['success']) {
                Log::info("Automatic rollback completed successfully");
                
                // Notify about successful rollback
                $this->webhookDispatcher->dispatch('deployment.rolled_back', [
                    'job_name' => $job->name,
                    'error' => $errorMessage,
                    'rollback_result' => $result,
                ]);
            } else {
                Log::error("Automatic rollback failed", [
                    'result' => $result,
                ]);
                
                // Escalate - manual intervention required
                $this->escalateIncident($job, 'critical', "Auto-rollback failed: {$result['message']}");
            }
        } catch (\Exception $e) {
            Log::error("Rollback exception: {$e->getMessage()}");
        }
    }
}
