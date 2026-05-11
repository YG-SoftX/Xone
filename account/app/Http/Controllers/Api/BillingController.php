<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingAccount;
use App\Models\DeveloperInvoice;
use App\Models\ProjectBilling;
use App\Models\DeveloperProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Get billing account details
     */
    public function index()
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->first();

        if (!$billingAccount) {
            // Create default billing account
            $billingAccount = BillingAccount::create([
                'user_id' => $user->id,
                'billing_id' => BillingAccount::generateBillingId(),
                'status' => 'active',
                'current_balance' => 0,
                'total_spent' => 0,
            ]);
        }

        // Get linked projects
        $linkedProjects = ProjectBilling::where('billing_account_id', $billingAccount->id)
            ->with('project:id,name,project_id')
            ->get()
            ->map(function($link) {
                return [
                    'project_id' => $link->project->id,
                    'name' => $link->project->name,
                    'project_identifier' => $link->project->project_id,
                ];
            });

        return response()->json([
            'success' => true,
            'billing_account' => [
                'id' => $billingAccount->id,
                'billing_id' => $billingAccount->billing_id,
                'company_name' => $billingAccount->company_name,
                'tax_id' => $billingAccount->tax_id,
                'address' => $billingAccount->address,
                'city' => $billingAccount->city,
                'country' => $billingAccount->country,
                'postal_code' => $billingAccount->postal_code,
                'status' => $billingAccount->status,
                'current_balance' => number_format($billingAccount->current_balance, 2),
                'total_spent' => number_format($billingAccount->total_spent, 2),
                'payment_methods_count' => is_array($billingAccount->payment_methods) 
                    ? count($billingAccount->payment_methods) 
                    : 0,
                'linked_projects_count' => $linkedProjects->count(),
            ],
            'linked_projects' => $linkedProjects,
        ]);
    }

    /**
     * Update billing account information
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->first();

        if (!$billingAccount) {
            $billingAccount = BillingAccount::create([
                'user_id' => $user->id,
                'billing_id' => BillingAccount::generateBillingId(),
                'status' => 'active',
            ]);
        }

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $billingAccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Billing account updated successfully',
            'billing_account' => $billingAccount->fresh(),
        ]);
    }

    /**
     * List invoices
     */
    public function invoices(Request $request)
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->firstOrFail();

        $query = DeveloperInvoice::where('billing_account_id', $billingAccount->id);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'invoices' => $invoices->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'period_start' => $invoice->period_start->toDateString(),
                    'period_end' => $invoice->period_end->toDateString(),
                    'subtotal' => number_format($invoice->subtotal, 2),
                    'tax' => number_format($invoice->tax, 2),
                    'discount' => number_format($invoice->discount, 2),
                    'total' => number_format($invoice->total, 2),
                    'formatted_total' => $invoice->getFormattedTotal(),
                    'status' => $invoice->status,
                    'is_overdue' => $invoice->isOverdue(),
                    'paid_at' => $invoice->paid_at?->toIso8601String(),
                    'due_at' => $invoice->due_at?->toIso8601String(),
                    'created_at' => $invoice->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * Pay invoice (integrate with payment gateway)
     */
    public function payInvoice(Request $request, $invoiceId)
    {
        $user = Auth::user();
        
        $invoice = DeveloperInvoice::whereHas('billingAccount', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($invoiceId);

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice already paid'], 400);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        // In production, integrate with Stripe/PayPal/Razorpay here
        // For now, simulate successful payment
        
        $billingAccount = $invoice->billingAccount;
        
        // Process payment through gateway (placeholder)
        $paymentSuccess = true; // Simulate success

        if ($paymentSuccess) {
            $invoice->markAsPaid();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'invoice' => [
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $invoice->getFormattedTotal(),
                    'status' => $invoice->status,
                    'paid_at' => $invoice->paid_at->toIso8601String(),
                ],
            ]);
        } else {
            return response()->json([
                'error' => 'Payment processing failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Add payment method (integrate with payment gateway)
     */
    public function addPaymentMethod(Request $request)
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'type' => 'required|in:credit_card,bank_account,paypal',
            'token' => 'required|string', // Payment gateway token
            'last_four' => 'nullable|string|size:4',
            'brand' => 'nullable|string',
            'exp_month' => 'nullable|integer',
            'exp_year' => 'nullable|integer',
        ]);

        // In production, verify token with payment gateway
        // Store encrypted payment method info

        $paymentMethods = $billingAccount->payment_methods ?? [];
        $paymentMethods[] = [
            'id' => 'pm_' . \Illuminate\Support\Str::random(16),
            'type' => $validated['type'],
            'token' => $validated['token'], // Should be encrypted in production
            'last_four' => $validated['last_four'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'exp_month' => $validated['exp_month'] ?? null,
            'exp_year' => $validated['exp_year'] ?? null,
            'is_primary' => empty($paymentMethods), // First one is primary
            'added_at' => now()->toIso8601String(),
        ];

        $billingAccount->update([
            'payment_methods' => $paymentMethods,
            'primary_payment_method_id' => $paymentMethods[0]['id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully',
            'payment_method' => end($paymentMethods),
        ], 201);
    }

    /**
     * Remove payment method
     */
    public function removePaymentMethod($methodId)
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->firstOrFail();

        $paymentMethods = $billingAccount->payment_methods ?? [];
        $methodIndex = array_search($methodId, array_column($paymentMethods, 'id'));

        if ($methodIndex === false) {
            return response()->json(['error' => 'Payment method not found'], 404);
        }

        // Cannot remove primary method if it's the only one
        if (count($paymentMethods) === 1) {
            return response()->json([
                'error' => 'Cannot remove the only payment method. Add another first.',
            ], 400);
        }

        unset($paymentMethods[$methodIndex]);
        $paymentMethods = array_values($paymentMethods); // Re-index

        $billingAccount->update([
            'payment_methods' => $paymentMethods,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed successfully',
        ]);
    }

    /**
     * Link project to billing account
     */
    public function linkProject(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($projectId);
        $billingAccount = BillingAccount::where('user_id', $user->id)->firstOrFail();

        // Check if already linked
        $existingLink = ProjectBilling::where('project_id', $project->id)
            ->where('billing_account_id', $billingAccount->id)
            ->first();

        if ($existingLink) {
            return response()->json(['error' => 'Project already linked to this billing account'], 409);
        }

        ProjectBilling::create([
            'project_id' => $project->id,
            'billing_account_id' => $billingAccount->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project linked to billing account',
        ]);
    }

    /**
     * Get usage-based billing estimate
     */
    public function estimate()
    {
        $user = Auth::user();
        
        $billingAccount = BillingAccount::where('user_id', $user->id)->first();

        if (!$billingAccount) {
            return response()->json([
                'success' => true,
                'estimate' => [
                    'current_charges' => 0,
                    'projected_monthly' => 0,
                    'breakdown' => [],
                ],
            ]);
        }

        // Calculate current month usage charges
        $projects = DeveloperProject::where('owner_id', $user->id)->get();
        $totalCharges = 0;
        $breakdown = [];

        foreach ($projects as $project) {
            $projectCharges = 0;
            
            // Get active subscriptions
            $subscriptions = \App\Models\ProductSubscription::where('project_id', $project->id)
                ->where('is_active', true)
                ->with('product')
                ->get();

            foreach ($subscriptions as $subscription) {
                // Get current month usage
                $usage = \App\Models\ApiUsageLog::where('project_id', $project->id)
                    ->where('product_id', $subscription->product_id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();

                // Calculate overage charges
                $quota = \App\Models\ProjectQuota::where('project_id', $project->id)
                    ->where('product_id', $subscription->product_id)
                    ->first();

                if ($quota && $usage > $quota->monthly_limit) {
                    $overage = $usage - $quota->monthly_limit;
                    $overageCharge = ($overage / 1000) * $subscription->product->price_per_1000_calls;
                    $projectCharges += $overageCharge;
                }
            }

            if ($projectCharges > 0) {
                $breakdown[] = [
                    'project' => $project->name,
                    'charges' => number_format($projectCharges, 2),
                ];
                $totalCharges += $projectCharges;
            }
        }

        return response()->json([
            'success' => true,
            'estimate' => [
                'current_charges' => number_format($totalCharges, 2),
                'projected_monthly' => number_format($totalCharges, 2),
                'currency' => 'USD',
                'breakdown' => $breakdown,
                'note' => 'Estimate based on current month usage. Final invoice generated on 1st of next month.',
            ],
        ]);
    }
}
