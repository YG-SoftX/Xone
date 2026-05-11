<?php

namespace App\Services\Payment;

/**
 * Payment Status Object
 * 
 * Represents the current status of a payment transaction.
 */
class PaymentStatus
{
    public bool $found;
    public string $status; // pending, completed, failed, refunded, disputed
    public ?float $amount = null;
    public ?string $currency = null;
    public ?string $provider_transaction_id = null;
    public ?string $error = null;
    public array $metadata = [];

    public function __construct(array $data = [])
    {
        $this->found = $data['found'] ?? false;
        $this->status = $data['status'] ?? 'unknown';
        $this->amount = $data['amount'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->provider_transaction_id = $data['provider_transaction_id'] ?? null;
        $this->error = $data['error'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'found' => $this->found,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider_transaction_id' => $this->provider_transaction_id,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
