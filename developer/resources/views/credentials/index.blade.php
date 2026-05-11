@extends('layouts.app')
@section('title', 'Credentials — ' . ($project['name'] ?? ''))

@section('content')
@php $pid = $project['id']; @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('projects.show', $pid) }}" class="text-xs hover:underline" style="color:#9b8e90">← {{ $project['name'] }}</a>
        <h2 class="text-xl font-bold mt-2">Credentials</h2>
    </div>
    <a href="{{ route('projects.credentials.create', $pid) }}"
       class="px-4 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
       style="background:#ff003c;color:#fff">+ New Credential</a>
</div>

{{-- One-time secret banner --}}
@if(session('new_credential_secret'))
<div class="rounded-2xl p-5 mb-6" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25)">
    <div class="text-sm font-semibold mb-2" style="color:#10b981">🔑 Your new credential secret</div>
    <p class="text-xs mb-3" style="color:#9b8e90">Copy this now. It will <strong>never</strong> be shown again.</p>
    <code class="block px-4 py-3 rounded-xl text-sm break-all select-all"
          style="background:#020202;color:#10b981;border:1px solid rgba(16,185,129,0.2)">
        {{ session('new_credential_secret') }}
    </code>
</div>
@endif

{{-- Credentials table --}}
<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="px-6 py-4" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        <span class="text-sm font-semibold">{{ count($credentials) }} credential{{ count($credentials)!==1?'s':'' }}</span>
    </div>

    @forelse($credentials as $cred)
    <div class="px-6 py-4 border-b last:border-0 flex items-center justify-between gap-4"
         style="border-color:rgba(255,255,255,0.04)">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-sm">{{ $cred['name'] }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgba(155,27,48,0.2);color:#ff6b6b">
                    {{ $cred['type'] }}
                </span>
                @if(!$cred['is_active'])
                <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgba(156,163,175,0.1);color:#6b7280">Disabled</span>
                @endif
            </div>
            <div class="text-xs font-mono" style="color:#9b8e90">{{ $cred['identifier'] ?? '—' }}</div>
            @if($cred['expires_at'] ?? null)
            <div class="text-xs mt-1" style="color:#f59e0b">Expires {{ $cred['expires_at'] }}</div>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <form method="POST" action="{{ route('credentials.rotate', $cred['id']) }}">
                @csrf
                <button class="px-3 py-1.5 rounded-lg text-xs transition hover:bg-white/5"
                        style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Rotate</button>
            </form>
            <form method="POST" action="{{ route('credentials.toggle', $cred['id']) }}">
                @csrf
                <button class="px-3 py-1.5 rounded-lg text-xs transition hover:bg-white/5"
                        style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">
                    {{ $cred['is_active'] ? 'Disable' : 'Enable' }}
                </button>
            </form>
            <form method="POST" action="{{ route('credentials.destroy', $cred['id']) }}"
                  onsubmit="return confirm('Revoke this credential? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 rounded-lg text-xs transition hover:bg-red-500/10"
                        style="color:#ff6b6b;border:1px solid rgba(255,0,60,0.15)">Revoke</button>
            </form>
        </div>
    </div>
    @empty
    <div class="px-6 py-12 text-center">
        <div class="text-4xl mb-3">🔑</div>
        <p class="text-sm mb-4" style="color:#9b8e90">No credentials yet.</p>
        <a href="{{ route('projects.credentials.create', $pid) }}"
           class="inline-flex px-4 py-2 rounded-xl text-sm font-semibold"
           style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">
            + Create Credential
        </a>
    </div>
    @endforelse
</div>
@endsection
