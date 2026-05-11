<?php

namespace App\Services\Payment;

/**
 * Webhook Response Object
 * 
 * Represents the result of webhook processing.
 */
class WebhookResponse
{
    public bool $success;
    public ?string $transaction_id = null;
    public ?string $event_type = null;
    public ?string $error = null;
    public array $metadata = [];

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->transaction_id = $data['transaction_id'] ?? null;
        $this->event_type = $data['event_type'] ?? null;
        $this->error = $data['error'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transaction_id,
            'event_type' => $this->event_type,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
