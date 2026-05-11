<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Unified Payment Service
 * 
 * Central payment orchestration service that provides a unified interface
 * for all payment providers with smart routing, failover, and transaction management.
 */
class UnifiedPaymentService
{
    protected array $providers = [];
    protected array $providerConfigs = [];

    public function __construct()
    {
        $this->initializeProviders();
        $this->loadProviderConfigs();
    }

    /**
     * Initialize all payment providers
     */
    protected function initializeProviders(): void
    {
        $this->providers = [
            'stripe' => new Providers\StripeProvider(),
            'paypal' => new Providers\PayPalProvider(),
            'razorpay' => new Providers\RazorpayProvider(),
            'esewa' => new Providers\ESewaProvider(),
            'khalti' => new Providers\KhaltiProvider(),
            'fonepay' => new Providers\FonepayProvider(),
        ];
    }

    /**
     * Load provider configurations from database
     */
    protected function loadProviderConfigs(): void
    {
        $configs = DB::table('payment_provider_configs')
            ->where('is_active', true)
            ->get();

        foreach ($configs as $config) {
            $this->providerConfigs[$config->provider] = $config;
        }
    }

    /**
     * Process a payment with smart routing
     *
     * @param array $paymentData Payment details
     * @param string|null $preferredProvider Optional specific provider
     * @return PaymentResponse
     */
    public function charge(array $paymentData, ?string $preferredProvider = null): PaymentResponse
    {
        try {
            // Validate payment data
            $validated = $this->validatePaymentData($paymentData);

            // Select best provider
            $provider = $preferredProvider ?? $this->selectBestProvider($validated);

            Log::info('Payment initiated', [
                'provider' => $provider,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'order_id' => $validated['order_id'],
            ]);

            // Create transaction record
            $transaction = $this->createTransactionRecord($validated, $provider);

            // Process payment through selected provider
            $result = $this->providers[$provider]->initiatePayment($validated);

            // Update transaction with result
            $this->updateTransactionStatus($transaction, $result);

            if ($result->success) {
                Log::info('Payment successful', [
                    'transaction_id' => $transaction->id,
                    'provider_transaction_id' => $result->transaction_id,
                ]);
            } else {
                Log::warning('Payment failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $result->error,
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new PaymentResponse([
                'success' => false,
                'error' => 'Payment processing failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify payment status
     *
     * @param string $transactionId Transaction ID
     * @return PaymentStatus
     */
    public function verifyPayment(string $transactionId): PaymentStatus
    {
        $transaction = DB::table('payment_transactions')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return new PaymentStatus([
                'found' => false,
                'error' => 'Transaction not found',
            ]);
        }

        $provider = $transaction->provider;
        
        if (!isset($this->providers[$provider])) {
            return new PaymentStatus([
                'found' => true,
                'error' => 'Invalid provider',
            ]);
        }

        return $this->providers[$provider]->verifyPayment($transaction->provider_transaction_id);
    }

    /**
     * Process refund
     *
     * @param string $transactionId Original transaction ID
     * @param float|null $amount Refund amount (null for full refund)
     * @param string $reason Refund reason
     * @return RefundResponse
     */
    public function refund(string $transactionId, ?float $amount = null, string $reason = ''): RefundResponse
    {
        $transaction = DB::table('payment_transactions')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return new RefundResponse([
                'success' => false,
                'error' => 'Transaction not found',
            ]);
        }

        if ($transaction->status !== 'completed') {
            return new RefundResponse([
                'success' => false,
                'error' => 'Only completed transactions can be refunded',
            ]);
        }

        $provider = $transaction->provider;
        $refundAmount = $amount ?? $transaction->amount;

        Log::info('Refund initiated', [
            'transaction_id' => $transactionId,
            'amount' => $refundAmount,
            'reason' => $reason,
        ]);

        return $this->providers[$provider]->processRefund(
            $transaction->provider_transaction_id,
            $refundAmount,
            $reason
        );
    }

    /**
     * Handle webhook from payment provider
     *
     * @param string $provider Provider name
     * @param \Illuminate\Http\Request $request Request object
     * @return WebhookResponse
     */
    public function handleWebhook(string $provider, $request): WebhookResponse
    {
        if (!isset($this->providers[$provider])) {
            return new WebhookResponse([
                'success' => false,
                'error' => 'Invalid provider',
            ]);
        }

        Log::info('Webhook received', [
            'provider' => $provider,
            'ip' => $request->ip(),
        ]);

        return $this->providers[$provider]->handleWebhook($request);
    }

    /**
     * Select best provider using smart routing
     *
     * @param array $paymentData Validated payment data
     * @return string Provider name
     */
    protected function selectBestProvider(array $paymentData): string
    {
        $activeProviders = array_keys($this->providerConfigs);

        if (empty($activeProviders)) {
            throw new Exception('No active payment providers available');
        }

        // Score each provider
        $scores = [];
        foreach ($activeProviders as $provider) {
            $scores[$provider] = $this->calculateProviderScore($provider, $paymentData);
        }

        // Select provider with highest score
        arsort($scores);
        $bestProvider = key($scores);

        Log::debug('Provider selection', [
            'selected' => $bestProvider,
            'scores' => $scores,
        ]);

        return $bestProvider;
    }

    /**
     * Calculate provider score for routing decision
     *
     * @param string $provider Provider name
     * @param array $paymentData Payment data
     * @return float Score (higher is better)
     */
    protected function calculateProviderScore(string $provider, array $paymentData): float
    {
        $score = 100.0;
        $config = $this->providerConfigs[$provider];

        // Factor 1: Historical success rate (40% weight)
        $successRate = $config->success_rate ?? 100;
        $score *= ($successRate / 100) * 0.4;

        // Factor 2: Geographic preference (30% weight)
        $country = $paymentData['customer_country'] ?? 'NP';
        $geoScore = $this->getGeographicScore($provider, $country);
        $score *= $geoScore * 0.3;

        // Factor 3: Transaction fee (20% weight)
        $feeScore = $this->getFeeScore($provider, $paymentData['amount']);
        $score *= $feeScore * 0.2;

        // Factor 4: Response time (10% weight)
        $responseTimeScore = $this->getResponseTimeScore($provider);
        $score *= $responseTimeScore * 0.1;

        return $score;
    }

    /**
     * Get geographic compatibility score
     */
    protected function getGeographicScore(string $provider, string $country): float
    {
        $geoPreferences = [
            'stripe' => ['US', 'GB', 'EU', 'CA', 'AU'],
            'paypal' => ['US', 'GB', 'EU', 'CA', 'AU', 'NP'],
            'razorpay' => ['IN', 'NP'],
            'esewa' => ['NP'],
            'khalti' => ['NP'],
            'fonepay' => ['NP'],
        ];

        $supportedCountries = $geoPreferences[$provider] ?? [];
        
        if (in_array($country, $supportedCountries)) {
            return 1.0;
        }

        return 0.3; // Lower score for unsupported countries
    }

    /**
     * Get fee-based score (lower fees = higher score)
     */
    protected function getFeeScore(string $provider, float $amount): float
    {
        $fees = [
            'stripe' => 0.029 + (0.30 / max($amount, 1)),
            'paypal' => 0.034 + (0.49 / max($amount, 1)),
            'razorpay' => 0.02,
            'esewa' => 0.018,
            'khalti' => 0.018,
            'fonepay' => 0.015,
        ];

        $fee = $fees[$provider] ?? 0.03;
        
        // Convert fee to score (lower fee = higher score)
        return 1.0 - ($fee * 10);
    }

    /**
     * Get response time score
     */
    protected function getResponseTimeScore(string $provider): float
    {
        $avgResponseTimes = [
            'stripe' => 150,
            'paypal' => 200,
            'razorpay' => 180,
            'esewa' => 250,
            'khalti' => 230,
            'fonepay' => 220,
        ];

        $responseTime = $avgResponseTimes[$provider] ?? 300;
        
        // Faster response = higher score
        return max(0.5, 1.0 - ($responseTime / 1000));
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(array $data): array
    {
        $validator = validator($data, [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|in:NPR,USD,EUR,INR,GBP',
            'customer_email' => 'required|email|max:255',
            'order_id' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'customer_country' => 'nullable|string|size:2',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new Exception('Validation failed: ' . $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Create transaction record in database
     */
    protected function createTransactionRecord(array $data, string $provider)
    {
        return DB::table('payment_transactions')->insertGetId([
            'transaction_id' => 'txn_' . uniqid(),
            'order_id' => $data['order_id'],
            'provider' => $provider,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'pending',
            'customer_email' => $data['customer_email'],
            'customer_country' => $data['customer_country'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update transaction status
     */
    protected function updateTransactionStatus($transaction, PaymentResponse $result): void
    {
        DB::table('payment_transactions')
            ->where('id', $transaction)
            ->update([
                'provider_transaction_id' => $result->transaction_id ?? null,
                'status' => $result->success ? 'pending_confirmation' : 'failed',
                'error_message' => $result->error ?? null,
                'client_secret' => $result->client_secret ?? null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Get transaction history
     *
     * @param array $filters Filter criteria
     * @return array
     */
    public function getTransactionHistory(array $filters = []): array
    {
        $query = DB::table('payment_transactions');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['customer_email'])) {
            $query->where('customer_email', $filters['customer_email']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get provider statistics
     *
     * @return array
     */
    public function getProviderStatistics(): array
    {
        $stats = [];

        foreach ($this->providers as $providerName => $provider) {
            $config = $this->providerConfigs[$providerName] ?? null;

            $stats[$providerName] = [
                'is_active' => $config ? $config->is_active : false,
                'success_rate' => $config ? $config->success_rate : 0,
                'total_transactions' => DB::table('payment_transactions')
                    ->where('provider', $providerName)
                    ->count(),
                'avg_response_time' => DB::table('payment_transactions')
                    ->where('provider', $providerName)
                    ->whereNotNull('processing_time_ms')
                    ->avg('processing_time_ms') ?? 0,
            ];
        }

        return $stats;
    }
}
