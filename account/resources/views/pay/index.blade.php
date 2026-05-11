@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Pay Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">YG Pay</h1>
        <p class="text-sm text-gray-600">Digital Wallet & Payments</p>
    </div>

    <!-- Wallet Balance Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
            <p class="text-sm opacity-90">Available Balance</p>
            <p class="text-4xl font-bold mt-2">${{ number_format($wallet->balance, 2) }}</p>
            <p class="text-xs mt-2 opacity-75">{{ $wallet->wallet_number }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <button onclick="document.getElementById('sendModal').classList.remove('hidden')" 
                        class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                    💸 Send Money
                </button>
                <button onclick="document.getElementById('depositModal').classList.remove('hidden')" 
                        class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition-colors text-sm">
                    ➕ Add Money
                </button>
                <button onclick="document.getElementById('withdrawModal').classList.remove('hidden')" 
                        class="w-full bg-orange-600 text-white py-2 rounded-md hover:bg-orange-700 transition-colors text-sm">
                    🏦 Withdraw
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-900 mb-4">This Month</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-600">Sent</p>
                    <p class="text-lg font-semibold text-red-600">-${{ number_format($recentTransactions->where('type', 'transfer')->sum('amount'), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Received</p>
                    <p class="text-lg font-semibold text-green-600">+${{ number_format($recentTransactions->where('type', 'deposit')->sum('amount'), 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        </div>

        @if($recentTransactions->isNotEmpty())
            <div class="divide-y divide-gray-200">
                @foreach($recentTransactions as $transaction)
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    {{ $transaction->type === 'deposit' ? 'bg-green-100' : ($transaction->type === 'transfer' ? 'bg-red-100' : 'bg-blue-100') }}">
                                    @if($transaction->type === 'deposit')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    @elseif($transaction->type === 'transfer')
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900 capitalize">{{ $transaction->type }}</p>
                                    <p class="text-xs text-gray-500">{{ $transaction->description ?? 'No description' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold 
                                    {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->type === 'deposit' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No transactions yet</p>
            </div>
        @endif
    </div>
</div>

<!-- Send Money Modal -->
<div id="sendModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Send Money</h3>
        <form action="{{ route('pay.send') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Email</label>
                <input type="email" name="recipient_email" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount (USD)</label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('sendModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Send</button>
            </div>
        </form>
    </div>
</div>

<!-- Deposit Modal -->
<div id="depositModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Add Money to Wallet</h3>
        <form action="{{ route('pay.deposit') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount (USD)</label>
                <input type="number" name="amount" step="0.01" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="stripe">💳 Credit/Debit Card (Stripe)</option>
                    <option value="paypal">🅿️ PayPal</option>
                    <option value="razorpay">💰 Razorpay</option>
                </select>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Deposit</button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Withdraw Money</h3>
        <form action="{{ route('pay.withdraw') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount (USD)</label>
                <input type="number" name="amount" step="0.01" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Account</label>
                <input type="text" name="bank_account" required placeholder="Account number" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('withdrawModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Withdraw</button>
            </div>
        </form>
    </div>
</div>
@endsection
