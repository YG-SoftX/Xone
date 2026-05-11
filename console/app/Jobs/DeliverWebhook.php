<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WebhookEndpoint $endpoint;
    protected array $payload;
    public int $tries;
    public int $backoff;

    public function __construct(WebhookEndpoint $endpoint, array $payload)
    {
        $this->endpoint = $endpoint;
        $this->payload = $payload;
        $this->tries = $endpoint->max_retries ?? 3;
        $this->backoff = [120, 240, 480]; // 2, 4, 8 minutes
    }

    public function handle(WebhookService $webhookService): void
    {
        $result = $webhookService->deliverWebhook($this->endpoint, $this->payload);

        if (!$result['success']) {
            throw new \Exception('Webhook delivery failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Log the failure and optionally notify admin
        \Illuminate\Support\Facades\Log::error('Webhook delivery permanently failed', [
            'endpoint_id' => $this->endpoint->id,
            'event_type' => $this->payload['event'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);

        // Notify project owner about persistent failure
        if ($this->endpoint->project && $this->endpoint->project->user) {
            $this->endpoint->project->user->notify(
                new \App\Notifications\WebhookDeliveryFailed(
                    $this->endpoint,
                    $this->payload['event'] ?? 'unknown',
                    $this->tries
                )
            );
        }
    }
}
