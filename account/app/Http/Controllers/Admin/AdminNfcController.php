<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NfcToken;
use App\Models\NfcTransaction;
use Illuminate\Http\Request;

class AdminNfcController extends Controller
{
    public function tokens(Request $request)
    {
        $query = NfcToken::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('token_uid', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $tokens = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total' => NfcToken::count(),
            'active' => NfcToken::where('is_active', true)->count(),
            'total_transactions' => NfcTransaction::count(),
            'total_volume' => NfcTransaction::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.nfc.tokens', compact('tokens', 'stats'));
    }

    public function toggleToken($id)
    {
        $token = NfcToken::findOrFail($id);
        $token->update(['is_active' => !$token->is_active]);

        return redirect()->back()->with('success', 'NFC token ' . ($token->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function transactions(Request $request)
    {
        $query = NfcTransaction::with(['user', 'nfcToken']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('merchant', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.nfc.transactions', compact('transactions'));
    }

    public function showTransaction($id)
    {
        $transaction = NfcTransaction::with(['user', 'nfcToken'])->findOrFail($id);
        return view('admin.nfc.transaction_show', compact('transaction'));
    }
}
