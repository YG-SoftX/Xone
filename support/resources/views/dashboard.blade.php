@extends('layouts.app')
@section('title', 'Overview')

@section('content')
{{-- Welcome banner --}}
<div class="rounded-2xl p-6 mb-8 flex items-center justify-between"
     style="background:linear-gradient(135deg,rgba(155,27,48,0.3) 0%,rgba(255,0,60,0.05) 100%);border:1px solid rgba(255,0,60,0.15)">
    <div>
        <h2 class="text-xl font-bold mb-1">Welcome back, {{ $user->name }}</h2>
        <p class="text-sm" style="color:#9b8e90">Get help, report issues, and track your support tickets.</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
       style="background:#ff003c;color:#fff">
        + New Ticket
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">📬</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#fb7185">Open</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['open'] }}</div>
    </div>
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">✅</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#34d399">Resolved</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['resolved'] }}</div>
    </div>
    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">📋</span>
            <span class="text-xs font-medium uppercase tracking-wider" style="color:#60a5fa">Total</span>
        </div>
        <div class="text-3xl font-bold">{{ $stats['total'] }}</div>
    </div>
</div>

{{-- Recent tickets --}}
<div class="rounded-2xl overflow-hidden mb-8" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="flex items-center justify-between px-6 py-4" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        <h3 class="font-semibold text-sm">Recent Tickets</h3>
        <a href="{{ route('tickets.index') }}" class="text-xs hover:underline" style="color:#9b8e90">View all ↗</a>
    </div>

    @if($recentTickets->count() > 0)
        <div class="divide-y" style="divide-color:rgba(255,255,255,0.04)">
            @foreach($recentTickets as $ticket)
                <a href="{{ route('tickets.show', $ticket->id) }}" class="flex items-center justify-between px-6 py-4 hover:bg-white/5 transition">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $ticket->subject }}</div>
                        <div class="text-xs mt-0.5" style="color:#9b8e90">
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
    @else
        <div class="px-6 py-10 text-center">
            <div class="text-3xl mb-3">🎉</div>
            <p class="text-sm mb-4" style="color:#9b8e90">No tickets yet! Everything seems to be running smoothly.</p>
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
               style="background:rgba(255,0,60,0.15);color:#ff003c;border:1px solid rgba(255,0,60,0.2)">
                + Create Ticket
            </a>
        </div>
    @endif
</div>

{{-- Quick links --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach([
        ['📬','Submit Ticket','Get help from our team','tickets.create'],
        ['📖','Knowledge Base','Browse articles & guides','knowledge.index'],
        ['❓','FAQ','Frequently asked questions','knowledge.index'],
        ['📊','System Status','Check service availability','#'],
    ] as [$icon,$title,$desc,$route])
    <a href="{{ $route === '#' ? '#' : route($route) }}"
       class="rounded-2xl p-5 hover:bg-white/5 transition-colors"
       style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        <div class="text-2xl mb-3">{{ $icon }}</div>
        <div class="text-sm font-semibold mb-1">{{ $title }}</div>
        <div class="text-xs" style="color:#9b8e90">{{ $desc }}</div>
    </a>
    @endforeach
</div>
@endsection
