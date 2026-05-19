@extends('layouts.app')
@section('title', 'My Tickets')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">My Tickets</h2>
    <a href="{{ route('tickets.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
       style="background:#ff003c;color:#fff">
        + New Ticket
    </a>
</div>

{{-- Status filter tabs --}}
<div class="flex gap-2 mb-6">
    @foreach(['' => 'All', 'open' => 'Open', 'pending' => 'Pending', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label)
    <a href="{{ $val ? route('tickets.index', ['status' => $val]) : route('tickets.index') }}"
       class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
       style="{{ ($currentStatus ?: '') === $val ? 'background:rgba(255,0,60,0.15);color:#ff003c' : 'background:rgba(255,255,255,0.04);color:#9b8e90;border:1px solid var(--border)' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

@if($tickets->count() > 0)
    <div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
        @foreach($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket->id) }}"
           class="flex items-center justify-between px-6 py-4 hover:bg-white/5 transition border-b"
           style="border-color:rgba(255,255,255,0.04)">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-sm font-medium truncate">{{ $ticket->subject }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded uppercase font-bold"
                          style="@switch($ticket->priority)
                              @case('high') background:rgba(251,113,133,0.15);color:#fb7185 @break
                              @case('medium') background:rgba(250,204,21,0.15);color:#facc15 @break
                              @default background:rgba(156,163,175,0.1);color:#6b7280 @endswitch">
                        {{ $ticket->priority }}
                    </span>
                </div>
                <div class="text-xs" style="color:#9b8e90">
                    {{ ucfirst($ticket->category) }} · {{ $ticket->created_at->diffForHumans() }}
                </div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 ml-3"
                  style="@switch($ticket->status)
                      @case('open') background:rgba(251,113,133,0.15);color:#fb7185 @break
                      @case('pending') background:rgba(250,204,21,0.15);color:#facc15 @break
                      @case('resolved') background:rgba(52,211,153,0.15);color:#34d399 @break
                      @default background:rgba(156,163,175,0.1);color:#6b7280 @endswitch">
                {{ ucfirst($ticket->status) }}
            </span>
        </a>
        @endforeach
    </div>

    <div class="mt-6">
        <style>
            .pagination { display:flex; gap:4px; justify-content:center; list-style:none; padding:0; }
            .pagination li { display:inline; }
            .pagination a, .pagination span {
                display:inline-flex; align-items:center; justify-content:center;
                min-width:36px; height:36px; padding:0 8px;
                border-radius:10px; font-size:13px; font-weight:500;
                transition:all .15s ease;
                color:#9b8e90; background:rgba(255,255,255,0.03);
                border:1px solid rgba(255,255,255,0.06);
            }
            .pagination a:hover { background:rgba(255,255,255,0.08); color:#fff; }
            .pagination .active span {
                background:rgba(255,0,60,0.15); color:#ff003c;
                border-color:rgba(255,0,60,0.2);
            }
            .pagination .disabled span { opacity:0.3; pointer-events:none; }
        </style>
        {{ $tickets->onEachSide(1)->links() }}
    </div>
@else
    <div class="text-center py-16">
        <div class="text-4xl mb-4">🎉</div>
        <p class="text-sm mb-4" style="color:#9b8e90">No tickets found{{ $currentStatus ? " with status \"{$currentStatus}\"" : '' }}.</p>
        <a href="{{ route('tickets.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
           style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">
            + Create Ticket
        </a>
    </div>
@endif
@endsection
