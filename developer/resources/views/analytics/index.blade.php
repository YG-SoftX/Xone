@extends('layouts.app')
@section('title', 'Analytics — ' . ($project['name'] ?? ''))

@section('content')
@php $pid = $project['id']; @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('projects.show', $pid) }}" class="text-xs hover:underline" style="color:#9b8e90">← {{ $project['name'] }}</a>
        <h2 class="text-xl font-bold mt-2">Analytics</h2>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            <select name="period" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl text-sm outline-none"
                    style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                @foreach(['7d'=>'Last 7 days','30d'=>'Last 30 days','90d'=>'Last 90 days'] as $val=>$label)
                <option value="{{ $val }}" {{ $period===$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('projects.analytics.export', ['project' => $pid, 'period' => $period]) }}"
           class="px-4 py-2 rounded-xl text-sm transition hover:bg-white/5"
           style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Export CSV</a>
    </div>
</div>

{{-- Summary cards --}}
@php $summary = $overview['summary'] ?? []; @endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['Total Requests', number_format($summary['total_calls']??0), '⚡', null],
        ['Success Rate',   ($summary['success_rate']??0).'%', '✅', null],
        ['Error Rate',     ($summary['error_rate']??0).'%', '❌', 'rgba(255,0,60,0.08)'],
        ['Avg Latency',    ($summary['avg_latency_ms']??0).'ms', '⏱', null],
    ] as [$label,$val,$icon,$bg])
    <div class="rounded-2xl p-5" style="background:{{ $bg ?? 'rgba(255,255,255,0.02)' }};border:1px solid rgba(255,255,255,0.06)">
        <div class="text-xl mb-2">{{ $icon }}</div>
        <div class="text-2xl font-bold mb-1">{{ $val }}</div>
        <div class="text-xs" style="color:#9b8e90">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Top endpoints --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
        <div class="px-6 py-4 text-sm font-semibold" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">Top Endpoints</div>
        @forelse($endpoints['data'] ?? [] as $ep)
        <div class="px-6 py-3 border-b last:border-0 flex items-center justify-between text-sm"
             style="border-color:rgba(255,255,255,0.04)">
            <div class="flex items-center gap-2 min-w-0">
                <span class="px-1.5 py-0.5 rounded text-xs font-mono flex-shrink-0"
                      style="background:rgba(155,27,48,0.2);color:#ff6b6b">{{ $ep['method']??'GET' }}</span>
                <span class="truncate text-xs font-mono" style="color:#9b8e90">{{ $ep['endpoint'] }}</span>
            </div>
            <span class="flex-shrink-0 text-xs ml-3">{{ number_format($ep['total_calls']??0) }}</span>
        </div>
        @empty
        <div class="px-6 py-8 text-sm text-center" style="color:#9b8e90">No data for this period.</div>
        @endforelse
    </div>

    <div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
        <div class="px-6 py-4 text-sm font-semibold" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">Latency Percentiles</div>
        @php $p = $latency['percentiles'] ?? []; @endphp
        @if(count($p))
        @foreach([['p50','Median'],['p90','p90'],['p95','p95'],['p99','p99']] as [$key,$label])
        <div class="px-6 py-3 border-b last:border-0 flex items-center justify-between text-sm"
             style="border-color:rgba(255,255,255,0.04)">
            <span style="color:#9b8e90">{{ $label }}</span>
            <span class="font-mono">{{ $p[$key] ?? '—' }}ms</span>
        </div>
        @endforeach
        @else
        <div class="px-6 py-8 text-sm text-center" style="color:#9b8e90">No data for this period.</div>
        @endif
    </div>
</div>

{{-- Errors by status --}}
@php $byStatus = $errors['by_status_code'] ?? []; @endphp
@if(count($byStatus))
<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="px-6 py-4 text-sm font-semibold" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        Errors by Status Code — {{ number_format($errors['total_errors']??0) }} total
    </div>
    <div class="divide-y" style="divide-color:rgba(255,255,255,0.04)">
        @foreach($byStatus as $status)
        <div class="px-6 py-3 flex items-center justify-between text-sm">
            <span class="font-mono px-2 py-0.5 rounded text-xs"
                  style="background:rgba(255,0,60,0.1);color:#ff6b6b">{{ $status['status_code'] }}</span>
            <span style="color:#9b8e90">{{ number_format($status['count']) }} requests</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
