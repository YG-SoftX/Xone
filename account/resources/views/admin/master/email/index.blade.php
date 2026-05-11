@extends('admin.master-layout')
@section('title', 'Email Management')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Total Emails</p><p class="text-2xl font-bold">{{ $stats['total_emails'] ?? '—' }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Sent Today</p><p class="text-2xl font-bold text-blue-600">{{ $stats['sent_today'] ?? '—' }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Failed Jobs</p><p class="text-2xl font-bold text-red-600">{{ $stats['failed_jobs'] ?? '—' }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Users</p><p class="text-2xl font-bold">{{ $stats['total_users'] ?? '—' }}</p></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded shadow p-6">
        <h3 class="font-semibold mb-4">Top Senders</h3>
        @foreach($topSenders ?? [] as $sender)
        <div class="flex justify-between py-2 border-b border-gray-100 last:border-0">
            <span class="text-sm">{{ $sender['email'] ?? 'Unknown' }}</span>
            <span class="text-sm font-bold">{{ $sender['count'] ?? 0 }}</span>
        </div>
        @endforeach
    </div>
    <div class="bg-white rounded shadow p-6">
        <h3 class="font-semibold mb-4">Recent Emails</h3>
        @foreach($recentEmails ?? [] as $email)
        <div class="flex justify-between py-2 border-b border-gray-100 last:border-0">
            <div><p class="text-sm font-medium">{{ $email['subject'] ?? 'No Subject' }}</p><p class="text-xs text-gray-500">{{ $email['from'] ?? '' }} → {{ $email['to'] ?? '' }}</p></div>
            <span class="text-xs text-gray-500">{{ $email['created_at'] ?? '' }}</span>
        </div>
        @endforeach
    </div>
</div>
<div class="mt-6 bg-white rounded shadow p-6">
    <h3 class="font-semibold mb-4">Mail Server Configuration</h3>
    <div class="grid grid-cols-2 gap-4">
        <div><p class="text-sm text-gray-600">SMTP Host</p><p class="font-medium">{{ $config['smtp_host'] ?? '—' }}</p></div>
        <div><p class="text-sm text-gray-600">SMTP Port</p><p class="font-medium">{{ $config['smtp_port'] ?? '—' }}</p></div>
        <div><p class="text-sm text-gray-600">IMAP Host</p><p class="font-medium">{{ $config['imap_host'] ?? '—' }}</p></div>
        <div><p class="text-sm text-gray-600">IMAP Extension</p><p class="font-medium {{ $config['imap_extension'] ? 'text-green-600' : 'text-red-600' }}">{{ $config['imap_extension'] ? 'Installed' : 'Missing' }}</p></div>
    </div>
</div>
@endsection
