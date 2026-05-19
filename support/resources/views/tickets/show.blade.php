@extends('layouts.app')
@section('title', $ticket->subject)

@section('content')
<div class="max-w-3xl">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <a href="{{ route('tickets.index') }}" class="text-xs hover:underline mb-2 inline-block" style="color:#9b8e90">
                ← Back to tickets
            </a>
            <h2 class="text-lg font-bold">{{ $ticket->subject }}</h2>
            <div class="flex items-center gap-3 mt-1">
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="@switch($ticket->status)
                          @case('open') background:rgba(251,113,133,0.15);color:#fb7185 @break
                          @case('pending') background:rgba(250,204,21,0.15);color:#facc15 @break
                          @case('resolved') background:rgba(52,211,153,0.15);color:#34d399 @break
                          @default background:rgba(156,163,175,0.1);color:#6b7280 @endswitch">
                    {{ ucfirst($ticket->status) }}
                </span>
                <span class="text-xs" style="color:#9b8e90">{{ ucfirst($ticket->category) }}</span>
                <span class="text-xs" style="color:#9b8e90">
                    Created {{ $ticket->created_at->diffForHumans() }}
                </span>
            </div>
        </div>
        @if(in_array($ticket->status, ['open', 'pending', 'resolved']))
        <form method="POST" action="{{ route('tickets.close', $ticket->id) }}">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    style="background:rgba(156,163,175,0.1);color:#9b8e90;border:1px solid rgba(255,255,255,0.06)"
                    onclick="return confirm('Close this ticket?')">
                <i class="fas fa-check mr-1"></i> Close Ticket
            </button>
        </form>
        @endif
    </div>

    {{-- Messages --}}
    <div class="space-y-4 mb-8">
        @forelse($ticket->messages ?? [] as $msg)
        <div class="rounded-2xl p-5 {{ $msg['role'] === 'agent' ? 'ml-8' : '' }}"
             style="background:{{ $msg['role'] === 'agent' ? 'rgba(255,0,60,0.05)' : 'rgba(255,255,255,0.02)' }};border:1px solid rgba(255,255,255,0.06)">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold"
                      style="background:{{ $msg['role'] === 'agent' ? 'var(--crimson-dim)' : '#333' }}">
                    {{ $msg['role'] === 'agent' ? 'S' : 'Y' }}
                </span>
                <span class="text-xs font-medium">{{ $msg['role'] === 'agent' ? 'Support Team' : 'You' }}</span>
                <span class="text-[10px]" style="color:#4a4044">{{ \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() }}</span>
            </div>
            <div class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg['body'] }}</div>
        </div>
        @empty
        <div class="text-center py-8">
            <p class="text-sm" style="color:#4a4044">No messages in this ticket.</p>
        </div>
        @endforelse
    </div>

    {{-- Reply form --}}
    @if(in_array($ticket->status, ['open', 'pending', 'resolved']))
    <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <h3 class="text-sm font-semibold mb-3">Add a Reply</h3>
        <form method="POST" action="{{ route('tickets.reply', $ticket->id) }}">
            @csrf
            <textarea name="message" rows="4" required maxlength="10000"
                      placeholder="Type your reply..."
                      class="w-full px-4 py-3 rounded-xl text-sm bg-white/5 border text-white placeholder-gray-500 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 transition resize-y mb-3"
                      style="border-color:rgba(255,255,255,0.1)"></textarea>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
                        style="background:#ff003c;color:#fff">
                    <i class="fas fa-reply mr-1.5"></i> Send Reply
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="text-center py-6">
        <p class="text-sm" style="color:#4a4044">This ticket is closed. <a href="{{ route('tickets.create') }}" class="hover:underline" style="color:#ff003c">Create a new ticket</a> if you need further assistance.</p>
    </div>
    @endif
</div>
@endsection
