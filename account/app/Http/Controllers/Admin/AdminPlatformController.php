<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPlatformController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'total_wallet_balance' => Wallet::sum('balance'),
            'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
            'total_revenue_mtd' => \App\Models\Transaction::where('type', 'credit')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        return view('admin.platform.index', compact('stats'));
    }

    public function users(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by email domain
        if ($request->filled('domain')) {
            $query->where('email', 'like', "%{$request->domain}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->with('wallet')->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.platform.users', compact('users'));
    }

    public function updateUserStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $user = User::findOrFail($id);
        $user->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function subscriptions()
    {
        $subscriptions = UserSubscription::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.platform.subscriptions', compact('subscriptions'));
    }

    public function cancelSubscription($id)
    {
        $sub = UserSubscription::findOrFail($id);
        $sub->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Subscription cancelled.');
    }

    public function wallets()
    {
        $wallets = Wallet::with('user')
            ->orderBy('balance', 'desc')
            ->paginate(50);

        return view('admin.platform.wallets', compact('wallets'));
    }

    public function adjustWalletBalance(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|between:-999999,999999',
            'reason' => 'required|string|max:500',
        ]);

        $amount = (float) $validated['amount'];

        if ($amount == 0) {
            return redirect()->back()->with('error', 'Adjustment amount cannot be zero.');
        }

        // Large adjustments (> ±10 000) are restricted to the super-admin.
        if (abs($amount) > 10000 && session('admin_user_id') !== 1) {
            Log::warning('Large wallet adjustment blocked — not super-admin', [
                'admin_user_id' => session('admin_user_id'),
                'wallet_id'     => $id,
                'amount'        => $amount,
            ]);

            return redirect()->back()->with('error', 'Adjustments exceeding ±10,000 require super-admin approval.');
        }

        $wallet = Wallet::findOrFail($id);
        $balanceBefore = (float) $wallet->balance;

        $wallet->increment('balance', $amount);

        \App\Models\Transaction::create([
            'user_id'     => $wallet->user_id,
            'description' => 'Admin adjustment by admin #' . session('admin_user_id') . ': ' . $validated['reason'],
            'amount'      => abs($amount),
            'type'        => $amount > 0 ? 'credit' : 'debit',
            'category'    => 'Admin Adjustment',
            'status'      => 'completed',
        ]);

        Log::warning('Wallet balance adjusted by admin', [
            'admin_user_id'  => session('admin_user_id'),
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $amount,
            'reason'         => $validated['reason'],
            'ip'             => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Wallet balance adjusted.');
    }
}
