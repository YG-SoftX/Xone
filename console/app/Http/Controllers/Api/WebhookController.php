<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Receive YG Pay webhooks
     */
    public function ygPayWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('services.yg_pay.webhook_secret');

        if (!$signature || !$this->verifySignature($request->getContent(), $signature, $secret)) {
            Log::warning('Invalid webhook signature received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();

        try {
            $this->billingService->handleWebhook($payload);
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('YG Pay webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Verify HMAC signature
     */
    protected function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
