<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PlatformFeature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sent = Invoice::where('sender_id', $user->id)
            ->orderBy('created_at', 'desc')->limit(50)->get();
        $received = Invoice::where('recipient_id', $user->id)
            ->orWhere('recipient_email', $user->email)
            ->orderBy('created_at', 'desc')->limit(50)->get();

        return Inertia::render('Invoices', [
            'sent' => $sent->map(fn($i) => $this->formatInvoice($i)),
            'received' => $received->map(fn($i) => $this->formatInvoice($i)),
        ]);
    }

    public function create()
    {
        return Inertia::render('InvoiceCreate');
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email',
            'recipient_name' => 'nullable|string',
            'description' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'due_date' => 'required|date|after:today',
        ]);

        $lineItems = $request->line_items;
        $subtotal = collect($lineItems)->sum(fn($item) => $item['quantity'] * $item['price']);
        $taxRate = $request->tax_rate ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $discount = $request->discount ?? 0;
        $total = $subtotal + $taxAmount - $discount;

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateNumber(),
            'sender_id' => $request->user()->id,
            'recipient_email' => $request->recipient_email,
            'recipient_name' => $request->recipient_name,
            'description' => $request->description,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount' => $discount,
            'total' => $total,
            'status' => 'draft',
            'issue_date' => today(),
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'line_items' => $lineItems,
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice created.');
    }

    public function show($id)
    {
        $invoice = Invoice::with(['sender', 'recipient'])->findOrFail($id);
        
        // Mark as viewed
        $invoice->markAsViewed();

        return Inertia::render('InvoiceShow', [
            'invoice' => $this->formatInvoice($invoice),
            'canPay' => $invoice->status !== 'paid' && $invoice->recipient_email === auth()->user()->email,
        ]);
    }

    public function pay(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice already paid.');
        }

        $user = $request->user();
        $wallet = \App\Models\Wallet::forUser($user->id);

        if ($wallet->balance < $invoice->total) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        DB::transaction(function () use ($invoice, $wallet, $user) {
            $wallet->decrement('balance', $invoice->total);
            $invoice->markAsPaid('wallet');

            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'description' => 'Paid invoice ' . $invoice->invoice_number,
                'amount' => $invoice->total,
                'type' => 'debit',
                'category' => 'Invoice',
                'status' => 'completed',
            ]);
        });

        return redirect()->back()->with('success', 'Invoice paid!');
    }

    public function send(Request $request, $id)
    {
        $invoice = Invoice::where('sender_id', $request->user()->id)->findOrFail($id);
        $invoice->markAsSent();

        return redirect()->back()->with('success', 'Invoice sent.');
    }

    public function cancel(Request $request, $id)
    {
        $invoice = Invoice::where('sender_id', $request->user()->id)->findOrFail($id);
        $invoice->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Invoice cancelled.');
    }

    protected function formatInvoice($invoice)
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'sender_name' => $invoice->sender->name,
            'sender_email' => $invoice->sender->email,
            'recipient_name' => $invoice->recipient_name,
            'recipient_email' => $invoice->recipient_email,
            'description' => $invoice->description,
            'subtotal' => (float) $invoice->subtotal,
            'tax_rate' => (float) $invoice->tax_rate,
            'tax_amount' => (float) $invoice->tax_amount,
            'discount' => (float) $invoice->discount,
            'total' => (float) $invoice->total,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
            'issue_date' => $invoice->issue_date?->format('d M Y'),
            'due_date' => $invoice->due_date?->format('d M Y'),
            'paid_at' => $invoice->paid_at?->format('d M Y'),
            'notes' => $invoice->notes,
            'line_items' => $invoice->line_items ?? [],
        ];
    }
}
