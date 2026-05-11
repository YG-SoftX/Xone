@extends('admin.master-layout')

@section('title', 'Wallets')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">User Wallets</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Currency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($wallets as $wallet)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $wallet->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $wallet->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            ${{ number_format($wallet->balance, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $wallet->currency }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $wallet->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                onclick="openAdjustModal({{ $wallet->id }}, '{{ addslashes($wallet->user->name) }}', {{ $wallet->balance }}, '{{ $wallet->currency }}')"
                                class="px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium transition">
                                Adjust Balance
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-200">
        {{ $wallets->links() }}
    </div>
</div>

{{-- Adjust Balance Modal --}}
<div id="modal-adjust" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-indigo-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Adjust Wallet Balance</h3>
            <p id="modal-adjust-user" class="text-indigo-200 text-sm mt-0.5"></p>
        </div>

        <form id="form-adjust" method="POST" action="">
            @csrf
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500">Current Balance</span>
                    <span id="modal-adjust-current" class="font-bold text-gray-900 text-lg"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
                    <select name="type" id="adjust-type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="add">➕ Add Funds</option>
                        <option value="subtract">➖ Deduct Funds</option>
                        <option value="set">✏ Set Exact Balance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <div class="relative">
                        <span id="modal-currency" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium"></span>
                        <input type="number" name="amount" id="adjust-amount"
                            min="0" step="0.01" required
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Note</label>
                    <input type="text" name="note"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g. Refund, bonus, correction…">
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-adjust').classList.add('hidden')"
                    class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                    Apply Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAdjustModal(walletId, userName, balance, currency) {
    document.getElementById('modal-adjust-user').textContent = userName;
    document.getElementById('modal-adjust-current').textContent = currency + ' ' + parseFloat(balance).toFixed(2);
    document.getElementById('modal-currency').textContent = currency;
    document.getElementById('form-adjust').action = '/admin/platform/wallets/' + walletId + '/adjust';
    document.getElementById('adjust-amount').value = '';
    document.getElementById('modal-adjust').classList.remove('hidden');
}
</script>
@endpush
@endsection
