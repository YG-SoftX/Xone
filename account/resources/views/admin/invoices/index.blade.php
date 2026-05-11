@extends('admin.master-layout')

@section('title', 'Invoice Management')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow">
        <p class="text-sm text-gray-600">Total Invoices</p>
        <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <p class="text-sm text-gray-600">Paid</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <p class="text-sm text-gray-600">Revenue</p>
        <p class="text-2xl font-bold text-blue-600">${{ number_format($stats['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
        <p class="text-sm text-gray-600">Pending</p>
        <p class="text-2xl font-bold text-orange-600">${{ number_format($stats['pending_amount'], 2) }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" placeholder="Search invoices..." value="{{ request('search') }}" class="flex-1 px-4 py-2 border rounded text-sm">
            <select name="status" class="px-4 py-2 border rounded text-sm">
                <option value="">All Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($invoices as $invoice)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-blue-600">
                            <a href="{{ route('admin.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->sender->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->recipient_name ?? $invoice->recipient_email }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">${{ number_format($invoice->total, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ match($invoice->status) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'sent' => 'bg-blue-100 text-blue-800',
                                    'overdue' => 'bg-red-100 text-red-800',
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->issue_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="text-blue-600 hover:underline">View</a>
                            @if($invoice->status !== 'paid')
                                <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline">Mark Paid</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-200">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
