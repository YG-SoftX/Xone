<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Subscription;
use App\Models\BillingInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillingService
{
    protected $ygPayUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->ygPayUrl = config('services.yg_pay.url', 'https://pay.ygxone.com');
        $this->apiKey = config('services.yg_pay.api_key');
    }

    /**
     * Create subscription via YG Pay
     */
    public function createSubscription(Project $project, string $planId, array $paymentMethod): Subscription
    {
        try {
            // Call YG Pay API to create subscription
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->ygPayUrl . '/api/v1/subscriptions', [
                'project_id' => $project->id,
                'plan_id' => $planId,
                'payment_method' => $paymentMethod,
                'metadata' => [
                    'project_name' => $project->name,
                    'user_email' => $project->user->email,
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception('YG Pay API error: ' . $response->body());
            }

            $data = $response->json();

            // Create local subscription record
            $subscription = $project->subscriptions()->create([
                'plan_id' => $planId,
                'provider' => 'yg_pay',
                'provider_subscription_id' => $data['subscription_id'],
                'status' => $data['status'] ?? 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            Log::info('Subscription created via YG Pay', [
                'project_id' => $project->id,
                'subscription_id' => $subscription->id,
                'provider_id' => $data['subscription_id'],
            ]);

            return $subscription;

        } catch (\Exception $e) {
            Log::error('Failed to create subscription via YG Pay', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel subscription via YG Pay
     */
    public function cancelSubscription(Subscription $subscription, bool $immediate = false): Subscription
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->delete($this->ygPayUrl . '/api/v1/subscriptions/' . $subscription->provider_subscription_id, [
                'cancel_at_period_end' => !$immediate,
            ]);

            if ($response->failed()) {
                throw new \Exception('YG Pay API error: ' . $response->body());
            }

            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            Log::info('Subscription canceled via YG Pay', [
                'subscription_id' => $subscription->id,
                'immediate' => $immediate,
            ]);

            return $subscription;

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription via YG Pay', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate invoice for project
     */
    public function generateInvoice(Project $project): BillingInvoice
    {
        // Calculate usage-based charges
        $apiCharges = $this->calculateApiCharges($project);
        $aiCharges = $this->calculateAiCharges($project);
        $storageCharges = $this->calculateStorageCharges($project);
        $subscriptionFee = $this->getSubscriptionFee($project);

        $total = $apiCharges + $aiCharges + $storageCharges + $subscriptionFee;

        // Create invoice
        $invoice = $project->invoices()->create([
            'invoice_number' => $this->generateInvoiceNumber($project),
            'amount' => $total,
            'currency' => 'USD',
            'status' => 'pending',
            'due_date' => now()->addDays(30),
            'line_items' => [
                'api_usage' => [
                    'description' => 'API Calls',
                    'quantity' => $apiCharges['count'] ?? 0,
                    'unit_price' => 0.001,
                    'total' => $apiCharges['amount'] ?? 0,
                ],
                'ai_usage' => [
                    'description' => 'AI Model Usage',
                    'quantity' => $aiCharges['tokens'] ?? 0,
                    'unit_price' => 0.00001,
                    'total' => $aiCharges['amount'] ?? 0,
                ],
                'storage' => [
                    'description' => 'Cloud Storage',
                    'quantity' => $storageCharges['gb'] ?? 0,
                    'unit_price' => 0.10,
                    'total' => $storageCharges['amount'] ?? 0,
                ],
                'subscription' => [
                    'description' => 'Monthly Subscription',
                    'quantity' => 1,
                    'unit_price' => $subscriptionFee,
                    'total' => $subscriptionFee,
                ],
            ],
        ]);

        // Process payment via YG Pay
        $this->processInvoicePayment($invoice);

        Log::info('Invoice generated', [
            'invoice_number' => $invoice->invoice_number,
            'amount' => $total,
            'project_id' => $project->id,
        ]);

        return $invoice;
    }

    /**
     * Process invoice payment via YG Pay
     */
    protected function processInvoicePayment(BillingInvoice $invoice): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->ygPayUrl . '/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'description' => 'Invoice #' . $invoice->invoice_number,
            ]);

            if ($response->successful()) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            } else {
                $invoice->update(['status' => 'failed']);
                
                Log::warning('Invoice payment failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            $invoice->update(['status' => 'failed']);
        }
    }

    /**
     * Calculate API usage charges
     */
    protected function calculateApiCharges(Project $project): array
    {
        $apiCalls = $project->aiUsageLogs()
            ->whereMonth('created_at', now()->month)
            ->count();

        return [
            'count' => $apiCalls,
            'amount' => $apiCalls * 0.001, // $0.001 per API call
        ];
    }

    /**
     * Calculate AI usage charges
     */
    protected function calculateAiCharges(Project $project): array
    {
        $totalTokens = $project->aiUsageLogs()
            ->whereMonth('created_at', now()->month)
            ->sum('cost');

        return [
            'tokens' => $totalTokens,
            'amount' => $totalTokens,
        ];
    }

    /**
     * Calculate storage charges
     */
    protected function calculateStorageCharges(Project $project): array
    {
        // This would integrate with YG Drive or unified storage
        $storageGB = 0; // TODO: Get from storage service

        return [
            'gb' => $storageGB,
            'amount' => $storageGB * 0.10, // $0.10 per GB
        ];
    }

    /**
     * Get subscription fee
     */
    protected function getSubscriptionFee(Project $project): float
    {
        $subscription = $project->activeSubscription;

        if (!$subscription) {
            return 0;
        }

        // Map plan IDs to prices
        $prices = [
            'free' => 0,
            'basic' => 9.99,
            'pro' => 29.99,
            'enterprise' => 99.99,
        ];

        return $prices[$subscription->plan_id] ?? 0;
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(Project $project): string
    {
        $count = $project->invoices()->count() + 1;
        $year = date('Y');
        
        return 'INV-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Handle YG Pay webhook
     */
    public function handleWebhook(array $payload): void
    {
        $eventType = $payload['event_type'] ?? null;

        switch ($eventType) {
            case 'payment.succeeded':
                $this->handlePaymentSucceeded($payload);
                break;

            case 'payment.failed':
                $this->handlePaymentFailed($payload);
                break;

            case 'subscription.updated':
                $this->handleSubscriptionUpdated($payload);
                break;

            default:
                Log::warning('Unknown YG Pay webhook event', [
                    'event_type' => $eventType,
                ]);
        }
    }

    protected function handlePaymentSucceeded(array $payload): void
    {
        $invoiceId = $payload['invoice_id'] ?? null;
        
        if ($invoiceId) {
            $invoice = BillingInvoice::find($invoiceId);
            
            if ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                Log::info('Payment succeeded via webhook', [
                    'invoice_id' => $invoiceId,
                ]);
            }
        }
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $invoiceId = $payload['invoice_id'] ?? null;
        
        if ($invoiceId) {
            $invoice = BillingInvoice::find($invoiceId);
            
            if ($invoice) {
                $invoice->update(['status' => 'failed']);

                Log::warning('Payment failed via webhook', [
                    'invoice_id' => $invoiceId,
                    'reason' => $payload['reason'] ?? 'Unknown',
                ]);
            }
        }
    }

    protected function handleSubscriptionUpdated(array $payload): void
    {
        $providerSubscriptionId = $payload['subscription_id'] ?? null;
        
        if ($providerSubscriptionId) {
            $subscription = Subscription::where('provider_subscription_id', $providerSubscriptionId)->first();
            
            if ($subscription) {
                $subscription->update([
                    'status' => $payload['status'] ?? $subscription->status,
                    'current_period_end' => $payload['period_end'] ?? $subscription->current_period_end,
                ]);

                Log::info('Subscription updated via webhook', [
                    'subscription_id' => $subscription->id,
                    'new_status' => $payload['status'],
                ]);
            }
        }
    }
}
