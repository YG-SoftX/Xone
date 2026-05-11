<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PagerDuty Integration Service
 * 
 * Professional incident management with on-call scheduling, escalation policies,
 * and automated incident creation/resolution.
 */
class PagerDutyNotifier
{
    protected ?string $apiKey;
    protected ?string $serviceId;
    protected ?string $routingKey;

    public function __construct()
    {
        $this->apiKey = config('services.pagerduty.api_key', '');
        $this->serviceId = config('services.pagerduty.service_id', '');
        $this->routingKey = config('services.pagerduty.routing_key', '');
    }

    /**
     * Create incident in PagerDuty
     */
    public function createIncident(string $summary, string $severity = 'critical', array $details = []): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'PagerDuty not configured',
                ];
            }

            $payload = [
                'routing_key' => $this->routingKey,
                'event_action' => 'trigger',
                'dedup_key' => $this->generateDedupKey($summary),
                'payload' => [
                    'summary' => $summary,
                    'severity' => $severity,
                    'source' => 'YG Account Cron Monitor',
                    'timestamp' => now()->toIso8601String(),
                    'component' => 'cron-job-monitoring',
                    'group' => 'infrastructure',
                    'class' => 'automated-alert',
                    'custom_details' => $details,
                ],
                'links' => [
                    [
                        'href' => url('/admin/cron-jobs'),
                        'text' => 'View in Admin Panel',
                    ],
                ],
            ];

            $response = Http::timeout(10)
                ->post('https://events.pagerduty.com/v2/enqueue', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::channel('cron')->info('PagerDuty incident created', [
                    'status' => $result['status'],
                    'dedup_key' => $result['dedup_key'] ?? null,
                    'summary' => $summary,
                ]);

                return [
                    'success' => true,
                    'status' => $result['status'],
                    'dedup_key' => $result['dedup_key'] ?? null,
                    'message_ids' => $result['message_ids'] ?? [],
                ];
            }

            $error = $response->json();
            
            Log::error('PagerDuty incident creation failed', [
                'errors' => $error['errors'] ?? [],
                'message' => $error['message'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => $error['message'] ?? 'Failed to create incident',
                'errors' => $error['errors'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('PagerDuty exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve incident in PagerDuty
     */
    public function resolveIncident(string $dedupKey, string $summary = ''): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Not configured'];
            }

            $payload = [
                'routing_key' => $this->routingKey,
                'event_action' => 'resolve',
                'dedup_key' => $dedupKey,
                'payload' => [
                    'summary' => $summary ?: 'Issue resolved automatically',
                    'severity' => 'info',
                    'source' => 'YG Account Cron Monitor',
                ],
            ];

            $response = Http::timeout(10)
                ->post('https://events.pagerduty.com/v2/enqueue', $payload);

            if ($response->successful()) {
                Log::info("PagerDuty incident resolved: {$dedupKey}");
                return ['success' => true, 'status' => 'resolved'];
            }

            return [
                'success' => false,
                'message' => 'Failed to resolve incident',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Acknowledge incident (for manual intervention tracking)
     */
    public function acknowledgeIncident(string $dedupKey): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Not configured'];
            }

            $payload = [
                'routing_key' => $this->routingKey,
                'event_action' => 'acknowledge',
                'dedup_key' => $dedupKey,
                'payload' => [
                    'summary' => 'Incident acknowledged by on-call engineer',
                    'severity' => 'warning',
                    'source' => 'YG Account Cron Monitor',
                ],
            ];

            $response = Http::timeout(10)
                ->post('https://events.pagerduty.com/v2/enqueue', $payload);

            if ($response->successful()) {
                Log::info("PagerDuty incident acknowledged: {$dedupKey}");
                return ['success' => true, 'status' => 'acknowledged'];
            }

            return ['success' => false, 'message' => 'Failed to acknowledge incident'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get on-call schedule status
     */
    public function getOnCallStatus(): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => "Token token={$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(5)
            ->get('https://api.pagerduty.com/schedules');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'schedules' => $response->json()['schedules'] ?? [],
                ];
            }

            return ['success' => false, 'message' => 'Failed to fetch schedules'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create cron job failure incident with context
     */
    public function createCronJobIncident(string $jobName, string $errorMessage, int $failedRuns, float $successRate): array
    {
        $severity = $this->determineSeverity($failedRuns, $successRate);
        
        $summary = "[{$severity}] Cron Job Failed: {$jobName}";
        
        $details = [
            'job_name' => $jobName,
            'error_message' => $errorMessage,
            'failed_runs' => $failedRuns,
            'success_rate' => $successRate,
            'timestamp' => now()->toIso8601String(),
            'admin_url' => url('/admin/cron-jobs'),
        ];

        return $this->createIncident($summary, $severity, $details);
    }

    /**
     * Test PagerDuty integration
     */
    public function testIntegration(): array
    {
        return $this->createIncident(
            'Test Alert from YG Account',
            'info',
            [
                'test' => true,
                'message' => 'This is a test alert to verify PagerDuty integration',
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Check if PagerDuty is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->routingKey);
    }

    // ── Private Helper Methods ──────────────────────────────────────────────

    protected function generateDedupKey(string $summary): string
    {
        // Dedup key prevents duplicate incidents for same issue
        return md5($summary . date('Y-m-d'));
    }

    protected function determineSeverity(int $failedRuns, float $successRate): string
    {
        if ($successRate < 50 || $failedRuns >= 5) {
            return 'critical';
        } elseif ($successRate < 70 || $failedRuns >= 3) {
            return 'error';
        } elseif ($successRate < 85) {
            return 'warning';
        }
        
        return 'info';
    }
}
