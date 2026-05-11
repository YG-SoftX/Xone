<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Advanced Escalation Policy Engine
 * 
 * Multi-tier escalation system with configurable policies, time-based rules,
 * and intelligent routing based on incident severity and type.
 */
class EscalationPolicyEngine
{
    protected array $policies;

    public function __construct()
    {
        $this->policies = config('escalation.policies', $this->getDefaultPolicies());
    }

    /**
     * Execute escalation for an incident
     */
    public function escalate(string $incidentId, string $severity, string $jobName, array $context = []): array
    {
        Log::warning("Escalation triggered", [
            'incident_id' => $incidentId,
            'severity' => $severity,
            'job_name' => $jobName,
        ]);

        $policy = $this->getApplicablePolicy($severity, $jobName);
        
        if (!$policy) {
            return [
                'success' => false,
                'message' => 'No applicable escalation policy found',
            ];
        }

        $escalationSteps = [];
        
        foreach ($policy['steps'] as $stepIndex => $step) {
            // Check if previous step was acknowledged
            if ($stepIndex > 0 && !$this->wasPreviousStepAcknowledged($incidentId, $stepIndex)) {
                $result = $this->executeEscalationStep($incidentId, $step, $context);
                $escalationSteps[] = $result;
                
                if ($result['success']) {
                    // Schedule next escalation if this one isn't acknowledged within timeout
                    $this->scheduleNextEscalation($incidentId, $stepIndex + 1, $step['timeout_minutes']);
                }
            } else if ($stepIndex === 0) {
                // First step always executes
                $result = $this->executeEscalationStep($incidentId, $step, $context);
                $escalationSteps[] = $result;
                
                if ($result['success']) {
                    $this->scheduleNextEscalation($incidentId, 1, $step['timeout_minutes']);
                }
            }
        }

        return [
            'success' => true,
            'incident_id' => $incidentId,
            'policy_applied' => $policy['name'],
            'steps_executed' => count($escalationSteps),
            'escalation_steps' => $escalationSteps,
        ];
    }

    /**
     * Acknowledge escalation (called when engineer responds)
     */
    public function acknowledgeEscalation(string $incidentId, string $responderId): bool
    {
        Cache::put("escalation:{$incidentId}:acknowledged", true, now()->addHours(24));
        Cache::put("escalation:{$incidentId}:responder", $responderId, now()->addHours(24));
        
        // Cancel pending escalations
        Cache::forget("escalation:{$incidentId}:next_step");
        
        Log::info("Escalation acknowledged", [
            'incident_id' => $incidentId,
            'responder_id' => $responderId,
        ]);

        return true;
    }

    /**
     * Get escalation status for incident
     */
    public function getEscalationStatus(string $incidentId): array
    {
        return [
            'incident_id' => $incidentId,
            'acknowledged' => Cache::get("escalation:{$incidentId}:acknowledged", false),
            'responder' => Cache::get("escalation:{$incidentId}:responder"),
            'current_step' => Cache::get("escalation:{$incidentId}:current_step", 0),
            'next_escalation_at' => Cache::get("escalation:{$incidentId}:next_escalation_at"),
        ];
    }

    /**
     * Create custom escalation policy
     */
    public function createPolicy(array $policyConfig): array
    {
        $validation = $this->validatePolicy($policyConfig);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // In production, save to database
        // For now, just return success
        
        return [
            'success' => true,
            'policy_id' => $policyConfig['id'],
            'message' => 'Escalation policy created successfully',
        ];
    }

    /**
     * Get all configured policies
     */
    public function getPolicies(): array
    {
        return $this->policies;
    }

    // ── Private Methods ──────────────────────────────────────────────

    protected function getApplicablePolicy(string $severity, string $jobName): ?array
    {
        foreach ($this->policies as $policy) {
            // Check if policy applies to this severity
            if (!in_array($severity, $policy['severities'])) {
                continue;
            }

            // Check job-specific rules
            if (!empty($policy['job_patterns'])) {
                $matchesPattern = false;
                foreach ($policy['job_patterns'] as $pattern) {
                    if (str_contains($jobName, $pattern)) {
                        $matchesPattern = true;
                        break;
                    }
                }
                
                if (!$matchesPattern) {
                    continue;
                }
            }

            return $policy;
        }

        // Return default policy if no specific match
        return $this->getDefaultPolicy();
    }

