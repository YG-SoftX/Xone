@php
    $mini      = $current->copy();
    $miniStart = $mini->copy()->startOfMonth()->startOfWeek();
    $miniEnd   = $mini->copy()->endOfMonth()->endOfWeek();
    $today     = now()->toDateString();
    $safeView  = in_array(request('view'), ['month','week','day','agenda']) ? request('view') : 'month';
    $safeDate  = request('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', request('date')) ? request('date') : null;
@endphp
<div class="text-sm">
    <div class="flex items-center justify-between mb-2">
        <span class="font-medium text-gray-700">{{ $mini->format('F Y') }}</span>
        <div class="flex gap-1">
            <a href="{{ route('calendar.index', ['view' => $safeView, 'date' => $mini->copy()->subMonth()->startOfMonth()->toDateString()]) }}"
               class="p-1 rounded hover:bg-gray-100 text-gray-400"><i class="fas fa-chevron-left text-xs"></i></a>
            <a href="{{ route('calendar.index', ['view' => $safeView, 'date' => $mini->copy()->addMonth()->startOfMonth()->toDateString()]) }}"
               class="p-1 rounded hover:bg-gray-100 text-gray-400"><i class="fas fa-chevron-right text-xs"></i></a>
        </div>
    </div>
    <div class="grid grid-cols-7 gap-0.5 text-center">
        @foreach(['S','M','T','W','T','F','S'] as $d)
        <div class="text-xs text-gray-400 font-medium py-1">{{ $d }}</div>
        @endforeach
        @php $d = $miniStart->copy(); @endphp
        @while($d <= $miniEnd)
        @php $ds = $d->toDateString(); @endphp
        <a href="{{ route('calendar.index', ['view' => $safeView, 'date' => $ds]) }}"
           class="text-xs py-1 rounded-full hover:bg-gray-100 transition
                  {{ $ds === $today ? 'bg-blue-600 text-white hover:bg-blue-700 font-bold' : '' }}
                  {{ $d->month !== $mini->month ? 'text-gray-300' : 'text-gray-700' }}
                  {{ $safeDate && $ds === $safeDate && $ds !== $today ? 'bg-blue-100 font-semibold' : '' }}">
            {{ $d->day }}
        </a>
        @php $d->addDay(); @endwhile
    </div>
</div>
