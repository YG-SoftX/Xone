@extends('layouts.app')
@section('title', 'Projects')

@section('content')

<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-xl font-bold">My Projects</h2>
        <p class="text-sm mt-1" style="color:#9b8e90">Manage your API projects and access credentials.</p>
    </div>
    <a href="{{ route('projects.create') }}"
       class="px-4 py-2.5 rounded-xl text-sm font-semibold transition hover:opacity-90"
       style="background:#ff003c;color:#fff">+ New Project</a>
</div>

@if(count($projects) === 0)
    <div class="rounded-2xl p-16 text-center" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-5xl mb-4">📁</div>
        <h3 class="text-lg font-semibold mb-2">No projects yet</h3>
        <p class="text-sm mb-6" style="color:#9b8e90">Create a project to start using the YGXone API ecosystem.</p>
        <a href="{{ route('projects.create') }}"
           class="inline-flex px-5 py-2.5 rounded-xl text-sm font-semibold"
           style="background:#ff003c;color:#fff">Create your first project</a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($projects as $project)
        <a href="{{ route('projects.show', $project['id']) }}"
           class="block rounded-2xl p-5 hover:bg-white/5 transition-colors"
           style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0"
                     style="background:rgba(155,27,48,0.3);color:#ff003c">
                    {{ strtoupper(substr($project['name'], 0, 2)) }}
                </div>
                <span class="text-xs px-2 py-1 rounded-full"
                      style="{{ ($project['is_active'] ?? true) ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
                    {{ ($project['is_active'] ?? true) ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="font-semibold mb-1">{{ $project['name'] }}</div>
            <div class="text-xs mb-4" style="color:#9b8e90">
                {{ $project['environment'] ?? 'development' }} · {{ $project['total_api_calls'] ?? 0 }} calls
            </div>
            <div class="flex items-center gap-4 text-xs" style="color:#4a4044">
                <span>{{ $project['counts']['credentials'] ?? 0 }} keys</span>
                <span>{{ $project['counts']['webhooks'] ?? 0 }} webhooks</span>
                <span>{{ count($project['members'] ?? []) }} members</span>
            </div>
        </a>
        @endforeach
    </div>
@endif

@endsection
