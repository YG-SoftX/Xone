<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Display the Billing Hub Overview
     */
    public function index()
    {
        // For the high-fidelity walk-through, we use structured usage data
        $usageData = [
            ['service' => 'Drive Storage', 'amount' => 45.50, 'usage' => '1.2 TB', 'color' => 'bg-blue-500'],
            ['service' => 'Mail Nodes', 'amount' => 12.00, 'usage' => '12 Users', 'color' => 'bg-red-500'],
            ['service' => 'YG Pay Processing', 'amount' => 8.75, 'usage' => '450 Tx', 'color' => 'bg-purple-500'],
            ['service' => 'AI Guardian', 'amount' => 15.00, 'usage' => 'Active', 'color' => 'bg-indigo-500'],
        ];

        $totalDue = array_sum(array_column($usageData, 'amount'));

        return view('billing.index', compact('usageData', 'totalDue'));
    }

    /**
     * Display Invoices
     */
    public function invoices()
    {
        return view('billing.invoices');
    }

    /**
     * Update Payment Method (YG Pay Sync)
     */
    public function updatePaymentMethod(Request $request)
    {
        // Logic to sync with YG Pay wallet
        return back()->with('success', 'Primary payment method updated to YG Pay (Stones).');
    }
}
