<?php

namespace App\Services;

use App\Models\BillingAccount;
use App\Models\PaymentTransaction;
use App\Services\Payment\Providers\YGPayProvider;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected YGPayProvider $ygpay;
    protected string $defaultProvider = 'ygpay';

    public function __construct()
    {
        // Initialize YG Pay provider (our internal payment service)
        $this->ygpay = new YGPayProvider();
        
        Log::info('PaymentService initialized with YG Pay provider');
    }

    /**
     * Create a payment intent (one-time payment) via YG Pay.
     * 
     * @param BillingAccount $billingAccount
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $metadata
     * @return array ['success' => bool, 'provider' => string, 'client_secret' => string|null, 'transaction' => PaymentTransaction|null, 'error' => string|null]
     */
    public function createPaymentIntent(
        BillingAccount $billingAccount,
        float $amount,
        string $currency = 'USD',
        string $description = 'Payment',
        array $metadata = []
    ): array {
        try {
            Log::info('Creating payment intent via YG Pay', [
                'billing_account_id' => $billingAccount->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            // Call YG Pay to create payment
            $result = $this->ygpay->charge([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'customer_email' => $billingAccount->user->email ?? null,
                'customer_name' => $billingAccount->user->name ?? null,
                'metadata' => array_merge($metadata, [
                    'billing_account_id' => $billingAccount->id,
                    'platform' => 'yg-account',
                ]),
                'return_url' => route('billing.payment.confirm'),
                'cancel_url' => route('billing.payment.cancel'),
            ]);

            if (!$result->success) {
                throw new Exception($result->message ?? 'Payment initiation failed');
            }

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'billing_account_id' => $billingAccount->id,
                'provider' => 'ygpay',
                'provider_transaction_id' => $result->transaction_id,
                'type' => 'payment',
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => $result->status ?? 'pending',
                'metadata' => [
                    'client_secret' => $result->client_secret,
                    'redirect_url' => $result->redirect_url,
                    'ygpay_response' => $result->raw_response,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::info('Payment intent created successfully via YG Pay', [
                'transaction_id' => $transaction->id,
                'ygpay_transaction_id' => $result->transaction_id,
            ]);

            return [
                'success' => true,
                'provider' => 'ygpay',
                'client_secret' => $result->client_secret,
                'redirect_url' => $result->redirect_url,
                'transaction' => $transaction,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Payment intent creation failed', [
                'error' => $e->getMessage(),
                'billing_account_id' => $billingAccount->id,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'provider' => 'ygpay',
                'client_secret' => null,
                'redirect_url' => null,
                'transaction' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a subscription via YG Pay.
     */
    public function createSubscription(
        BillingAccount $billingAccount,
        string $planId,
        string $billingCycle = 'monthly',
        int $trialDays = 0,
        array $metadata = []
    ): array {
        try {
            Log::info('Creating subscription via YG Pay', [
                'billing_account_id' => $billingAccount->id,
                'plan_id' => $planId,
                'billing_cycle' => $billingCycle,
            ]);

            $result = $this->ygpay->createSubscription([
                'plan_id' => $planId,
                'customer_email' => $billingAccount->user->email,
                'customer_name' => $billingAccount->user->name ?? null,
                'billing_cycle' => $billingCycle,
                'trial_days' => $trialDays,
                'metadata' => array_merge($metadata, [
                    'billing_account_id' => $billingAccount->id,
                    'platform' => 'yg-account',
                ]),
            ]);

            if (!$result->success) {
                throw new Exception($result->message ?? 'Subscription creation failed');
            }

            // Update billing account
            $billingAccount->update([
                'subscription_id' => $result->subscription_id,
                'subscription_status' => $result->status,
                'next_billing_date' => $result->next_billing_date,
            ]);

            Log::info('Subscription created successfully via YG Pay', [
                'subscription_id' => $result->subscription_id,
            ]);

            return [
                'success' => true,
                'provider' => 'ygpay',
                'subscription_id' => $result->subscription_id,
                'status' => $result->status,
                'next_billing_date' => $result->next_billing_date,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Subscription creation failed', [
                'error' => $e->getMessage(),
                'billing_account_id' => $billingAccount->id,
                'plan_id' => $planId,
            ]);

            return [
                'success' => false,
                'provider' => 'ygpay',
                'subscription_id' => null,
                'status' => null,
                'next_billing_date' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a subscription via YG Pay.
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            Log::info('Cancelling subscription via YG Pay', [
                'subscription_id' => $subscriptionId,
            ]);

            $result = $this->ygpay->cancelSubscription($subscriptionId);

            if (!$result->success) {
                throw new Exception($result->message ?? 'Subscription cancellation failed');
            }

            Log::info('Subscription cancelled successfully via YG Pay', [
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'success' => true,
                'provider' => 'ygpay',
                'cancelled_at' => $result->cancelled_at,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Subscription cancellation failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'success' => false,
                'provider' => 'ygpay',
                'cancelled_at' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a payment via YG Pay.
     */
    public function refund(string $transactionId, float $amount = null): array
    {
        try {
            Log::info('Processing refund via YG Pay', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);

            $result = $this->ygpay->refund($transactionId, $amount);

            if (!$result->success) {
                throw new Exception($result->message ?? 'Refund failed');
            }

            // Update transaction status
            $transaction = PaymentTransaction::where('provider_transaction_id', $transactionId)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => 'refunded',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'refund_id' => $result->refund_id,
                        'refunded_amount' => $result->amount,
                        'refunded_at' => now()->toIso8601String(),
                    ]),
                ]);
            }

            Log::info('Refund processed successfully via YG Pay', [
                'transaction_id' => $transactionId,
                'refund_id' => $result->refund_id,
            ]);

            return [
                'success' => true,
                'provider' => 'ygpay',
                'refund_id' => $result->refund_id,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'provider' => 'ygpay',
                'refund_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature from YG Pay.
     */
    public function verifyWebhook(array $payload, string $signature): bool
    {
        return $this->ygpay->verifyWebhook($payload, $signature);
    }

    /**
     * Get transaction details from YG Pay.
     */
    public function getTransaction(string $transactionId): ?array
    {
        return $this->ygpay->getTransaction($transactionId);
    }

    /**
     * Check if YG Pay is healthy and available.
     */
    public function healthCheck(): bool
    {
        return $this->ygpay->healthCheck();
    }

    /**
     * Get the default provider name.
     */
    public function getDefaultProvider(): string
    {
        return $this->defaultProvider;
    }

    /**
     * Get all enabled providers (now only YG Pay).
     */
    public function getEnabledProviders(): array
    {
        return ['ygpay'];
    }
}
