@extends('layouts.calendar')
@section('title', 'YG Calendar')

@section('content')
@php
    use App\Services\NepaliCalendarService;
    $today       = now();
    $bsToday     = NepaliCalendarService::adToBS($today->year, $today->month, $today->day);
    $bsHolidays  = NepaliCalendarService::getHolidays($bsToday['year']);
    $daysInMonth = NepaliCalendarService::getDaysInMonth($bsToday['year'], $bsToday['month']);
    $monthName   = NepaliCalendarService::$monthsNepaliEng[$bsToday['month']];
@endphp

<div class="max-w-6xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-10 gap-6">
        <div>
            <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">YG Calendar</h1>
            <p class="text-sm text-gray-500">
                Today: <strong>{{ $today->format('F j, Y') }}</strong> (AD) &nbsp;|&nbsp;
                <strong class="text-brand">{{ NepaliCalendarService::format($bsToday['year'], $bsToday['month'], $bsToday['day']) }}</strong>
            </p>
        </div>

        <!-- Calendar Mode Toggle -->
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-gray-100 rounded-full p-1">
                <button id="btn-ad" onclick="switchMode('ad')" 
                    class="px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest transition-all bg-white text-gray-900 shadow-sm">
                    AD (Gregorian)
                </button>
                <button id="btn-bs" onclick="switchMode('bs')" 
                    class="px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest transition-all text-gray-500">
                    BS (Nepali)
                </button>
            </div>
            <a href="{{ route('calendar.event.create') }}" 
                class="px-6 py-2.5 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-md hover:bg-opacity-90 transition-all flex items-center gap-2">
                <i class="fas fa-plus text-[10px]"></i> New Event
            </a>
        </div>
    </div>

    <!-- Dual Calendar Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Main Calendar -->
        <div class="lg:col-span-2">
            
            <!-- AD Calendar (Google-style) -->
            <div id="calendar-ad" class="google-card">
                <div class="flex items-center justify-between mb-6">
                    <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h2 class="text-lg font-normal text-gray-900">{{ $today->format('F Y') }}</h2>
                    <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Day Headers -->
                <div class="grid grid-cols-7 mb-2">
                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                        <div class="text-center text-[10px] font-black text-gray-400 uppercase tracking-widest py-2">{{ $day }}</div>
                    @endforeach
                </div>

                <!-- Day Grid -->
                @php
                    $startDay  = (int) $today->copy()->startOfMonth()->dayOfWeek;
                    $totalDays = (int) $today->daysInMonth;
                @endphp
                <div class="grid grid-cols-7 gap-1">
                    @for($i = 0; $i < $startDay; $i++)
                        <div></div>
                    @endfor
                    @for($d = 1; $d <= $totalDays; $d++)
                        @php
                            $bsDate = NepaliCalendarService::adToBS($today->year, $today->month, $d);
                            $isHoliday = collect($bsHolidays)->contains(fn($h) => $h['month'] === $bsDate['month'] && $h['day'] === $bsDate['day']);
                            $isToday   = $d === (int) $today->day;
                        @endphp
                        <div class="relative aspect-square flex flex-col items-center justify-center rounded-xl cursor-pointer hover:bg-gray-50 transition-all group
                            {{ $isToday ? 'bg-brand !text-white shadow-md' : '' }}">
                            <span class="text-sm font-normal {{ $isToday ? 'text-white' : 'text-gray-800' }}">{{ $d }}</span>
                            <span class="text-[8px] {{ $isToday ? 'text-white/70' : 'text-brand/60' }} font-bold">{{ $bsDate['day'] }}</span>
                            @if($isHoliday && !$isToday)
                                <div class="absolute bottom-1 w-1 h-1 bg-red-400 rounded-full"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <!-- BS Calendar (Nepali) -->
            <div id="calendar-bs" class="google-card hidden border-brand/20">
                <div class="flex items-center justify-between mb-2">
                    <button class="w-8 h-8 rounded-full hover:bg-brand/5 flex items-center justify-center text-gray-400">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="text-center">
                        <h2 class="text-lg font-normal text-gray-900">{{ NepaliCalendarService::$monthsNepali[$bsToday['month']] }} {{ $bsToday['year'] }}</h2>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-widest">{{ $monthName }} {{ $bsToday['year'] }} BS</p>
                    </div>
                    <button class="w-8 h-8 rounded-full hover:bg-brand/5 flex items-center justify-center text-gray-400">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Nepali Day Headers -->
                <div class="grid grid-cols-7 mb-2 mt-4">
                    @foreach(NepaliCalendarService::$nepaliDays as $day)
                        <div class="text-center text-[9px] font-black text-gray-400 py-2">{{ $day }}</div>
                    @endforeach
                </div>

                <!-- BS Day Grid -->
                <div class="grid grid-cols-7 gap-1">
                    @for($i = 0; $i < $startDay; $i++)
                        <div></div>
                    @endfor
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $isHoliday = collect($bsHolidays)->contains(fn($h) => $h['month'] === $bsToday['month'] && $h['day'] === $d);
                            $isTodayBS = $d === $bsToday['day'];
                        @endphp
                        <div class="relative aspect-square flex flex-col items-center justify-center rounded-xl cursor-pointer hover:bg-brand/5 transition-all
                            {{ $isTodayBS ? 'bg-brand !text-white shadow-md' : '' }}">
                            <span class="text-sm font-normal {{ $isTodayBS ? 'text-white' : 'text-gray-800' }}">{{ $d }}</span>
                            @if($isHoliday)
                                <div class="absolute bottom-1 w-1.5 h-1.5 {{ $isTodayBS ? 'bg-white' : 'bg-red-400' }} rounded-full"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Upcoming Events & Nepali Holidays -->
        <div class="space-y-6">

            <!-- Upcoming Events -->
            <div class="google-card">
                <h3 class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-4">Upcoming Events</h3>
                @forelse($upcomingEvents ?? [] as $event)
                    <div class="flex items-start gap-3 py-3 border-b border-gray-50 last:border-0">
                        <div class="w-2 h-8 rounded-full mt-1" style="background: {{ data_get($event, 'color', '#1a73e8') }}"></div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ data_get($event, 'title') }}</p>
                            <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse(data_get($event, 'starts_at'))->format('M j, g:i A') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">No upcoming events. Create one now.</p>
                @endforelse
            </div>

            <!-- Nepali Public Holidays -->
            <div class="google-card border-brand/10 bg-brand/[0.01]">
                <h3 class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-4">
                    🇳🇵 Nepali Holidays {{ $bsToday['year'] }} BS
                </h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($bsHolidays as $holiday)
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 text-center w-10">
                                <div class="text-xs font-black text-brand">{{ NepaliCalendarService::$monthsNepaliEng[$holiday['month']] }}</div>
                                <div class="text-lg font-normal text-gray-900 leading-tight">{{ $holiday['day'] }}</div>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-800 leading-tight">{{ $holiday['name'] }}</p>
                                <span class="text-[9px] uppercase font-bold tracking-widest 
                                    {{ $holiday['type'] === 'national' ? 'text-red-500' : ($holiday['type'] === 'religious' ? 'text-amber-500' : 'text-blue-500') }}">
                                    {{ $holiday['type'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    const adCal  = document.getElementById('calendar-ad');
    const bsCal  = document.getElementById('calendar-bs');
    const btnAd  = document.getElementById('btn-ad');
    const btnBs  = document.getElementById('btn-bs');

    if (mode === 'bs') {
        adCal.classList.add('hidden');
        bsCal.classList.remove('hidden');
        btnBs.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        btnBs.classList.remove('text-gray-500');
        btnAd.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
        btnAd.classList.add('text-gray-500');
    } else {
        bsCal.classList.add('hidden');
        adCal.classList.remove('hidden');
        btnAd.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        btnAd.classList.remove('text-gray-500');
        btnBs.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
        btnBs.classList.add('text-gray-500');
    }
}
</script>
@endsection
