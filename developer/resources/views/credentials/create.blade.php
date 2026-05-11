@extends('layouts.app')
@section('title', 'New Credential')

@section('content')
@php $pid = $project['id']; @endphp

<div class="max-w-lg">
    <a href="{{ route('projects.credentials', $pid) }}" class="text-sm hover:underline" style="color:#9b8e90">← Credentials</a>
    <h2 class="text-xl font-bold mt-3 mb-6">Create credential</h2>

    @if($errors->has('api'))
    <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
        {{ $errors->first('api') }}
    </div>
    @endif

    <form method="POST" action="{{ route('projects.credentials.store', $pid) }}" class="space-y-5">
        @csrf
        <div class="rounded-2xl p-6 space-y-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div>
                <label class="block text-sm font-medium mb-2">Name <span style="color:#ff003c">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Production API Key"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Type <span style="color:#ff003c">*</span></label>
                <select name="type" required class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                        style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                    <option value="api_key">API Key</option>
                    <option value="oauth_client">OAuth Client</option>
                    <option value="service_account">Service Account</option>
                    <option value="webhook_secret">Webhook Secret</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Allowed IPs <span class="font-normal" style="color:#9b8e90">(comma-separated, optional)</span></label>
                <input type="text" name="restrictions[allowed_ips]" value="{{ old('restrictions.allowed_ips') }}"
                       placeholder="192.168.1.1, 10.0.0.0/24"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Expiry date <span class="font-normal" style="color:#9b8e90">(optional)</span></label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
                    style="background:#ff003c;color:#fff">Create Credential</button>
            <a href="{{ route('projects.credentials', $pid) }}"
               class="px-6 py-2.5 rounded-xl text-sm hover:bg-white/5"
               style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Cancel</a>
        </div>
    </form>
</div>
@endsection
