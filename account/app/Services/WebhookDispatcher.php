<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Custom Webhook Dispatcher Service
 * 
 * Manages and dispatches events to custom webhook endpoints configured by administrators,
 * supporting third-party integrations with signature verification, retry logic, and monitoring.
 */
class WebhookDispatcher
{
    /**
     * Dispatch event to all configured webhooks
     */
    public function dispatch(string $event, array $payload): array
    {
        $webhooks = $this->getActiveWebhooks($event);
        $results = [];

        foreach ($webhooks as $webhook) {
            $result = $this->sendToWebhook($webhook, $event, $payload);
            $results[$webhook['id']] = $result;
        }

        return [
            'event' => $event,
            'total_webhooks' => count($webhooks),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
            'results' => $results,
        ];
    }

    /**
     * Send payload to single webhook endpoint
     */
    protected function sendToWebhook(array $webhook, string $event, array $payload): array
    {
        try {
            // Build request payload
            $requestPayload = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $payload,
                'source' => 'YG Account',
                'version' => '1.0',
            ];

            // Generate signature for verification
            $signature = $this->generateSignature($requestPayload, $webhook['secret']);

            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $event,
                'X-Webhook-Source' => 'YG Account',
            ];

            // Add custom headers if configured
            if (!empty($webhook['custom_headers'])) {
                $headers = array_merge($headers, $webhook['custom_headers']);
            }

            // Send request with retry logic
            $response = $this->sendWithRetry(
                $webhook['url'],
                $requestPayload,
                $headers,
                $webhook['max_retries'] ?? 3
            );

            if ($response['success']) {
                Log::channel('cron')->info("Webhook dispatched successfully", [
                    'webhook_id' => $webhook['id'],
                    'url' => $webhook['url'],
                    'event' => $event,
                    'status_code' => $response['status_code'] ?? null,
                ]);

                return [
                    'success' => true,
                    'status_code' => $response['status_code'] ?? null,
                    'response_time_ms' => $response['response_time_ms'] ?? null,
                    'attempts' => $response['attempts'] ?? 1,
                ];
            }

            Log::error("Webhook dispatch failed", [
                'webhook_id' => $webhook['id'],
                'url' => $webhook['url'],
                'event' => $event,
                'error' => $response['message'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Failed to send webhook',
                'attempts' => $response['attempts'] ?? 1,
            ];
        } catch (\Exception $e) {
            Log::error("Webhook exception: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send HTTP request with exponential backoff retry
     */
    protected function sendWithRetry(string $url, array $payload, array $headers, int $maxRetries): array
    {
        $lastError = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $startTime = microtime(true);
                
                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->post($url, $payload);

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'status_code' => $response->status(),
                        'response_time_ms' => $responseTime,
                        'attempts' => $attempt,
                    ];
                }

                $lastError = "HTTP {$response->status()}: {$response->body()}";
                
                // Don't retry client errors (4xx)
                if ($response->clientError()) {
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }

            // Exponential backoff: 1s, 2s, 4s, 8s...
            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt - 1));
            }
        }

        return [
            'success' => false,
            'message' => $lastError ?? 'Max retries exceeded',
            'attempts' => $maxRetries,
        ];
    }

    /**
     * Generate HMAC signature for webhook verification
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);
        
        return hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Get active webhooks for specific event
     */
    protected function getActiveWebhooks(string $event): array
    {
        // In production, query database for configured webhooks
        // For now, return from config
        
        $configuredWebhooks = config('services.webhooks.custom', []);
        
        return array_filter($configuredWebhooks, function ($webhook) use ($event) {
            return ($webhook['enabled'] ?? false) && 
                   ($webhook['events'] === ['*'] || in_array($event, $webhook['events'] ?? []));
        });
    }

    /**
     * Verify webhook signature (for incoming webhooks)
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(string $url, string $secret = null): array
    {
        $testPayload = [
            'event' => 'test.webhook',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from YG Account',
                'test' => true,
            ],
            'source' => 'YG Account',
        ];

        $signature = $secret ? $this->generateSignature($testPayload, $secret) : null;

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Event' => 'test.webhook',
        ];

        try {
            $startTime = microtime(true);
            
            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->post($url, $testPayload);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'message' => 'Webhook test successful',
                ];
            }

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhook delivery statistics
     */
    public function getDeliveryStats(): array
    {
        // In production, query webhook_logs table
        // For now, return mock data
        
        return [
            'total_deliveries_today' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
            'average_response_time_ms' => 0,
            'most_active_webhooks' => [],
        ];
    }

    /**
     * Register custom webhook endpoint
     */
    public function registerWebhook(array $config): array
    {
        // Validate configuration
        $validation = $this->validateWebhookConfig($config);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Generate secret if not provided
        if (empty($config['secret'])) {
            $config['secret'] = Str::random(64);
        }

        // In production, save to database
        // For now, just return the config
        
        return [
            'success' => true,
            'webhook_id' => $config['id'] ?? Str::uuid()->toString(),
            'secret' => $config['secret'],
            'message' => 'Webhook registered successfully',
            'configuration' => $config,
        ];
    }

    /**
     * Validate webhook configuration
     */
    protected function validateWebhookConfig(array $config): array
    {
        $errors = [];

        if (empty($config['url'])) {
            $errors[] = 'Webhook URL is required';
        } elseif (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid webhook URL format';
        } elseif (!str_starts_with($config['url'], 'https://')) {
            $errors[] = 'Webhook URL must use HTTPS';
        }

        if (empty($config['events'])) {
            $errors[] = 'At least one event must be specified';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Common event types for documentation
     */
    public function getAvailableEvents(): array
    {
        return [
            'cron_job.failed' => 'Cron job execution failed',
            'cron_job.success' => 'Cron job executed successfully',
            'backup.verified' => 'Backup verification completed',
            'backup.failed' => 'Backup verification failed',
            'deployment.started' => 'Deployment process started',
            'deployment.completed' => 'Deployment completed',
            'deployment.failed' => 'Deployment failed',
            'incident.created' => 'New incident created',
            'incident.resolved' => 'Incident resolved',
            'anomaly.detected' => 'Anomaly detected in system metrics',
            'user.login' => 'User login event',
            'payment.completed' => 'Payment transaction completed',
            '*' => 'All events (wildcard)',
        ];
    }
}
