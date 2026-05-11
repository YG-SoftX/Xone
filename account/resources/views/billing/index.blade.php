@extends('layouts.dashboard')
@section('title', 'Payments & subscriptions')

@section('dashboard-content')
<div class="max-w-5xl mx-auto px-6 py-10">
    
    <!-- Header -->
    <div class="mb-10">
        <h1 class="text-[28px] font-normal text-[#202124] mb-2">Payments & subscriptions</h1>
        <p class="text-sm text-[#5f6368]">Manage your payment methods, recurring payments, and transaction history across the YG empire.</p>
    </div>

    <!-- Top Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
        
        <!-- Balance Card -->
        <div class="google-card md:col-span-2">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-lg font-normal text-[#202124] mb-1">Current Balance</h3>
                    <div class="text-4xl font-bold text-gray-900">${{ number_format($totalDue, 2) }}</div>
                    <p class="text-xs text-green-600 font-bold uppercase tracking-widest mt-2">Paid through May 15, 2026</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="fas fa-credit-card text-blue-600"></i>
                </div>
            </div>
            <div class="flex gap-4">
                <button class="bg-[#1a73e8] text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-blue-700 shadow-md">Pay now</button>
                <button class="text-[#1a73e8] px-6 py-2 rounded-full text-sm font-medium hover:bg-blue-50">View details</button>
            </div>
        </div>

        <!-- Payment Method Card -->
        <div class="google-card">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">Primary Method</h3>
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded flex items-center justify-center text-white font-bold text-[10px]">
                    YG PAY
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">YG Wallet (Stones)</div>
                    <div class="text-xs text-gray-500">Auto-pay enabled</div>
                </div>
            </div>
            <a href="#" class="text-[#1a73e8] text-xs font-medium hover:underline">Manage payment methods</a>
        </div>
    </div>

    <!-- Usage Breakdown -->
    <div class="google-card mb-10">
        <h3 class="text-lg font-normal text-[#202124] mb-8">Ecosystem Usage Breakdown</h3>
        <div class="space-y-8">
            @foreach($usageData as $item)
            <div>
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ $item['service'] }}</span>
                        <span class="text-xs text-gray-500 ml-2">({{ $item['usage'] }})</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">${{ number_format($item['amount'], 2) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="{{ $item['color'] }} h-1.5 rounded-full" style="width: {{ ($item['amount'] / $totalDue) * 100 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-8 pt-8 border-t border-gray-100 flex justify-between items-center">
            <div class="text-sm text-gray-500">Estimated total for this period</div>
            <div class="text-xl font-bold text-gray-900">${{ number_format($totalDue, 2) }}</div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('billing.invoices') }}" class="google-card hover:bg-gray-50 transition-colors flex items-center gap-4">
            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900">Invoices & receipts</div>
                <div class="text-xs text-gray-500">View and download past transaction history</div>
            </div>
            <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
        </a>
        <a href="#" class="google-card hover:bg-gray-50 transition-colors flex items-center gap-4">
            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                <i class="fas fa-history"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900">Subscription settings</div>
                <div class="text-xs text-gray-500">Manage your node access and storage tiers</div>
            </div>
            <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
        </a>
    </div>

</div>
@endsection
