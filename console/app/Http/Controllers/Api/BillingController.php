<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\BillingInvoice;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * List all invoices for a project
     */
    public function invoices(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $invoices = $project->invoices()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get invoice details
     */
    public function invoice(BillingInvoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice->project);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * List all subscriptions for a project
     */
    public function subscriptions(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $subscriptions = $project->subscriptions()->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Create new subscription
     */
    public function createSubscription(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'plan_id' => 'required|string|in:free,basic,pro,enterprise',
            'payment_method' => 'required|array',
            'payment_method.type' => 'required|string|in:card,paypal',
            'payment_method.token' => 'required_if:payment_method.type,card|string',
            'payment_method.email' => 'required_if:payment_method.type,paypal|email',
        ]);

        try {
            $subscription = $this->billingService->createSubscription(
                $project,
                $validated['plan_id'],
                $validated['payment_method']
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => $subscription,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, Request $request): JsonResponse
    {
        $this->authorize('update', $subscription->project);

        $immediate = $request->input('immediate', false);

        try {
            $subscription = $this->billingService->cancelSubscription($subscription, $immediate);

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'data' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate invoice manually
     */
    public function generateInvoice(Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        try {
            $invoice = $this->billingService->generateInvoice($project);

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing statistics
     */
    public function stats(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $stats = [
            'total_spend' => $project->invoices()->where('status', 'paid')->sum('amount'),
            'current_subscription' => $project->activeSubscription,
            'invoices_count' => $project->invoices()->count(),
            'pending_invoices' => $project->invoices()->where('status', 'pending')->count(),
            'next_billing_date' => $project->activeSubscription?->current_period_end,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
