<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Notification Service
 * 
 * Sends cron job alerts to Slack, Discord, and other webhook endpoints.
 */
class WebhookNotifier
{
    /**
     * Send notification to all configured webhooks
     */
    public function sendAlert(CronJob $job, string $message, string $type = 'failure'): void
    {
        $webhooks = $this->getConfiguredWebhooks();
        
        foreach ($webhooks as $webhook) {
            try {
                match($webhook['platform']) {
                    'slack' => $this->sendToSlack($webhook['url'], $job, $message, $type),
                    'discord' => $this->sendToDiscord($webhook['url'], $job, $message, $type),
                    default => $this->sendGenericWebhook($webhook['url'], $job, $message, $type),
                };
                
                Log::channel('cron')->info("Webhook notification sent to {$webhook['platform']}");
            } catch (\Exception $e) {
                Log::channel('cron')->error("Failed to send webhook to {$webhook['platform']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Send alert to Slack
     */
    protected function sendToSlack(string $webhookUrl, CronJob $job, string $message, string $type): void
    {
        $color = match($type) {
            'failure' => '#e74c3c', // Red
            'warning' => '#f39c12', // Orange
            'success' => '#2ecc71', // Green
            default => '#3498db',   // Blue
        };

        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "🚨 Cron Job Alert: {$job->name}",
                    'text' => $message,
                    'fields' => [
                        [
                            'title' => 'Job Name',
                            'value' => $job->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Status',
                            'value' => strtoupper($type),
                            'short' => true,
                        ],
                        [
                            'title' => 'Success Rate',
                            'value' => number_format($job->success_rate, 1) . '%',
                            'short' => true,
                        ],
                        [
                            'title' => 'Schedule',
                            'value' => $job->schedule,
                            'short' => true,
                        ],
                    ],
                    'footer' => 'YG Account Cron Monitor',
                    'ts' => now()->timestamp,
                ]
            ]
        ];

        Http::timeout(5)->post($webhookUrl, $payload);
    }

    /**
     * Send alert to Discord
     */
    protected function sendToDiscord(string $webhookUrl, CronJob $job, string $message, string $type): void
    {
        $color = match($type) {
            'failure' => 15158332, // Red (0xE74C3C)
            'warning' => 15968532, // Orange (0xF39C12)
            'success' => 3066993,  // Green (0x2ECC71)
            default => 3447003,    // Blue (0x3498DB)
        };

        $payload = [
            'embeds' => [
                [
                    'title' => "🚨 Cron Job Alert: {$job->name}",
                    'description' => $message,
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Job Name',
                            'value' => $job->name,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Status',
                            'value' => strtoupper($type),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Success Rate',
                            'value' => number_format($job->success_rate, 1) . '%',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Schedule',
                            'value' => "`{$job->schedule}`",
                            'inline' => true,
                        ],
                        [
                            'name' => 'Total Runs',
                            'value' => (string)$job->total_runs,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Failed Runs',
                            'value' => (string)$job->failed_runs,
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'YG Account Cron Monitor',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]
            ]
        ];

        Http::timeout(5)->post($webhookUrl, $payload);
    }

    /**
     * Send generic webhook payload
     */
    protected function sendGenericWebhook(string $webhookUrl, CronJob $job, string $message, string $type): void
    {
        $payload = [
            'event' => 'cron_job_alert',
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'job_name' => $job->name,
                'job_description' => $job->description,
                'status' => $job->status,
                'success_rate' => $job->success_rate,
                'total_runs' => $job->total_runs,
                'failed_runs' => $job->failed_runs,
                'last_run_at' => $job->last_run_at?->toIso8601String(),
                'schedule' => $job->schedule,
                'message' => $message,
            ],
        ];

        Http::timeout(5)->post($webhookUrl, $payload);
    }

    /**
     * Get configured webhook endpoints from config
     */
    protected function getConfiguredWebhooks(): array
    {
        return config('services.webhooks.cron_alerts', []);
    }

    /**
     * Test webhook configuration
     */
    public function testWebhook(string $platform, string $url): array
    {
        try {
            $testJob = new CronJob([
                'name' => 'test-webhook',
                'description' => 'Test webhook notification',
                'schedule' => '* * * * *',
                'status' => 'success',
                'success_rate' => 100,
                'total_runs' => 1,
                'failed_runs' => 0,
            ]);

            match($platform) {
                'slack' => $this->sendToSlack($url, $testJob, 'This is a test notification from YG Account', 'success'),
                'discord' => $this->sendToDiscord($url, $testJob, 'This is a test notification from YG Account', 'success'),
                default => $this->sendGenericWebhook($url, $testJob, 'This is a test notification from YG Account', 'success'),
            };

            return ['success' => true, 'message' => "Test notification sent to {$platform}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
