<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\SecurePaymentGateway;
use App\Services\Payment\PaymentSecurityMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * IsolatedPaymentController
 * 
 * Secure API endpoint for processing payments through the isolated third-party module.
 * All requests are subject to maximum security validation and monitoring.
 */
class IsolatedPaymentController extends Controller
{
    private $secureGateway;
    private $securityMonitor;

    public function __construct(
        SecurePaymentGateway $gateway,
        PaymentSecurityMonitor $monitor
    ) {
        $this->secureGateway = $gateway;
        $this->securityMonitor = $monitor;
        
        // Apply middleware
        $this->middleware('isolated.payment.security');
        $this->middleware('throttle:30,1'); // 30 requests per minute
    }

    /**
     * Process payment through isolated gateway
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        // Check if isolated payments are enabled
        if (!config('isolated_payment_security.enabled', false)) {
            return response()->json([
                'success' => false,
                'error' => 'Isolated payment processing is currently disabled',
                'code' => 'SERVICE_DISABLED',
            ], 503);
        }

        // Check emergency disable
        if (config('isolated_payment_security.emergency_disable', false)) {
            return response()->json([
                'success' => false,
                'error' => 'Payment processing temporarily suspended due to security concerns',
                'code' => 'EMERGENCY_DISABLE',
            ], 503);
        }

        // Check maintenance mode
        if (config('isolated_payment_security.maintenance_mode', false)) {
            return response()->json([
                'success' => false,
                'error' => config('isolated_payment_security.maintenance_message', 
                    'Payment processing is under maintenance'),
                'code' => 'MAINTENANCE_MODE',
            ], 503);
        }

        try {
            // Extract and validate payment data
            $paymentData = $request->validate([
                'amount' => 'required|numeric|min:' . config('isolated_payment_security.min_transaction_amount', 0.01),
                'currency' => 'required|string|in:' . implode(',', config('isolated_payment_security.allowed_currencies', ['NPR'])),
                'customer_email' => 'required|email|max:255',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'order_id' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'callback_url' => 'required|url|max:500',
                'metadata' => 'nullable|array',
            ]);

            // Add user context if authenticated
            if (auth()->check()) {
                $paymentData['user_id'] = auth()->id();
                $paymentData['authenticated'] = true;
            } else {
                $paymentData['authenticated'] = false;
            }

            // Add request metadata
            $paymentData['ip_address'] = $request->ip();
            $paymentData['user_agent'] = $request->userAgent();
            $paymentData['timestamp'] = Carbon::now()->toIso8601String();

            // Process through secure isolated gateway
            $result = $this->secureGateway->processIsolatedPayment($paymentData);

            // Increment request counter for monitoring
            $this->incrementRequestCounter();

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Payment validation failed', [
                'errors' => $e->errors(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);

        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            // Increment error counter
            $this->incrementErrorCounter();

            return response()->json([
                'success' => false,
                'error' => 'Payment processing failed. Please try again later.',
                'code' => 'PROCESSING_ERROR',
                'transaction_ref' => $result['transaction_id'] ?? null,
            ], 500);
        }
    }

    /**
     * Verify payment status
     * 
     * @param string $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(string $transactionId)
    {
        try {
            // Validate transaction ID format
            if (!preg_match('/^TXN_[a-f0-9]{32}_\d+$/', $transactionId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid transaction ID format',
                    'code' => 'INVALID_TRANSACTION_ID',
                ], 400);
            }

            // Query transaction status from database
            $transaction = DB::table('pay_transactions')
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'error' => 'Transaction not found',
                    'code' => 'TRANSACTION_NOT_FOUND',
                ], 404);
            }

            // Verify user has permission to view this transaction
            if (auth()->check() && $transaction->user_id !== auth()->id()) {
                // Allow admins to view any transaction
                if (!auth()->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to transaction',
                        'code' => 'UNAUTHORIZED',
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->transaction_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Verification failed',
                'code' => 'VERIFICATION_ERROR',
            ], 500);
        }
    }

    /**
     * Handle webhook callback from isolated payment processor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Verify webhook signature if configured
            if (! $this->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature',
                ], 401);
            }

            // Extract webhook data
            $webhookData = $request->all();

            // Validate required fields
            if (!isset($webhookData['transaction_id']) || !isset($webhookData['status'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required fields',
                ], 400);
            }

            // Process webhook based on status
            $status = $webhookData['status'];
            $transactionId = $webhookData['transaction_id'];

            switch ($status) {
                case 'success':
                    $this->handleSuccessfulPayment($transactionId, $webhookData);
                    break;
                    
                case 'failed':
                    $this->handleFailedPayment($transactionId, $webhookData);
                    break;
                    
                case 'pending':
                    $this->handlePendingPayment($transactionId, $webhookData);
                    break;
                    
                default:
                    Log::warning('Unknown webhook status', [
                        'status' => $status,
                        'transaction_id' => $transactionId,
                    ]);
            }

            // Acknowledge webhook
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Get security dashboard data (admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSecurityDashboard()
    {
        // Require admin authentication
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $dashboard = $this->securityMonitor->getSecurityDashboard();

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Run manual security scan (admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function runSecurityScan()
    {
        // Require admin authentication
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $scanResults = $this->securityMonitor->runSecurityScan();

        return response()->json([
            'success' => true,
            'scan_results' => $scanResults,
        ]);
    }

    /**
     * Emergency disable (super admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function emergencyDisable()
    {
        // Require super admin authentication
        if (!auth()->check() || !auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - Super Admin access required',
            ], 403);
        }

        $this->securityMonitor->emergencyDisable();

        return response()->json([
            'success' => true,
            'message' => 'Emergency disable activated. All isolated payments suspended.',
        ]);
    }

    /**
     * Re-enable after emergency (super admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function reEnableAfterEmergency()
    {
        // Require super admin authentication
        if (!auth()->check() || !auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - Super Admin access required',
            ], 403);
        }

        $this->securityMonitor->reEnableAfterEmergency();

        return response()->json([
            'success' => true,
            'message' => 'Isolated payments re-enabled.',
        ]);
    }

    /**
     * Verify webhook signature
     * 
     * @param Request $request
     * @return bool
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        // Implement webhook signature verification
        // This should match what the isolated payment processor sends
        
        $signature = $request->header('X-Webhook-Signature');
        
        if (!$signature) {
            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $secret = config('isolated_payment_security.webhook_secret');
        
        if (!$secret) {
            // If no secret configured, accept all webhooks (not recommended for production)
            Log::warning('Webhook secret not configured');
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle successful payment webhook
     */
    private function handleSuccessfulPayment(string $transactionId, array $data): void
    {
        DB::table('pay_transactions')
            ->where('transaction_id', $transactionId)
            ->update([
                'status' => 'completed',
                'processed_at' => now(),
                'updated_at' => now(),
                'metadata' => json_encode(array_merge(
                    json_decode(DB::table('pay_transactions')
                        ->where('transaction_id', $transactionId)
                        ->value('metadata') ?? '{}', true) ?? [],
                    ['webhook_data' => $data]
                )),
            ]);

        Log::info('Payment completed via webhook', [
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Handle failed payment webhook
     */
    private function handleFailedPayment(string $transactionId, array $data): void
    {
        DB::table('pay_transactions')
            ->where('transaction_id', $transactionId)
            ->update([
                'status' => 'failed',
                'error_message' => $data['error_message'] ?? 'Payment failed',
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::warning('Payment failed via webhook', [
            'transaction_id' => $transactionId,
            'error' => $data['error_message'] ?? 'Unknown error',
        ]);
    }

    /**
     * Handle pending payment webhook
     */
    private function handlePendingPayment(string $transactionId, array $data): void
    {
        DB::table('pay_transactions')
            ->where('transaction_id', $transactionId)
            ->update([
                'status' => 'pending',
                'updated_at' => now(),
            ]);

        Log::info('Payment pending via webhook', [
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Increment request counter for monitoring
     */
    private function incrementRequestCounter(): void
    {
        $key = 'payment_total_requests_1h';
        cache()->increment($key, 1);
    }

    /**
     * Increment error counter for monitoring
     */
    private function incrementErrorCounter(): void
    {
        $key = 'payment_error_count_1h';
        cache()->increment($key, 1);
    }
}
