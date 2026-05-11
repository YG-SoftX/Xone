<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['sender', 'recipient']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('recipient_email', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total' => Invoice::count(),
            'draft' => Invoice::where('status', 'draft')->count(),
            'sent' => Invoice::where('status', 'sent')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
            'pending_amount' => Invoice::whereIn('status', ['sent', 'viewed'])->sum('total'),
        ];

        return view('admin.invoices.index', compact('invoices', 'stats'));
    }

    public function show($id)
    {
        $invoice = Invoice::with(['sender', 'recipient'])->findOrFail($id);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,viewed,paid,overdue,cancelled,refunded',
        ]);

        $invoice = Invoice::findOrFail($id);
        $invoice->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Invoice status updated.');
    }

    public function markAsPaid($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->markAsPaid('admin_adjustment');

        return redirect()->back()->with('success', 'Invoice marked as paid.');
    }

    public function resend($id)
    {
        $invoice = Invoice::findOrFail($id);
        
        // In production, this would trigger an email send
        $invoice->markAsSent();

        return redirect()->back()->with('success', 'Invoice resent.');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted.');
    }
}
