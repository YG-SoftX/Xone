<?php

namespace App\Jobs;

use App\Models\BillingAccount;
use App\Models\DeveloperInvoice;
use App\Models\ProjectQuota;
use App\Models\ApiProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    /**
     * Execute the job - generate monthly invoices for all billing accounts
     */
    public function handle(): void
    {
        \Log::info('Starting monthly invoice generation job');

        try {
            // Get previous month's date range
            $periodStart = now()->subMonth()->startOfMonth();
            $periodEnd = now()->subMonth()->endOfMonth();

            // Get all active billing accounts
            $billingAccounts = BillingAccount::where('status', 'active')->get();

            foreach ($billingAccounts as $account) {
                $this->generateInvoiceForAccount($account, $periodStart, $periodEnd);
            }

            \Log::info('Monthly invoice generation completed successfully', [
                'accounts_processed' => $billingAccounts->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Monthly invoice generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('GenerateInvoices job permanently failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Generate invoice for a specific billing account
     */
    protected function generateInvoiceForAccount(BillingAccount $account, $periodStart, $periodEnd): void
    {
        // Get all projects linked to this billing account, eager-load subscriptions+quotas
        $linkedProjects = \App\Models\ProjectBilling::where('billing_account_id', $account->id)
            ->with(['project.subscriptions' => fn($q) => $q->where('is_active', true)->with('product'),
                    'project.quotas'])
            ->get();

        if ($linkedProjects->isEmpty()) {
            return; // No projects to bill
        }

        // Calculate charges for each project
        $lineItems = [];
        $subtotal = 0;

        foreach ($linkedProjects as $link) {
            $projectCharges = $this->calculateProjectCharges($link->project, $periodStart, $periodEnd);
            
            if ($projectCharges > 0) {
                $lineItems[] = [
                    'project_id' => $link->project->id,
                    'project_name' => $link->project->name,
                    'description' => "API usage for {$link->project->name}",
                    'amount' => $projectCharges,
                ];
                $subtotal += $projectCharges;
            }
        }

        if (empty($lineItems)) {
            return; // No charges to invoice
        }

        // Calculate tax (example: 10% - adjust based on your requirements)
        $taxRate = 0.10;
        $tax = round($subtotal * $taxRate, 2);

        // Apply any discounts
        $discount = 0; // Could be based on volume, promotions, etc.

        $total = $subtotal + $tax - $discount;

        // Create invoice record
        $invoice = DeveloperInvoice::create([
            'billing_account_id' => $account->id,
            'invoice_number' => DeveloperInvoice::generateInvoiceNumber(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => 'pending',
            'line_items' => $lineItems,
            'due_at' => now()->addDays(30), // 30 days payment term
        ]);

        // Update billing account balance
        $account->addCharge($total);

        \Log::info('Invoice generated', [
            'invoice_number' => $invoice->invoice_number,
            'billing_account_id' => $account->id,
            'total' => $total,
            'line_items_count' => count($lineItems),
        ]);

        // In production, send email notification here
        // Mail::to($account->user->email)->send(new InvoiceGenerated($invoice));
    }

    /**
     * Calculate charges for a project based on API usage overages
     */
    protected function calculateProjectCharges($project, $periodStart, $periodEnd): float
    {
        $totalCharges = 0;

        // Get all active subscriptions for this project
        $subscriptions = \App\Models\ProductSubscription::where('project_id', $project->id)
            ->where('is_active', true)
            ->with('product')
            ->get();

        foreach ($subscriptions as $subscription) {
            // Get total usage for the period
            $usage = \App\Models\ApiUsageLog::where('project_id', $project->id)
                ->where('product_id', $subscription->product_id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();

            // Get quota limits
            $quota = ProjectQuota::where('project_id', $project->id)
                ->where('product_id', $subscription->product_id)
                ->first();

            if ($quota && $usage > $quota->monthly_limit) {
                // Calculate overage
                $overage = $usage - $quota->monthly_limit;
                
                // Get product pricing
                $product = ApiProduct::find($subscription->product_id);
                $overageCharge = ($overage / 1000) * $product->price_per_1000_calls;
                
                $totalCharges += $overageCharge;
            }
        }

        return round($totalCharges, 2);
    }
}
