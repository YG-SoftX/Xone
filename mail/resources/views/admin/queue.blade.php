@extends('admin.layout')

@section('title', 'Queue Manager')

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold {{ $pendingCount > 0 ? 'text-yellow-600' : 'text-emerald-600' }}">{{ number_format($pendingCount) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Pending Jobs</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="text-2xl font-bold {{ $failedCount > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($failedCount) }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Failed Jobs</div>
    </div>
</div>

@if($failedCount > 0)
<div class="flex items-center gap-3 mb-6">
    <form method="POST" action="{{ route('admin.queue.retry-all') }}">
        @csrf
        <button type="submit" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
            <i class="fas fa-redo text-gray-400"></i> Retry All Failed Jobs
        </button>
    </form>
    <form method="POST" action="{{ route('admin.queue.prune') }}" onsubmit="return confirm('Delete failed jobs older than 7 days?')">
        @csrf
        <button type="submit" class="px-4 py-2 bg-white border border-red-200 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition flex items-center gap-2">
            <i class="fas fa-trash"></i> Prune Old Failed Jobs
        </button>
    </form>
</div>
@endif

<div class="bg-white border border-gray-200 rounded-xl p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-exclamation-triangle text-gray-400"></i> Failed Jobs
    </h2>

    @if($failedJobs->isEmpty())
        <div class="px-4 py-6 text-center text-sm text-emerald-600 bg-emerald-50 rounded-xl">
            <i class="fas fa-check-circle mr-1.5"></i> No failed jobs in the queue.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Job</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Error</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Failed At</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedJobs as $job)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="py-3 px-4 text-sm text-gray-900">{{ $job->id }}</td>
                            <td class="py-3 px-4 text-sm text-gray-600 max-w-[200px] truncate">
                                {{ json_decode($job->payload)->displayName ?? 'Unknown' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-red-600 max-w-[300px] truncate">
                                {{ Str::limit($job->exception, 100) }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                            <td class="py-3 px-4">
                                <form method="POST" action="{{ route('admin.queue.destroy', $job->id) }}" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-white border border-red-200 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-8 bg-white border border-gray-200 rounded-xl p-6">
    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fas fa-terminal text-gray-400"></i> Queue Commands
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:work</code>
            <p class="text-xs text-gray-500 mt-1">Start processing queued emails</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:listen</code>
            <p class="text-xs text-gray-500 mt-1">Monitor queue (dev mode)</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:failed</code>
            <p class="text-xs text-gray-500 mt-1">List all failed jobs</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:retry all</code>
            <p class="text-xs text-gray-500 mt-1">Retry all failed jobs</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:flush</code>
            <p class="text-xs text-gray-500 mt-1">Clear all failed jobs</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg text-sm">
            <code class="text-red-600 font-mono">php artisan queue:prune-failed --hours=168</code>
            <p class="text-xs text-gray-500 mt-1">Prune jobs older than 7 days</p>
        </div>
    </div>
</div>