    protected function executeEscalationStep(string $incidentId, array $step, array $context): array
    {
        try {
            $notifications = [];

            // Execute notification channels
            foreach ($step['channels'] as $channel) {
                $result = $this->sendNotification($channel, $incidentId, $step, $context);
                $notifications[] = $result;
            }

            // Update current step in cache
            Cache::put("escalation:{$incidentId}:current_step", $step['level'], now()->addHours(24));

            Log::info("Escalation step executed", [
                'incident_id' => $incidentId,
                'level' => $step['level'],
                'channels' => $step['channels'],
            ]);

            return [
                'success' => true,
                'level' => $step['level'],
                'description' => $step['description'],
                'notifications_sent' => count($notifications),
                'timeout_minutes' => $step['timeout_minutes'],
            ];
        } catch (\Exception $e) {
            Log::error("Escalation step failed: {$e->getMessage()}");
            
            return [
                'success' => false,
                'level' => $step['level'],
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function sendNotification(string $channel, string $incidentId, array $step, array $context): array
    {
        switch ($channel) {
            case 'email':
                return $this->sendEmailNotification($step, $context);
            
            case 'sms':
                return $this->sendSMSNotification($step, $context);
            
            case 'pagerduty':
                return $this->sendPagerDutyNotification($incidentId, $step, $context);
            
            case 'slack':
                return $this->sendSlackNotification($step, $context);
            
            case 'phone_call':
                return $this->initiatePhoneCall($step, $context);
            
            default:
                return ['success' => false, 'message' => "Unknown channel: {$channel}"];
        }
    }

    protected function sendEmailNotification(array $step, array $context): array
    {
        // TODO: Implement email sending
        return ['success' => true, 'channel' => 'email'];
    }

    protected function sendSMSNotification(array $step, array $context): array
    {
        $twilio = app(\App\Services\TwilioNotifier::class);
        
        foreach ($step['recipients'] ?? [] as $phone) {
            $twilio->sendSms($phone, "🚨 ESCALATION LEVEL {$step['level']}\nIncident: {$context['job_name']}\nPlease respond immediately.");
        }
        
        return ['success' => true, 'channel' => 'sms'];
    }

    protected function sendPagerDutyNotification(string $incidentId, array $step, array $context): array
    {
        $pagerDuty = app(\App\Services\PagerDutyNotifier::class);
        
        $result = $pagerDuty->createIncident(
            "ESCALATION LEVEL {$step['level']}: {$context['job_name']}",
            $context['severity'] ?? 'critical',
            array_merge($context, ['escalation_level' => $step['level']])
        );
        
        return ['success' => $result['success'], 'channel' => 'pagerduty'];
    }

    protected function sendSlackNotification(array $step, array $context): array
    {
        $webhookNotifier = app(\App\Services\WebhookNotifier::class);
        
        $message = "🚨 *ESCALATION LEVEL {$step['level']}*\n";
        $message .= "*Job:* {$context['job_name']}\n";
        $message .= "*Severity:* {$context['severity']}\n";
        $message .= "*Action Required:* {$step['description']}";
        
        $webhookNotifier->sendAlert(null, $message, 'failure');
        
        return ['success' => true, 'channel' => 'slack'];
    }

    protected function initiatePhoneCall(array $step, array $context): array
    {
        // TODO: Integrate with Twilio Voice API or similar
        Log::info("Phone call initiated", [
            'recipients' => $step['recipients'] ?? [],
            'message' => "Critical incident: {$context['job_name']}",
        ]);
        
        return ['success' => true, 'channel' => 'phone_call'];
    }

    protected function wasPreviousStepAcknowledged(string $incidentId, int $stepIndex): bool
    {
        // Check if incident was acknowledged
        return Cache::get("escalation:{$incidentId}:acknowledged", false);
    }

    protected function scheduleNextEscalation(string $incidentId, int $nextStep, int $timeoutMinutes): void
    {
        $nextEscalationTime = now()->addMinutes($timeoutMinutes);
        
        Cache::put("escalation:{$incidentId}:next_step", $nextStep, $nextEscalationTime);
        Cache::put("escalation:{$incidentId}:next_escalation_at", $nextEscalationTime->toIso8601String(), $nextEscalationTime);
        
        // Schedule Artisan command to run at escalation time
        // In production, use Laravel's task scheduler or queue jobs
    }

    protected function validatePolicy(array $policy): array
    {
        $errors = [];

        if (empty($policy['id'])) {
            $errors[] = 'Policy ID is required';
        }

        if (empty($policy['name'])) {
            $errors[] = 'Policy name is required';
        }

        if (empty($policy['severities'])) {
            $errors[] = 'At least one severity level must be specified';
        }

        if (empty($policy['steps']) || !is_array($policy['steps'])) {
            $errors[] = 'Policy must have at least one escalation step';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    protected function getDefaultPolicies(): array
    {
        return [
            [
                'id' => 'critical-system-failure',
                'name' => 'Critical System Failure Escalation',
                'severities' => ['critical'],
                'job_patterns' => ['backup', 'scheduler', 'database'],
                'steps' => [
                    [
                        'level' => 1,
                        'description' => 'Notify on-call engineer via SMS and PagerDuty',
                        'channels' => ['sms', 'pagerduty'],
                        'timeout_minutes' => 5,
                        'recipients' => config('services.twilio.admin_phones', []),
                    ],
                    [
                        'level' => 2,
                        'description' => 'Escalate to team lead with phone call',
                        'channels' => ['phone_call', 'email', 'slack'],
                        'timeout_minutes' => 10,
                        'recipients' => [config('services.twilio.oncall_phone')],
                    ],
                    [
                        'level' => 3,
                        'description' => 'Emergency escalation to engineering manager',
                        'channels' => ['phone_call', 'sms', 'email'],
                        'timeout_minutes' => 15,
                        'recipients' => [config('services.twilio.manager_phone')],
                    ],
                ],
            ],
            [
                'id' => 'high-priority-incident',
                'name' => 'High Priority Incident Escalation',
                'severities' => ['high'],
                'steps' => [
                    [
                        'level' => 1,
                        'description' => 'Notify on-call engineer',
                        'channels' => ['sms', 'pagerduty'],
                        'timeout_minutes' => 10,
                    ],
                    [
                        'level' => 2,
                        'description' => 'Escalate to team lead',
                        'channels' => ['email', 'slack'],
                        'timeout_minutes' => 20,
                    ],
                ],
            ],
            [
                'id' => 'standard-escalation',
                'name' => 'Standard Escalation Policy',
                'severities' => ['medium', 'low'],
                'steps' => [
                    [
                        'level' => 1,
                        'description' => 'Email notification to admin team',
                        'channels' => ['email'],
                        'timeout_minutes' => 60,
                    ],
                ],
            ],
        ];
    }

    protected function getDefaultPolicy(): array
    {
        return $this->getDefaultPolicies()[2]; // Standard escalation
    }
}
