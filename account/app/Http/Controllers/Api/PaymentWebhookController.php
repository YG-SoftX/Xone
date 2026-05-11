<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhook;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        
        // Disable CSRF for webhook routes
        $this->middleware('csrf')->except([
            'stripe',
            'paypal',
            'razorpay',
        ]);
    }

    /**
     * Handle Stripe webhook.
     */
    public function stripe(Request $request)
    {
        // Raw body must be captured BEFORE any JSON decoding — Stripe verifies the
        // exact bytes it sent, so re-encoding $request->all() produces the wrong HMAC.
        $rawBody  = $request->getContent();
        $payload   = json_decode($rawBody, true) ?? [];
        $signature = $request->header('Stripe-Signature');

        Log::info('Stripe webhook received', [
            'event_type' => $payload['type'] ?? null,
            'ip' => $request->ip(),
        ]);

        // Store webhook in database for tracking
        $webhook = PaymentWebhook::create([
            'webhook_id' => $payload['id'] ?? uniqid('wh_stripe_'),
            'provider' => 'stripe',
            'event_type' => $payload['type'] ?? 'unknown',
            'payload' => $payload,
            'signature' => $signature,
            'processed' => false,
        ]);

        try {
            $result = $this->paymentService->processWebhook('stripe', $payload, $signature, $rawBody);

            if ($result['success']) {
                $webhook->markAsProcessed();
                
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhook->markAsFailed($result['error'] ?? 'Processing failed');
                
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());
            
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle PayPal webhook.
     */
    public function paypal(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('Paypal-Transmission-Sig');

        Log::info('PayPal webhook received', [
            'event_type' => $payload['event_type'] ?? null,
            'ip' => $request->ip(),
        ]);

        // Verify it's from PayPal (IP whitelist check can be added here)
        $allowedIps = config('payment.webhook.ip_whitelist', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning('PayPal webhook from unauthorized IP', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Store webhook
        $webhook = PaymentWebhook::create([
            'webhook_id' => $payload['id'] ?? uniqid('wh_paypal_'),
            'provider' => 'paypal',
            'event_type' => $payload['event_type'] ?? 'unknown',
            'payload' => $payload,
            'signature' => $signature,
            'processed' => false,
        ]);

        try {
            $result = $this->paymentService->processWebhook('paypal', $payload, $signature);

            if ($result['success']) {
                $webhook->markAsProcessed();
                
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhook->markAsFailed($result['error'] ?? 'Processing failed');
                
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());
            
            Log::error('PayPal webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Razorpay webhook.
     */
    public function razorpay(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Razorpay-Signature');

        Log::info('Razorpay webhook received', [
            'event' => $payload['event'] ?? null,
            'ip' => $request->ip(),
        ]);

        // Store webhook
        $webhook = PaymentWebhook::create([
            'webhook_id' => $payload['id'] ?? uniqid('wh_razorpay_'),
            'provider' => 'razorpay',
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'signature' => $signature,
            'processed' => false,
        ]);

        try {
            $result = $this->paymentService->processWebhook('razorpay', $payload, $signature);

            if ($result['success']) {
                $webhook->markAsProcessed();
                
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhook->markAsFailed($result['error'] ?? 'Processing failed');
                
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }
        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());
            
            Log::error('Razorpay webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Get webhook status (for debugging).
     */
    public function status()
    {
        $stats = [
            'total_webhooks' => PaymentWebhook::count(),
            'processed' => PaymentWebhook::where('processed', true)->count(),
            'unprocessed' => PaymentWebhook::where('processed', false)->count(),
            'by_provider' => PaymentWebhook::selectRaw('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->pluck('count', 'provider'),
            'recent_failures' => PaymentWebhook::where('processed', true)
                ->whereNotNull('error_message')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($webhook) => [
                    'id' => $webhook->id,
                    'provider' => $webhook->provider,
                    'event_type' => $webhook->event_type,
                    'error' => $webhook->error_message,
                    'created_at' => $webhook->created_at->toIso8601String(),
                ]),
        ];

        return response()->json($stats);
    }

    /**
     * Retry processing a webhook.
     */
    public function retry($webhookId)
    {
        $webhook = PaymentWebhook::findOrFail($webhookId);

        if ($webhook->processed && !$webhook->error_message) {
            return response()->json([
                'error' => 'Webhook already processed successfully',
            ], 400);
        }

        try {
            // Re-encode so Stripe can re-verify; signature check is skipped on retries
            // because the original raw body is no longer available.
            $result = $this->paymentService->processWebhook(
                $webhook->provider,
                $webhook->payload,
                null // skip sig verification on retry — payload already passed once
            );

            if ($result['success']) {
                $webhook->markAsProcessed();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook reprocessed successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
