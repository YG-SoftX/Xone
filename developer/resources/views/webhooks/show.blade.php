@extends('layouts.app')
@section('title', 'Deliveries — ' . ($webhook['name'] ?? ''))

@section('content')

<div class="mb-6">
    <a href="javascript:history.back()" class="text-xs hover:underline" style="color:#9b8e90">← Back</a>
    <h2 class="text-xl font-bold mt-2">{{ $webhook['name'] }} — Deliveries</h2>
    <div class="text-xs mt-1 font-mono" style="color:#9b8e90">{{ $webhook['url'] }}</div>
</div>

<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="px-6 py-4 flex items-center justify-between" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        <span class="text-sm font-semibold">Delivery History</span>
        <span class="text-xs" style="color:#9b8e90">
            {{ $webhook['success_count']??0 }} delivered · {{ $webhook['failure_count']??0 }} failed
        </span>
    </div>

    @forelse($deliveries as $d)
    <div class="px-6 py-4 border-b last:border-0" style="border-color:rgba(255,255,255,0.04)">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs px-2 py-0.5 rounded-full font-mono"
                          style="{{ ($d['success']??false) ? 'background:rgba(16,185,129,0.15);color:#10b981' : 'background:rgba(255,0,60,0.12);color:#ff6b6b' }}">
                        {{ $d['status_code']??'—' }}
                    </span>
                    <span class="text-xs font-mono" style="color:#9b8e90">{{ $d['event_type'] }}</span>
                    <span class="text-xs" style="color:#4a4044">attempt {{ $d['attempt']??1 }}</span>
                </div>
                @if($d['response_body']??null)
                <details class="mt-2">
                    <summary class="text-xs cursor-pointer" style="color:#9b8e90">Response</summary>
                    <pre class="mt-2 text-xs p-3 rounded-lg overflow-auto max-h-40"
                         style="background:#020202;color:#9b8e90">{{ $d['response_body'] }}</pre>
                </details>
                @endif
            </div>
            @if(!($d['success']??false))
            <form method="POST" action="{{ route('webhooks.redeliver', $d['id']) }}" class="flex-shrink-0">
                @csrf
                <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-white/5"
                        style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Retry</button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="px-6 py-12 text-center text-sm" style="color:#9b8e90">No deliveries yet.</div>
    @endforelse
</div>
@endsection
