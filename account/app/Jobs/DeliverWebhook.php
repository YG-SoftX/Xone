<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeliverWebhook Job
 * 
 * Asynchronously delivers webhook events to registered endpoints.
 * Implements retry logic with exponential backoff.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 5;
    public $backoff = [10, 30, 60, 120, 300]; // Exponential backoff in seconds

    protected Webhook $webhook;
    protected array $payload;
    protected string $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Webhook $webhook, array $payload, string $event)
    {
        $this->webhook = $webhook;
        $this->payload = $payload;
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'payload' => json_encode($this->payload),
            'status' => 'pending',
            'attempt' => $this->attempts(),
        ]);

        try {
            // Generate HMAC signature
            $signature = hash_hmac(
                'sha256',
                json_encode($this->payload),
                $this->webhook->secret
            );

            // Send webhook request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->event,
                'X-Webhook-ID' => (string) $this->webhook->id,
                'User-Agent' => 'YG-Account-Webhooks/1.0',
            ])->timeout(10)->post($this->webhook->url, $this->payload);

            // Update delivery status
            if ($response->successful()) {
                $delivery->update([
                    'status' => 'delivered',
                    'response_code' => $response->status(),
                    'response_body' => substr($response->body(), 0, 1000),
                    'delivered_at' => now(),
                ]);

                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $this->webhook->id,
                    'event' => $this->event,
                    'url' => $this->webhook->url,
                    'response_code' => $response->status(),
                ]);
            } else {
                throw new \Exception("HTTP {$response->status()}: {$response->body()}");
            }

        } catch (\Exception $e) {
            $delivery->update([
                'status' => 'failed',
                'response_code' => null,
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            Log::warning('Webhook delivery failed', [
                'webhook_id' => $this->webhook->id,
                'event' => $this->event,
                'url' => $this->webhook->url,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle job failure after all retries exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery permanently failed after all retries', [
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'url' => $this->webhook->url,
            'error' => $exception->getMessage(),
        ]);

        // Mark webhook as inactive after repeated failures
        if ($this->webhook->failure_count >= 10) {
            $this->webhook->update(['is_active' => false]);
            
            Log::warning('Webhook disabled due to repeated failures', [
                'webhook_id' => $this->webhook->id,
                'failure_count' => $this->webhook->failure_count,
            ]);
        }
    }
}
