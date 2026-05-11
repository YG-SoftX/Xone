@extends('admin.master-layout')

@section('title', 'Support Tickets')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Open</p><p class="text-2xl font-bold text-blue-600">{{ $stats['open'] ?? 0 }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">In Progress</p><p class="text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] ?? 0 }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Resolved</p><p class="text-2xl font-bold text-green-600">{{ $stats['resolved'] ?? 0 }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Closed</p><p class="text-2xl font-bold text-gray-600">{{ $stats['closed'] ?? 0 }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Avg Response</p><p class="text-2xl font-bold">{{ $stats['avg_response'] ?? '—' }}</p></div>
</div>

<!-- Filters -->
<div class="bg-white rounded shadow p-4 mb-6">
    <form method="GET" class="flex gap-3 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tickets..." class="border rounded px-3 py-2 text-sm flex-1 min-w-[200px]">
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">All Status</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
        <select name="priority" class="border rounded px-3 py-2 text-sm">
            <option value="">All Priority</option>
            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
        </select>
        <select name="category" class="border rounded px-3 py-2 text-sm">
            <option value="">All Categories</option>
            <option value="billing">Billing</option>
            <option value="technical">Technical</option>
            <option value="account">Account</option>
            <option value="feature_request">Feature Request</option>
            <option value="bug">Bug Report</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Filter</button>
    </form>
</div>

<!-- Ticket List -->
<div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y">
        <thead class="bg-gray-50"><tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="bg-white divide-y">
            @foreach($tickets as $ticket)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-mono font-medium">{{ $ticket->ticket_number }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-xs truncate">{{ Str::limit($ticket->subject, 50) }}</td>
                <td class="px-6 py-4 text-sm">{{ $ticket->name ?? $ticket->email ?? 'Guest' }}</td>
                <td class="px-6 py-4 text-sm capitalize">{{ $ticket->service ?? '—' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-semibold {{ match($ticket->priority) {
                        'urgent' => 'bg-red-100 text-red-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'medium' => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">{{ ucfirst($ticket->priority) }}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-semibold {{ match($ticket->status) {
                        'open' => 'bg-blue-100 text-blue-800',
                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                        'resolved' => 'bg-green-100 text-green-800',
                        'closed' => 'bg-gray-100 text-gray-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $ticket->created_at->diffForHumans() }}</td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <a href="{{ route('admin.support.show', $ticket->id) }}" class="text-blue-600 hover:underline">View</a>
                    @if($ticket->status === 'open')
                    <form method="POST" action="{{ route('admin.support.resolve', $ticket->id) }}" class="inline"><button class="text-green-600 hover:underline">Resolve</button></form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $tickets->links() }}</div>
</div>
@endsection
