<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;

/**
 * Payment Provider Interface
 * 
 * Contract that all payment providers must implement.
 */
interface PaymentProviderInterface
{
    /**
     * Initiate a payment
     *
     * @param array $data Payment data
     * @return PaymentResponse
     */
    public function initiatePayment(array $data): PaymentResponse;

    /**
     * Verify payment status
     *
     * @param string $transactionId Provider transaction ID
     * @return PaymentStatus
     */
    public function verifyPayment(string $transactionId): PaymentStatus;

    /**
     * Process a refund
     *
     * @param string $transactionId Original transaction ID
     * @param float $amount Refund amount
     * @param string $reason Refund reason
     * @return RefundResponse
     */
    public function processRefund(string $transactionId, float $amount, string $reason = ''): RefundResponse;

    /**
     * Handle webhook from provider
     *
     * @param Request $request HTTP request
     * @return WebhookResponse
     */
    public function handleWebhook(Request $request): WebhookResponse;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Check if provider is available
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
