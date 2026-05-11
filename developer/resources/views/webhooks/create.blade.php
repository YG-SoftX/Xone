@extends('layouts.app')
@section('title', 'New Webhook')

@section('content')
@php $pid = $project['id']; @endphp

<div class="max-w-xl">
    <a href="{{ route('projects.webhooks', $pid) }}" class="text-sm hover:underline" style="color:#9b8e90">← Webhooks</a>
    <h2 class="text-xl font-bold mt-3 mb-6">Add webhook endpoint</h2>

    @if($errors->has('api'))
    <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
        {{ $errors->first('api') }}
    </div>
    @endif

    <form method="POST" action="{{ route('projects.webhooks.store', $pid) }}" class="space-y-5">
        @csrf
        <div class="rounded-2xl p-6 space-y-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div>
                <label class="block text-sm font-medium mb-2">Name <span style="color:#ff003c">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Production handler"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Endpoint URL <span style="color:#ff003c">*</span></label>
                <input type="url" name="url" value="{{ old('url') }}" required placeholder="https://example.com/webhooks/yg"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                <p class="text-xs mt-1" style="color:#9b8e90">HTTPS required in production.</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-3">Events to subscribe <span style="color:#ff003c">*</span></label>
                @foreach($events as $group => $evList)
                <div class="mb-4">
                    <div class="text-xs uppercase tracking-wider mb-2" style="color:#4a4044">{{ $group }}</div>
                    <div class="space-y-2">
                        @foreach($evList as $ev)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="events[]" value="{{ $ev }}"
                                   {{ in_array($ev, old('events',[]))?'checked':'' }}
                                   class="w-4 h-4 rounded" style="accent-color:#ff003c">
                            <span class="text-sm font-mono" style="color:#9b8e90">{{ $ev }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
                    style="background:#ff003c;color:#fff">Create Webhook</button>
            <a href="{{ route('projects.webhooks', $pid) }}"
               class="px-6 py-2.5 rounded-xl text-sm hover:bg-white/5"
               style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Cancel</a>
        </div>
    </form>
</div>
@endsection
