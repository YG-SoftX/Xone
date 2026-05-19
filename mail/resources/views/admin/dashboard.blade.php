@extends('admin.layout')

@section('title', 'Dashboard')

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-red-600">{{ number_format($stats['total_users']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Total Users</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_emails']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Total Emails</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-emerald-600">{{ number_format($stats['inbox_count']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Inbox</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['sent_count']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Sent</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-orange-600">{{ number_format($stats['trash_count']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Trash</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-cyan-600">{{ number_format($stats['attachments_count']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Attachments</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold {{ $stats['failed_jobs'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($stats['failed_jobs']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Failed Jobs</div>
    </div>
    <div class="stat-card bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending_jobs']) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Pending Jobs</div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-users text-gray-400"></i> Recent Users
    </h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentUsers as $user)
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm text-gray-900">{{ $user->name }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="py-3 px-4 text-sm text-gray-500">{{ $user->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-8 text-center text-sm text-gray-400">No users yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-envelope text-gray-400"></i> Recent Emails
    </h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">From</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">To</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Folder</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentMails as $mail)
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm text-gray-900">{{ $mail->from }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $mail->to }}</td>
                        <td class="py-3 px-4 text-sm text-gray-700">{{ Str::limit($mail->subject, 40) }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $mail->folder === 'sent' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                {{ $mail->folder === 'trash' ? 'bg-red-50 text-red-700' : '' }}
                                {{ $mail->folder === 'inbox' ? 'bg-blue-50 text-blue-700' : '' }}
                                {{ $mail->folder === 'spam' ? 'bg-yellow-50 text-yellow-700' : '' }}
                                {{ !in_array($mail->folder, ['sent','trash','inbox','spam']) ? 'bg-gray-50 text-gray-700' : '' }}">
                                {{ ucfirst($mail->folder) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500">{{ $mail->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-gray-400">No emails yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-rocket text-gray-400"></i> Top Senders
    </h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sent Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topSenders as $sender)
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm text-gray-900">{{ $sender->from }}</td>
                        <td class="py-3 px-4 text-sm text-gray-700 font-semibold">{{ number_format($sender->count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-8 text-center text-sm text-gray-400">No sent emails yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
