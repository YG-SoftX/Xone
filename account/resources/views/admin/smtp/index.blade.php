@extends('admin.master-layout')
@section('title', 'SMTP Accounts')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Total Accounts</p><p class="text-2xl font-bold">{{ $stats['total'] }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Active</p><p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Verified</p><p class="text-2xl font-bold text-blue-600">{{ $stats['verified'] }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Emails Today</p><p class="text-2xl font-bold">{{ $stats['total_emails_today'] }}</p></div>
</div>
<div class="bg-white rounded shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">SMTP Accounts</h3>
        <a href="{{ route('admin.smtp.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Create Account</a>
    </div>
    <table class="min-w-full divide-y">
        <thead class="bg-gray-50"><tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent Today</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="bg-white divide-y">
            @foreach($accounts as $acc)
            <tr>
                <td class="px-6 py-4 text-sm">{{ $acc->email_address }}</td>
                <td class="px-6 py-4 text-sm">{{ $acc->domain }}</td>
                <td class="px-6 py-4 text-sm">{{ $acc->user->name }}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full {{ $acc->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $acc->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-6 py-4 text-sm">{{ $acc->emails_sent_today }}/{{ $acc->daily_limit }}</td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <a href="{{ route('admin.smtp.show', $acc->id) }}" class="text-blue-600">View</a>
                    <form method="POST" action="{{ route('admin.smtp.toggle', $acc->id) }}" class="inline"><button class="text-green-600">Toggle</button></form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $accounts->links() }}</div>
</div>
@endsection
