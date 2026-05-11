<?php

namespace App\Services\Payment;

/**
 * Payment Response Object
 * 
 * Standardized response structure for all payment operations.
 */
class PaymentResponse
{
    public bool $success;
    public ?string $transaction_id = null;
    public ?string $client_secret = null;
    public ?string $redirect_url = null;
    public ?string $provider = null;
    public ?string $error = null;
    public ?string $error_code = null;
    public array $metadata = [];

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->transaction_id = $data['transaction_id'] ?? null;
        $this->client_secret = $data['client_secret'] ?? null;
        $this->redirect_url = $data['redirect_url'] ?? null;
        $this->provider = $data['provider'] ?? null;
        $this->error = $data['error'] ?? null;
        $this->error_code = $data['error_code'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transaction_id,
            'client_secret' => $this->client_secret,
            'redirect_url' => $this->redirect_url,
            'provider' => $this->provider,
            'error' => $this->error,
            'error_code' => $this->error_code,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
