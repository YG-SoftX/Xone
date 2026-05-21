@extends('layouts.app')

@section('title', 'Overview')

@section('content')

{{-- ── Welcome banner ──────────────────────────────────────────────────────── --}}
<div class="rounded-2xl p-6 mb-8 flex items-center justify-between"
     style="background:linear-gradient(135deg,rgba(155,27,48,0.3) 0%,rgba(255,0,60,0.05) 100%);border:1px solid rgba(255,0,60,0.15)">
    <div>
        <h2 class="text-xl font-bold mb-1">Welcome back, {{ $user->name }}</h2>
        <p class="text-sm" style="color:#9b8e90">Manage your projects, credentials, and API usage from the YGXone developer console.</p>
    </div>
    <a href="{{ route('projects.create') }}"
       class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
       style="background:#ff003c;color:#fff">
        + New Project
    </a>
</div>

{{-- ── Stats ────────────────────────────────────────────────────────────────── --}}
@php
    $statCards = [
        ['label'=>'Projects',        'value'=> $stats['projects_count']     ?? '—', 'icon'=>'📁'],
        ['label'=>'Active Keys',     'value'=> $stats['active_credentials'] ?? '—', 'icon'=>'🔑'],
        ['label'=>'Requests Today',  'value'=> $stats['requests_today']     ?? '—', 'icon'=>'⚡'],
        ['label'=>'Billing Balance', 'value'=> $stats['billing_balance']    ?? '—', 'icon'=>'💳'],
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach ($statCards as $card)
        <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-lg">{{ $card['icon'] }}</span>
            </div>
            <div class="text-2xl font-bold mb-1">{{ $card['value'] }}</div>
            <div class="text-xs" style="color:#9b8e90">{{ $card['label'] }}</div>
        </div>
    @endforeach
</div>

{{-- ── Recent projects ─────────────────────────────────────────────────────── --}}
<div class="rounded-2xl overflow-hidden mb-8" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="flex items-center justify-between px-6 py-4" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        <h3 class="font-semibold text-sm">Recent Projects</h3>
        <a href="{{ route('projects.index') }}"
           class="text-xs hover:underline" style="color:#9b8e90">View all</a>
    </div>

    @if(count($projects) > 0)
        <div class="divide-y" style="divide-color:rgba(255,255,255,0.04)">
            @foreach($projects as $project)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <div class="text-sm font-medium">{{ $project['name'] }}</div>
                        <div class="text-xs mt-0.5" style="color:#9b8e90">
                            ID: {{ $project['project_id'] ?? $project['id'] }} · Created {{ \Carbon\Carbon::parse($project['created_at'])->diffForHumans() }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs px-2 py-0.5 rounded-full"
                              style="{{ $project['is_active'] ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
                            {{ $project['is_active'] ? 'Active' : 'Inactive' }}
                        </span>
                        <a href="{{ route('projects.show', $project['id']) }}"
                           class="text-xs hover:underline" style="color:#ff003c">Open</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="px-6 py-10 text-center">
            <div class="text-3xl mb-3">📁</div>
            <p class="text-sm mb-4" style="color:#9b8e90">No projects yet. Create your first project to get started.</p>
            <a href="{{ route('projects.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
               style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">
                + Create Project
            </a>
        </div>
    @endif
</div>

{{-- ── Quick links ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach([
        ['📊','Analytics','View API usage analytics','projects.index'],
        ['🔑','Credentials','Manage API keys & OAuth clients','projects.index'],
        ['🔔','Webhooks','Configure event webhooks','projects.index'],
        ['👥','Team','Manage project members','projects.index'],
    ] as [$icon,$title,$desc,$routeName])
    <a href="{{ route($routeName) }}"
       class="rounded-2xl p-5 hover:bg-white/5 transition-colors"
       style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-2xl mb-3">{{ $icon }}</div>
        <div class="text-sm font-semibold mb-1">{{ $title }}</div>
        <div class="text-xs" style="color:#9b8e90">{{ $desc }}</div>
    </a>
    @endforeach
</div>

@endsection
