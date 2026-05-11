<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'project_id',
        'invoice_number',
        'period_start',
        'period_end',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'line_items',
        'paid_at',
        'due_at',
    ];

    protected $casts = [
        'line_items' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    /**
     * Get billing account
     */
    public function billingAccount()
    {
        return $this->belongsTo(BillingAccount::class);
    }

    /**
     * Get project (if invoice is project-specific)
     */
    public function project()
    {
        return $this->belongsTo(DeveloperProject::class);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber()
    {
        return 'INV-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Reduce billing account balance
        $this->billingAccount->processPayment($this->total);
    }

    /**
     * Check if overdue
     */
    public function isOverdue()
    {
        return $this->status === 'pending' && 
               $this->due_at && 
               $this->due_at->isPast();
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotal()
    {
        return '$' . number_format((float) $this->total, 2);
    }
}
