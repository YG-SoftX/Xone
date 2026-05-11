@extends('layouts.app')
@section('title', 'Webhooks — ' . ($project['name'] ?? ''))

@section('content')
@php $pid = $project['id']; @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('projects.show', $pid) }}" class="text-xs hover:underline" style="color:#9b8e90">← {{ $project['name'] }}</a>
        <h2 class="text-xl font-bold mt-2">Webhooks</h2>
    </div>
    <a href="{{ route('projects.webhooks.create', $pid) }}"
       class="px-4 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
       style="background:#ff003c;color:#fff">+ Add Webhook</a>
</div>

@if(session('webhook_secret'))
<div class="rounded-2xl p-5 mb-6" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25)">
    <div class="text-sm font-semibold mb-2" style="color:#10b981">🔐 Webhook Signing Secret</div>
    <p class="text-xs mb-3" style="color:#9b8e90">Use this to verify incoming webhook payloads. It will <strong>never</strong> be shown again.</p>
    <code class="block px-4 py-3 rounded-xl text-sm break-all select-all"
          style="background:#020202;color:#10b981;border:1px solid rgba(16,185,129,0.2)">{{ session('webhook_secret') }}</code>
</div>
@endif

<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    @forelse($webhooks as $wh)
    <div class="px-6 py-4 border-b last:border-0" style="border-color:rgba(255,255,255,0.04)">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-medium text-sm">{{ $wh['name'] }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="{{ $wh['is_active'] ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
                        {{ $wh['is_active'] ? 'Active' : 'Paused' }}
                    </span>
                </div>
                <div class="text-xs font-mono truncate mb-2" style="color:#9b8e90">{{ $wh['url'] }}</div>
                <div class="flex flex-wrap gap-1">
                    @foreach(($wh['events']??[]) as $ev)
                    <span class="text-xs px-2 py-0.5 rounded" style="background:rgba(255,255,255,0.04);color:#9b8e90">{{ $ev }}</span>
                    @endforeach
                </div>
                <div class="text-xs mt-2" style="color:#4a4044">
                    {{ $wh['success_count']??0 }} delivered · {{ $wh['failure_count']??0 }} failed
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('webhooks.show', $wh['id']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs hover:bg-white/5"
                   style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Deliveries</a>
                <form method="POST" action="{{ route('webhooks.toggle', $wh['id']) }}">
                    @csrf
                    <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-white/5"
                            style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">
                        {{ $wh['is_active'] ? 'Pause' : 'Resume' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('webhooks.destroy', $wh['id']) }}"
                      onsubmit="return confirm('Delete this webhook?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-red-500/10"
                            style="color:#ff6b6b;border:1px solid rgba(255,0,60,0.15)">Delete</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="px-6 py-12 text-center">
        <div class="text-4xl mb-3">🔔</div>
        <p class="text-sm mb-4" style="color:#9b8e90">No webhooks configured.</p>
        <a href="{{ route('projects.webhooks.create', $pid) }}"
           class="inline-flex px-4 py-2 rounded-xl text-sm font-semibold"
           style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">+ Add Webhook</a>
    </div>
    @endforelse
</div>
@endsection
