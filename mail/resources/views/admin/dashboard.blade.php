@extends('admin.layout')

@section('title', 'Dashboard')

<div class="stats">
    <div class="stat-card">
        <div class="value">{{ number_format($stats['total_users']) }}</div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['total_emails']) }}</div>
        <div class="label">Total Emails</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['inbox_count']) }}</div>
        <div class="label">Inbox</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['sent_count']) }}</div>
        <div class="label">Sent</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['trash_count']) }}</div>
        <div class="label">Trash</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['attachments_count']) }}</div>
        <div class="label">Attachments</div>
    </div>
    <div class="stat-card">
        <div class="value" style="color: {{ $stats['failed_jobs'] > 0 ? '#f87171' : '#34d399' }}">{{ number_format($stats['failed_jobs']) }}</div>
        <div class="label">Failed Jobs</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['pending_jobs']) }}</div>
        <div class="label">Pending Jobs</div>
    </div>
</div>

<h2 style="font-size:18px;margin-bottom:16px;">Recent Users</h2>
<table>
    <thead><tr><th>Name</th><th>Email</th><th>Created</th></tr></thead>
    <tbody>
        @forelse($recentUsers as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->created_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="3" style="color:#64748b">No users yet</td></tr>
        @endforelse
    </tbody>
</table>

<h2 style="font-size:18px;margin:24px 0 16px;">Recent Emails</h2>
<table>
    <thead><tr><th>From</th><th>To</th><th>Subject</th><th>Folder</th><th>Date</th></tr></thead>
    <tbody>
        @forelse($recentMails as $mail)
            <tr>
                <td>{{ $mail->from }}</td>
                <td>{{ $mail->to }}</td>
                <td>{{ Str::limit($mail->subject, 40) }}</td>
                <td>
                    <span class="badge {{ $mail->folder === 'sent' ? 'badge-success' : ($mail->folder === 'trash' ? 'badge-danger' : 'badge-warning') }}">
                        {{ ucfirst($mail->folder) }}
                    </span>
                </td>
                <td>{{ $mail->created_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#64748b">No emails yet</td></tr>
        @endforelse
    </tbody>
</table>

<h2 style="font-size:18px;margin:24px 0 16px;">Top Senders</h2>
<table>
    <thead><tr><th>Email</th><th>Sent Count</th></tr></thead>
    <tbody>
        @forelse($topSenders as $sender)
            <tr>
                <td>{{ $sender->from }}</td>
                <td>{{ number_format($sender->count) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" style="color:#64748b">No sent emails yet</td></tr>
        @endforelse
    </tbody>
</table>
