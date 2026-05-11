<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookId;
    protected $payload;
    protected $maxRetries;

    /**
     * Create a new job instance.
     */
    public function __construct(int $webhookId, array $payload, int $maxRetries = 3)
    {
        $this->webhookId = $webhookId;
        $this->payload = $payload;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $webhook = \App\Models\Webhook::find($this->webhookId);
            
            if (!$webhook || !$webhook->is_active) {
                Log::warning("Webhook not found or inactive", ['webhook_id' => $this->webhookId]);
                return;
            }

            // Calculate signature for security (HMAC SHA256)
            $signature = hash_hmac('sha256', json_encode($this->payload), $webhook->secret);
            
            // Send webhook request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->payload['event'] ?? 'unknown',
                'X-Webhook-ID' => $this->webhookId,
                'User-Agent' => 'YG-Developer-Portal/1.0',
            ])
            ->timeout(30)
            ->post($webhook->url, $this->payload);

            // Create delivery log
            WebhookDelivery::create([
                'webhook_id' => $this->webhookId,
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 10000), // Limit size
                'response_time_ms' => $response->handlerStats()['total_time'] * 1000 ?? 0,
                'request_payload' => json_encode($this->payload),
                'signature' => $signature,
                'delivered_at' => now(),
                'is_success' => $response->successful(),
            ]);

            if ($response->successful()) {
                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $this->webhookId,
                    'url' => $webhook->url,
                    'status' => $response->status(),
                ]);
                
                // Update webhook last_delivery_at
                $webhook->update(['last_delivery_at' => now()]);
            } else {
                Log::warning("Webhook delivery failed", [
                    'webhook_id' => $this->webhookId,
                    'url' => $webhook->url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                // Retry with exponential backoff if not successful
                if ($this->attempts() < $this->maxRetries) {
                    $delay = pow(2, $this->attempts()) * 60; // 2, 4, 8 minutes
                    $this->release($delay);
                } else {
                    Log::error("Webhook delivery failed after max retries", [
                        'webhook_id' => $this->webhookId,
                        'attempts' => $this->attempts(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Webhook delivery exception", [
                'webhook_id' => $this->webhookId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retry on exception
            if ($this->attempts() < $this->maxRetries) {
                $delay = pow(2, $this->attempts()) * 60;
                $this->release($delay);
            }
        }
    }
}
