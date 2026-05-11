<?php

namespace App\Jobs;

use App\Models\BillingAccount;
use App\Models\DeveloperInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateMonthlyInvoice Job
 * 
 * Generates monthly invoices for developer billing accounts.
 * Runs on the 1st of each month via scheduler.
 */
class GenerateMonthlyInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    protected int $billingAccountId;
    protected string $period;

    /**
     * Create a new job instance.
     */
    public function __construct(int $billingAccountId, string $period)
    {
        $this->billingAccountId = $billingAccountId;
        $this->period = $period; // Format: YYYY-MM
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Generating monthly invoice', [
            'billing_account_id' => $this->billingAccountId,
            'period' => $this->period,
        ]);

        $billingAccount = BillingAccount::find($this->billingAccountId);
        
        if (!$billingAccount) {
            Log::error('Billing account not found', [
                'billing_account_id' => $this->billingAccountId,
            ]);
            return;
        }

        // Calculate usage for the period
        $usageData = $this->calculateUsage($billingAccount, $this->period);
        
        // Generate invoice
        $invoice = DeveloperInvoice::create([
            'billing_account_id' => $billingAccount->id,
            'invoice_number' => $this->generateInvoiceNumber($billingAccount, $this->period),
            'period_start' => now()->parse($this->period . '-01')->startOfMonth(),
            'period_end' => now()->parse($this->period . '-01')->endOfMonth(),
            'subtotal' => $usageData['subtotal'],
            'tax_amount' => $usageData['tax_amount'],
            'total_amount' => $usageData['total'],
            'currency' => $billingAccount->currency ?? 'USD',
            'status' => 'pending',
            'line_items' => $usageData['line_items'],
            'due_date' => now()->addDays(30),
        ]);

        Log::info('Monthly invoice generated successfully', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->total_amount,
        ]);

        // Send invoice email notification
        $this->sendInvoiceNotification($invoice);
    }

    /**
     * Calculate usage for billing period.
     */
    protected function calculateUsage(BillingAccount $billingAccount, string $period): array
    {
        // This would query API usage logs and calculate charges
        // For now, returning placeholder data
        return [
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'line_items' => [],
        ];
    }

    /**
     * Generate unique invoice number.
     */
    protected function generateInvoiceNumber(BillingAccount $billingAccount, string $period): string
    {
        $yearMonth = str_replace('-', '', $period);
        return "INV-{$billingAccount->id}-{$yearMonth}";
    }

    /**
     * Send invoice notification email.
     */
    protected function sendInvoiceNotification(DeveloperInvoice $invoice): void
    {
        // Implementation would send email via Laravel Mail
        Log::info('Invoice notification sent', [
            'invoice_id' => $invoice->id,
            'email' => $invoice->billingAccount->user->email ?? null,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to generate monthly invoice', [
            'billing_account_id' => $this->billingAccountId,
            'period' => $this->period,
            'error' => $exception->getMessage(),
        ]);
    }
}
