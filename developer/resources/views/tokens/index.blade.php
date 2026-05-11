@extends('layouts.app')
@section('title', 'API Tokens')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold">API Tokens</h2>
        <p class="text-sm mt-1" style="color:#9b8e90">Personal access tokens for direct API authentication.</p>
    </div>
</div>

@if(session('new_token'))
<div class="rounded-2xl p-5 mb-6" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25)">
    <div class="text-sm font-semibold mb-2" style="color:#10b981">🔑 Your new token</div>
    <p class="text-xs mb-3" style="color:#9b8e90">Copy this now — it will <strong>never</strong> be shown again.</p>
    <code class="block px-4 py-3 rounded-xl text-sm break-all select-all"
          style="background:#020202;color:#10b981;border:1px solid rgba(16,185,129,0.2)">{{ session('new_token') }}</code>
</div>
@endif

{{-- Create form --}}
<div class="rounded-2xl p-6 mb-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
    <h3 class="text-sm font-semibold mb-4">Create new token</h3>
    <form method="POST" action="{{ route('tokens.store') }}" class="flex items-end gap-3">
        @csrf
        <div class="flex-1">
            <label class="block text-xs mb-1.5" style="color:#9b8e90">Token name</label>
            <input type="text" name="name" required placeholder="e.g. CI/CD pipeline"
                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                   style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
        </div>
        <div>
            <label class="block text-xs mb-1.5" style="color:#9b8e90">Expires</label>
            <input type="datetime-local" name="expires_at"
                   class="px-4 py-2.5 rounded-xl text-sm outline-none"
                   style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
                style="background:#ff003c;color:#fff">Create Token</button>
    </form>
</div>

{{-- Tokens list --}}
<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    @forelse($tokens as $token)
    <div class="px-6 py-4 border-b last:border-0 flex items-center justify-between"
         style="border-color:rgba(255,255,255,0.04)">
        <div>
            <div class="text-sm font-medium">{{ $token['name'] }}</div>
            <div class="text-xs mt-0.5" style="color:#9b8e90">
                Created {{ $token['created_at']??'' }}
                @if($token['last_used_at']??null) · Last used {{ $token['last_used_at'] }} @endif
                @if($token['expires_at']??null) · Expires {{ $token['expires_at'] }} @endif
            </div>
        </div>
        <form method="POST" action="{{ route('tokens.destroy', $token['id']) }}"
              onsubmit="return confirm('Revoke this token?')">
            @csrf @method('DELETE')
            <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-red-500/10"
                    style="color:#ff6b6b;border:1px solid rgba(255,0,60,0.15)">Revoke</button>
        </form>
    </div>
    @empty
    <div class="px-6 py-12 text-center">
        <div class="text-4xl mb-3">🔑</div>
        <p class="text-sm" style="color:#9b8e90">No tokens yet. Create one above.</p>
    </div>
    @endforelse
</div>
@endsection
