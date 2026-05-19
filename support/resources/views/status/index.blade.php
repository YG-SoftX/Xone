@extends('layouts.app')
@section('title', 'System Status')

@section('content')
{{-- Overall status banner --}}
<div class="rounded-2xl p-6 mb-8 flex items-center justify-between"
     style="background:linear-gradient(135deg,rgba(155,27,48,0.3) 0%,rgba(255,0,60,0.05) 100%);border:1px solid rgba(255,0,60,0.15)">
    <div class="flex items-center gap-4">
        <div class="text-4xl">
            @if($uptime === 100)
                ✅
            @elseif($uptime >= 80)
                ⚠️
            @else
                ❌
            @endif
        </div>
        <div>
            <h2 class="text-xl font-bold mb-1">
                @if($uptime === 100)
                    All Systems Operational
                @elseif($uptime >= 80)
                    Some Services Degraded
                @else
                    Major Service Disruption
                @endif
            </h2>
            <p class="text-sm" style="color:#9b8e90">
                {{ $stats['operational'] }} operational · {{ $stats['degraded'] }} degraded · {{ $stats['down'] }} down
                &mdash; {{ $uptime }}% uptime
            </p>
        </div>
    </div>
    <div class="text-right">
        <div class="text-xs mb-1" style="color:#9b8e90">Last checked</div>
        <div class="text-sm font-mono">{{ \\Carbon\\Carbon::parse($lastCheck)->diffForHumans() }}</div>
    </div>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span>✅</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#34d399">Operational</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['operational'] }}</div>
    </div>
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span>⚠️</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#facc15">Degraded</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['degraded'] }}</div>
    </div>
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span>❌</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#fb7185">Down</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['down'] }}</div>
    </div>
</div>

{{-- Services grid --}}
<div class="flex items-center justify-between mb-4">
    <h3 class="text-sm font-semibold uppercase tracking-wider" style="color:#9b8e90">All Services ({{ $stats['total'] }})</h3>
    <a href="{{ route('status.refresh') }}"
       class="text-xs px-3 py-1.5 rounded-lg transition-colors hover:bg-white/5"
       style="background:rgba(255,255,255,0.04);color:#9b8e90;border:1px solid rgba(255,255,255,0.06)">
        <i class="fas fa-sync mr-1"></i> Refresh
    </a>
</div>

<div class="grid md:grid-cols-2 gap-3">
    @foreach($services as $service)
        <div class="rounded-2xl p-5 flex items-center justify-between transition-colors hover:bg-white/5"
             style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div class="flex items-center gap-4">
                <div class="text-2xl">{{ $service['icon'] }}</div>
                <div>
                    <div class="text-sm font-semibold">{{ $service['name'] }}</div>
                    <div class="text-xs mt-0.5" style="color:#9b8e90">{{ $service['url'] }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-medium" style="color:{{ $service['color'] }}">
                    {{ $service['message'] }}
                </div>
                @if($service['latency'] > 0)
                    <div class="text-[10px] mt-0.5" style="color:#4a4044">
                        {{ $service['latency'] }}ms response
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Footer note --}}
<div class="text-center mt-8">
    <p class="text-xs" style="color:#4a4044">
        Status checks are cached for 2 minutes. 
        <a href="{{ route('status.refresh') }}" class="hover:underline" style="color:#ff003c">Force refresh</a>
        to get the latest results.
    </p>
</div>
@endsection
