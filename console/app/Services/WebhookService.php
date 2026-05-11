<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Create webhook endpoint
     */
    public function createEndpoint(Project $project, array $data): WebhookEndpoint
    {
        return $project->webhookEndpoints()->create([
            'url' => $data['url'],
            'events' => $data['events'] ?? ['*'],
            'secret' => $this->generateSecret(),
            'active' => true,
            'max_retries' => $data['max_retries'] ?? 3,
        ]);
    }

    /**
     * Update webhook endpoint
     */
    public function updateEndpoint(WebhookEndpoint $endpoint, array $data): WebhookEndpoint
    {
        $endpoint->update([
            'url' => $data['url'] ?? $endpoint->url,
            'events' => $data['events'] ?? $endpoint->events,
            'active' => $data['active'] ?? $endpoint->active,
            'max_retries' => $data['max_retries'] ?? $endpoint->max_retries,
        ]);

        return $endpoint;
    }

    /**
     * Delete webhook endpoint
     */
    public function deleteEndpoint(WebhookEndpoint $endpoint): bool
    {
        return $endpoint->delete();
    }

    /**
     * Test webhook delivery
     */
    public function testDelivery(WebhookEndpoint $endpoint): array
    {
        $payload = [
            'event' => 'test.webhook',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'This is a test webhook',
            ],
        ];

        return $this->deliverWebhook($endpoint, $payload);
    }

    /**
     * Deliver webhook to endpoint
     */
    public function deliverWebhook(WebhookEndpoint $endpoint, array $payload): array
    {
        $signature = $this->generateSignature($endpoint->secret, json_encode($payload));

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $payload['event'] ?? 'unknown',
            ])->timeout(10)->post($endpoint->url, $payload);

            $delivery = $endpoint->deliveries()->create([
                'event_type' => $payload['event'] ?? 'unknown',
                'payload' => $payload,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'attempt' => 1,
                'delivered_at' => now(),
            ]);

            Log::info('Webhook delivered', [
                'endpoint_id' => $endpoint->id,
                'status' => $response->status(),
            ]);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'delivery_id' => $delivery->id,
            ];

        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'endpoint_id' => $endpoint->id,
                'error' => $e->getMessage(),
            ]);

            // Schedule retry
            $this->scheduleRetry($endpoint, $payload, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Schedule retry for failed delivery
     */
    protected function scheduleRetry(WebhookEndpoint $endpoint, array $payload, string $error): void
    {
        $lastDelivery = $endpoint->deliveries()
            ->where('event_type', $payload['event'])
            ->orderByDesc('created_at')
            ->first();

        $attempt = $lastDelivery ? $lastDelivery->attempt + 1 : 1;

        if ($attempt <= $endpoint->max_retries) {
            // Calculate delay with exponential backoff
            $delay = pow(2, $attempt - 1) * 60; // 2, 4, 8 minutes

            Log::info('Webhook retry scheduled', [
                'endpoint_id' => $endpoint->id,
                'attempt' => $attempt,
                'delay_seconds' => $delay,
            ]);

            // In production, dispatch to queue with delay
            // WebhookDeliveryJob::dispatch($endpoint, $payload)->delay(now()->addSeconds($delay));
        } else {
            Log::warning('Webhook max retries exceeded', [
                'endpoint_id' => $endpoint->id,
                'event' => $payload['event'],
            ]);
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get webhook delivery statistics
     */
    public function getDeliveryStats(WebhookEndpoint $endpoint): array
    {
        $deliveries = $endpoint->deliveries()->get();

        return [
            'total_deliveries' => $deliveries->count(),
            'successful_deliveries' => $deliveries->where('status_code', '>=', 200)
                ->where('status_code', '<', 300)->count(),
            'failed_deliveries' => $deliveries->where(function($q) {
                $q->whereNull('status_code')
                  ->orWhere('status_code', '<', 200)
                  ->orWhere('status_code', '>=', 300);
            })->count(),
            'success_rate' => $deliveries->count() > 0 
                ? round(($deliveries->where('status_code', '>=', 200)
                    ->where('status_code', '<', 300)->count() / $deliveries->count()) * 100, 2)
                : 0,
            'average_response_time' => null, // TODO: Track response time
        ];
    }

    /**
     * Generate HMAC secret
     */
    protected function generateSecret(): string
    {
        return Str::random(32);
    }

    /**
     * Generate signature for payload
     */
    protected function generateSignature(string $secret, string $payload): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Filter events for endpoint
     */
    public function shouldDeliverEvent(WebhookEndpoint $endpoint, string $event): bool
    {
        if (in_array('*', $endpoint->events)) {
            return true;
        }

        return in_array($event, $endpoint->events);
    }
}
