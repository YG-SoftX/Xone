<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\PaymentResponse;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Pay Provider - Integrates with internal YG Pay service
 * 
 * This provider connects to the YG Pay microservice (pay.ygxone.com)
 * for all payment processing, leveraging its multi-gateway setup.
 */
class YGPayProvider implements PaymentProviderInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $currency;

    public function __construct()
    {
        $this->apiKey = config('payment.ygpay.api_key');
        $this->apiUrl = rtrim(config('payment.ygpay.api_url', 'https://pay.ygxone.com/api/v1'), '/');
        $this->currency = config('payment.stripe.currency', 'USD');
    }

    /**
     * Process a one-time payment via YG Pay
     */
    public function charge(array $data): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/payments/charge", [
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $this->currency,
                'description' => $data['description'] ?? 'Payment',
                'customer_email' => $data['customer_email'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'return_url' => $data['return_url'] ?? null,
                'cancel_url' => $data['cancel_url'] ?? null,
            ]);

            if ($response->failed()) {
                throw new Exception("YG Pay API error: " . $response->body());
            }

            $result = $response->json();

            return new PaymentResponse([
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? null,
                'provider' => 'ygpay',
                'status' => $result['status'] ?? 'pending',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $this->currency,
                'message' => $result['message'] ?? 'Payment initiated',
                'redirect_url' => $result['redirect_url'] ?? null,
                'client_secret' => $result['client_secret'] ?? null,
                'raw_response' => $result,
            ]);

        } catch (Exception $e) {
            Log::error('YG Pay Charge Failed', [
                'error' => $e->getMessage(),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $this->currency,
            ]);

            return new PaymentResponse([
                'success' => false,
                'provider' => 'ygpay',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'error_code' => 'ygpay_charge_error',
            ]);
        }
    }

    /**
     * Create a subscription via YG Pay
     */
    public function createSubscription(array $data): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/subscriptions/create", [
                'plan_id' => $data['plan_id'],
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'] ?? null,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'trial_days' => $data['trial_days'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
            ]);

            if ($response->failed()) {
                throw new Exception("YG Pay API error: " . $response->body());
            }

            $result = $response->json();

            return new PaymentResponse([
                'success' => true,
                'subscription_id' => $result['subscription_id'] ?? null,
                'provider' => 'ygpay',
                'status' => $result['status'] ?? 'active',
                'message' => $result['message'] ?? 'Subscription created',
                'next_billing_date' => $result['next_billing_date'] ?? null,
                'raw_response' => $result,
            ]);

        } catch (Exception $e) {
            Log::error('YG Pay Subscription Creation Failed', [
                'error' => $e->getMessage(),
                'plan_id' => $data['plan_id'] ?? null,
            ]);

            return new PaymentResponse([
                'success' => false,
                'provider' => 'ygpay',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'error_code' => 'ygpay_subscription_error',
            ]);
        }
    }

    /**
     * Cancel a subscription via YG Pay
     */
    public function cancelSubscription(string $subscriptionId): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/subscriptions/{$subscriptionId}/cancel");

            if ($response->failed()) {
                throw new Exception("YG Pay API error: " . $response->body());
            }

            $result = $response->json();

            return new PaymentResponse([
                'success' => true,
                'subscription_id' => $subscriptionId,
                'provider' => 'ygpay',
                'status' => 'cancelled',
                'message' => $result['message'] ?? 'Subscription cancelled',
                'cancelled_at' => $result['cancelled_at'] ?? now()->toIso8601String(),
                'raw_response' => $result,
            ]);

        } catch (Exception $e) {
            Log::error('YG Pay Subscription Cancellation Failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);

            return new PaymentResponse([
                'success' => false,
                'provider' => 'ygpay',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'error_code' => 'ygpay_cancel_error',
            ]);
        }
    }

    /**
     * Refund a payment via YG Pay
     */
    public function refund(string $transactionId, float $amount = null): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/payments/{$transactionId}/refund", [
                'amount' => $amount, // null = full refund
                'reason' => 'Customer requested refund',
            ]);

            if ($response->failed()) {
                throw new Exception("YG Pay API error: " . $response->body());
            }

            $result = $response->json();

            return new PaymentResponse([
                'success' => true,
                'refund_id' => $result['refund_id'] ?? null,
                'transaction_id' => $transactionId,
                'provider' => 'ygpay',
                'status' => 'refunded',
                'amount' => $amount ?? $result['refunded_amount'],
                'message' => $result['message'] ?? 'Refund processed',
                'raw_response' => $result,
            ]);

        } catch (Exception $e) {
            Log::error('YG Pay Refund Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return new PaymentResponse([
                'success' => false,
                'provider' => 'ygpay',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'error_code' => 'ygpay_refund_error',
            ]);
        }
    }

    /**
     * Verify webhook signature from YG Pay
     */
    public function verifyWebhook(array $payload, string $signature): bool
    {
        try {
            // YG Pay uses HMAC-SHA256 signature verification
            $expectedSignature = hash_hmac(
                'sha256',
                json_encode($payload),
                $this->apiKey
            );

            return hash_equals($expectedSignature, $signature);

        } catch (Exception $e) {
            Log::error('YG Pay Webhook Verification Failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get transaction details from YG Pay
     */
    public function getTransaction(string $transactionId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ])->get("{$this->apiUrl}/payments/{$transactionId}");

            if ($response->failed()) {
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('YG Pay Transaction Lookup Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return null;
        }
    }

    /**
     * Check if provider is available and healthy
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->apiUrl}/health");

            return $response->successful() && $response->json('status') === 'healthy';

        } catch (Exception $e) {
            Log::warning('YG Pay Health Check Failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'YG Pay';
    }

    /**
     * Get provider identifier
     */
    public function getIdentifier(): string
    {
        return 'ygpay';
    }
}
