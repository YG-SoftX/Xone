<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'line_items',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'line_items' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
