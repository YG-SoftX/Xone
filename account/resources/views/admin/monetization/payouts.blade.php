@extends('layouts.dashboard')
@section('title', 'Imperial Payout Portal')

@section('dashboard-content')
<div class="max-w-6xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-brand/5 rounded-2xl flex items-center justify-center shadow-sm">
                <i class="fas fa-crown text-brand text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Imperial Payout Portal</h1>
                <p class="text-[14px] text-gray-500">Manage your ecosystem platform profit and master withdrawals</p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl border border-gray-100 shadow-sm">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Master Node:</span>
            <span class="text-[10px] font-black text-brand uppercase tracking-widest">YGX-CORE-01</span>
        </div>
    </div>

    <!-- Payout Commander -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div class="col-span-1 md:col-span-2">
            <div class="google-card h-full flex flex-col justify-between border-brand/20 bg-brand/5">
                <div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-4">Available for Withdrawal</h3>
                    <div class="text-6xl font-normal text-gray-900 mb-2">${{ number_format($balance->platform_profit_balance, 2) }}</div>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">
                        This balance represents your accumulated **32% Platform Commission** from all YG Ads and AdSense activity across the empire.
                    </p>
                </div>
                
                <div class="flex items-center gap-6">
                    <form action="{{ route('admin.monetization.payout.withdraw') }}" method="POST">
                        @csrf
                        <button class="bg-brand text-white px-10 py-4 rounded-full text-sm font-bold shadow-xl hover:bg-opacity-90 transition-all flex items-center gap-3">
                            <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-5 h-5 brightness-0 invert" alt="">
                            Withdraw to YG Pay
                        </button>
                    </form>
                    <div class="text-xs text-gray-400 font-medium italic">
                        * Instant transfer to Master Account
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Lifetime Extraction</div>
                <div class="text-2xl font-normal text-gray-900">${{ number_format($balance->total_payouts, 2) }}</div>
                <div class="mt-2 text-[10px] text-green-600 font-bold flex items-center gap-1">
                    <i class="fas fa-check-circle"></i> Fully Audited
                </div>
            </div>
            <div class="google-card !bg-white">
                <div class="text-[10px] uppercase font-bold text-gray-400 mb-2 tracking-widest">Last Payout Date</div>
                <div class="text-lg font-bold text-gray-900">{{ $balance->last_payout_at ? $balance->last_payout_at->format('M d, Y') : 'No payouts yet' }}</div>
                <div class="mt-2 text-[10px] text-gray-400 font-medium">via YG Pay Protocol</div>
            </div>
        </div>
    </div>

    <!-- Economic Genealogy -->
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-6">Revenue Genealogy (Recent Earnings)</h3>
    <div class="google-card !p-0 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                    <th class="px-6 py-4">Source Node</th>
                    <th class="px-6 py-4">Event Type</th>
                    <th class="px-6 py-4">Gross Amount</th>
                    <th class="px-6 py-4 text-right">Your Profit (32%)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-5">
                        <p class="text-sm font-bold text-gray-900">Sovereign Logistics</p>
                        <p class="text-[10px] text-gray-400 uppercase">Node 05</p>
                    </td>
                    <td class="px-6 py-5">
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest rounded-full">AdSense Click</span>
                    </td>
                    <td class="px-6 py-5 text-sm text-gray-500">$2.50</td>
                    <td class="px-6 py-5 text-right font-bold text-gray-900 text-sm">$0.80</td>
                </tr>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-5">
                        <p class="text-sm font-bold text-gray-900">Imperial Core</p>
                        <p class="text-[10px] text-gray-400 uppercase">Node 01</p>
                    </td>
                    <td class="px-6 py-5">
                        <span class="px-3 py-1 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest rounded-full">Internal Ad View</span>
                    </td>
                    <td class="px-6 py-5 text-sm text-gray-500">$1.00</td>
                    <td class="px-6 py-5 text-right font-bold text-gray-900 text-sm">$0.32</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
