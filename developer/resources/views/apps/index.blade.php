@extends('layouts.app')
@section('title', 'OAuth Apps')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold">OAuth Apps</h2>
        <p class="text-sm mt-1" style="color:#9b8e90">Register apps that use YG Account SSO for authentication.</p>
    </div>
    <a href="{{ route('apps.create') }}"
       class="px-4 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
       style="background:#ff003c;color:#fff">+ New App</a>
</div>

@if(session('new_app_secret'))
<div class="rounded-2xl p-5 mb-6" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25)">
    <div class="text-sm font-semibold mb-2" style="color:#10b981">🔐 Client Secret</div>
    <p class="text-xs mb-3" style="color:#9b8e90">Copy this now — it will <strong>never</strong> be shown again.</p>
    <code class="block px-4 py-3 rounded-xl text-sm break-all select-all"
          style="background:#020202;color:#10b981;border:1px solid rgba(16,185,129,0.2)">{{ session('new_app_secret') }}</code>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($apps as $app)
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-start justify-between mb-3">
            <div>
                <div class="font-semibold">{{ $app['name'] }}</div>
                <div class="text-xs mt-0.5 font-mono" style="color:#9b8e90">{{ $app['slug'] }}</div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full"
                  style="{{ $app['is_active'] ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(156,163,175,0.1);color:#6b7280' }}">
                {{ $app['is_active'] ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <div class="text-xs mb-3" style="color:#9b8e90">{{ $app['description']??'' }}</div>
        <div class="text-xs font-mono mb-4 truncate" style="color:#4a4044">{{ $app['redirect_uri'] }}</div>
        <div class="flex items-center gap-2 text-xs" style="color:#9b8e90">
            <span>{{ $app['auth_count']??0 }} auths</span>
            <span>·</span>
            <span>Client ID: {{ substr($app['client_id']??'',0,12) }}…</span>
        </div>
        <div class="flex items-center gap-2 mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.04)">
            <a href="{{ route('apps.show', $app['id']) }}"
               class="px-3 py-1.5 rounded-lg text-xs hover:bg-white/5"
               style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Details</a>
            <form method="POST" action="{{ route('apps.rotate', $app['id']) }}">
                @csrf
                <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-white/5"
                        style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Rotate Secret</button>
            </form>
            <form method="POST" action="{{ route('apps.destroy', $app['id']) }}"
                  onsubmit="return confirm('Delete this app?')" class="ml-auto">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-red-500/10"
                        style="color:#ff6b6b;border:1px solid rgba(255,0,60,0.15)">Delete</button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-2 rounded-2xl p-16 text-center" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-5xl mb-4">🔗</div>
        <h3 class="text-lg font-semibold mb-2">No OAuth apps</h3>
        <p class="text-sm mb-6" style="color:#9b8e90">Register an app to let users sign in with YG Account.</p>
        <a href="{{ route('apps.create') }}"
           class="inline-flex px-5 py-2.5 rounded-xl text-sm font-semibold"
           style="background:#ff003c;color:#fff">Register an app</a>
    </div>
    @endforelse
</div>
@endsection
