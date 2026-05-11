@extends('layouts.app')
@section('title', $project['name'] ?? 'Project')

@section('content')

@php $pid = $project['id']; @endphp

{{-- Header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('projects.index') }}" class="text-xs hover:underline" style="color:#9b8e90">← Projects</a>
        <h2 class="text-xl font-bold mt-2">{{ $project['name'] }}</h2>
        <div class="flex items-center gap-3 mt-1 text-xs" style="color:#9b8e90">
            <span>{{ $project['environment'] ?? 'development' }}</span>
            <span>·</span>
            <span>ID: {{ $project['project_id'] ?? $pid }}</span>
            <span class="px-2 py-0.5 rounded-full" style="{{ ($project['is_active']??true) ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
                {{ ($project['is_active']??true) ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.edit', $pid) }}"
           class="px-4 py-2 rounded-xl text-sm transition hover:bg-white/5"
           style="border:1px solid rgba(255,255,255,0.08);color:#9b8e90">Edit</a>
        @if(!($project['is_active']??true))
        <form method="POST" action="{{ route('projects.activate', $pid) }}">
            @csrf
            <button class="px-4 py-2 rounded-xl text-sm font-semibold" style="background:rgba(16,185,129,0.2);color:#10b981">Reactivate</button>
        </form>
        @endif
    </div>
</div>

{{-- Tab nav --}}
@php
    $tabs = [
        'credentials' => ['label'=>'Credentials', 'route'=>route('projects.credentials',$pid)],
        'analytics'   => ['label'=>'Analytics',   'route'=>route('projects.analytics',$pid)],
        'quotas'      => ['label'=>'Quotas',       'route'=>route('projects.quotas',$pid)],
        'webhooks'    => ['label'=>'Webhooks',     'route'=>route('projects.webhooks',$pid)],
        'team'        => ['label'=>'Team',         'route'=>route('projects.team',$pid)],
    ];
@endphp
<div class="flex gap-1 mb-8 border-b" style="border-color:rgba(255,255,255,0.06)">
    @foreach($tabs as $key => $tab)
    <a href="{{ $tab['route'] }}"
       class="px-4 py-2.5 text-sm font-medium transition border-b-2 -mb-px"
       style="border-color:transparent;color:#9b8e90">
        {{ $tab['label'] }}
    </a>
    @endforeach
</div>

{{-- Quick stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['Total Calls', $project['total_api_calls']??0, '⚡'],
        ['Credentials', count($credentials['data']??$credentials??[]), '🔑'],
        ['Team Members', count($team['data']??$team??[]), '👥'],
        ['Webhooks', count($webhooks['data']??$webhooks??[]), '🔔'],
    ] as [$label,$value,$icon])
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-xl mb-2">{{ $icon }}</div>
        <div class="text-2xl font-bold mb-1">{{ number_format($value) }}</div>
        <div class="text-xs" style="color:#9b8e90">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Recent credentials --}}
<div class="rounded-2xl overflow-hidden mb-6" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="flex items-center justify-between px-6 py-4" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        <h3 class="text-sm font-semibold">Credentials</h3>
        <a href="{{ route('projects.credentials', $pid) }}" class="text-xs hover:underline" style="color:#9b8e90">Manage →</a>
    </div>
    @forelse(array_slice($credentials['data']??$credentials??[], 0, 3) as $cred)
    <div class="flex items-center justify-between px-6 py-3.5 border-b last:border-0" style="border-color:rgba(255,255,255,0.04)">
        <div>
            <div class="text-sm font-medium">{{ $cred['name'] }}</div>
            <div class="text-xs mt-0.5" style="color:#9b8e90">{{ $cred['type'] }} · {{ $cred['identifier'] ?? '***' }}</div>
        </div>
        <span class="text-xs px-2 py-0.5 rounded-full"
              style="{{ $cred['is_active'] ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
            {{ $cred['is_active'] ? 'Active' : 'Disabled' }}
        </span>
    </div>
    @empty
    <div class="px-6 py-8 text-center text-sm" style="color:#9b8e90">No credentials yet.</div>
    @endforelse
</div>

{{-- Quota alerts --}}
@php $alerts = $quotas['alerts'] ?? []; @endphp
@if(count($alerts))
<div class="rounded-2xl p-5 mb-6" style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2)">
    <div class="text-sm font-semibold mb-3" style="color:#f59e0b">⚠ Quota Alerts</div>
    @foreach($alerts as $alert)
    <div class="text-sm mb-1" style="color:#9b8e90">{{ $alert['message'] ?? $alert }}</div>
    @endforeach
</div>
@endif

@endsection
