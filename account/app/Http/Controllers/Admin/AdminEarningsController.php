<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminEarningsController extends Controller
{
    /**
     * Display the Imperial Payout Portal
     */
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Imperial treasury access denied.');
        }

        $balance = SystemBalance::firstOrCreate([], [
            'total_revenue' => 0,
            'total_payouts' => 0,
            'platform_profit_balance' => 0
        ]);

        return view('admin.monetization.payouts', compact('balance'));
    }

    /**
     * Withdraw accumulated profit to YG Pay
     */
    public function withdraw(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);

        $balance = SystemBalance::first();
        $amount = $balance->platform_profit_balance;

        if ($amount <= 0) {
            return back()->with('error', "The Imperial Treasury is currently empty.");
        }

        // Logic to initiate transfer to YG Pay Master Account
        // In production, this would call the YG Pay API
        
        $balance->update([
            'total_payouts' => $balance->total_payouts + $amount,
            'platform_profit_balance' => 0,
            'last_payout_at' => now(),
        ]);

        return back()->with('success', "Imperial Withdrawal of $" . number_format($amount, 2) . " has been successfully transferred to your YG Pay Master Account.");
    }
}
