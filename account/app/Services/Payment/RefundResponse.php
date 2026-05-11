<?php

namespace App\Services\Payment;

/**
 * Refund Response Object
 * 
 * Represents the result of a refund operation.
 */
class RefundResponse
{
    public bool $success;
    public ?string $refund_id = null;
    public ?float $refunded_amount = null;
    public ?string $status = null; // pending, completed, failed
    public ?string $error = null;
    public ?string $estimated_arrival = null;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->refund_id = $data['refund_id'] ?? null;
        $this->refunded_amount = $data['refunded_amount'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->error = $data['error'] ?? null;
        $this->estimated_arrival = $data['estimated_arrival'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refund_id,
            'refunded_amount' => $this->refunded_amount,
            'status' => $this->status,
            'error' => $this->error,
            'estimated_arrival' => $this->estimated_arrival,
        ];
    }
}
