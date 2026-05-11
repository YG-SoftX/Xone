@extends('admin.layout')

@section('title', 'Queue Manager')

<div class="stats">
    <div class="stat-card">
        <div class="value" style="color:{{ $pendingCount > 0 ? '#fbbf24' : '#34d399' }}">{{ number_format($pendingCount) }}</div>
        <div class="label">Pending Jobs</div>
    </div>
    <div class="stat-card">
        <div class="value" style="color:{{ $failedCount > 0 ? '#f87171' : '#34d399' }}">{{ number_format($failedCount) }}</div>
        <div class="label">Failed Jobs</div>
    </div>
</div>

@if($failedCount > 0)
<div style="margin-bottom:24px;display:flex;gap:12px;">
    <form method="POST" action="{{ route('admin.queue.retry-all') }}">
        @csrf
        <button class="btn">Retry All Failed Jobs</button>
    </form>
    <form method="POST" action="{{ route('admin.queue.prune') }}" onsubmit="return confirm('Delete failed jobs older than 7 days?')">
        @csrf
        <button class="btn btn-danger">Prune Old Failed Jobs</button>
    </form>
</div>
@endif

<h2 style="font-size:18px;margin-bottom:16px;">Failed Jobs</h2>

@if($failedJobs->isEmpty())
    <div class="alert alert-success">No failed jobs in the queue.</div>
@else
    <table>
        <thead>
            <tr><th>ID</th><th>Job</th><th>Error</th><th>Failed At</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($failedJobs as $job)
                <tr>
                    <td>{{ $job->id }}</td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ json_decode($job->payload)->displayName ?? 'Unknown' }}
                    </td>
                    <td style="max-width:300px;color:#f87171;font-size:12px;">
                        {{ Str::limit($job->exception, 100) }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.queue.destroy', $job->id) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div style="margin-top:32px;background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;">
    <h3 style="font-size:16px;margin-bottom:12px;">Queue Commands</h3>
    <div style="font-size:13px;color:#94a3b8;line-height:2;">
        <code>php artisan queue:work</code> — Start processing queued emails<br>
        <code>php artisan queue:listen</code> — Monitor queue (dev mode)<br>
        <code>php artisan queue:failed</code> — List all failed jobs<br>
        <code>php artisan queue:retry all</code> — Retry all failed jobs<br>
        <code>php artisan queue:flush</code> — Clear all failed jobs<br>
        <code>php artisan queue:prune-failed --hours=168</code> — Prune jobs older than 7 days
    </div>
</div>
