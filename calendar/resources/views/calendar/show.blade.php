@extends('layouts.app')
@section('title', $event->title)

@section('calendar-content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Colour bar --}}
        <div class="h-2" style="background: {{ $event->display_color }}"></div>

        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $event->title }}</h1>
                <div class="flex gap-2">
                    <a href="{{ route('calendar.edit', $event->id) }}"
                       class="p-2 rounded-full hover:bg-gray-100 text-gray-500">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('calendar.destroy', $event->id) }}"
                          onsubmit="return confirm('Delete this event?')">
                        @csrf @method('DELETE')
                        <button class="p-2 rounded-full hover:bg-red-50 text-gray-500 hover:text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <a href="{{ route('calendar.index', ['date' => $event->starts_at->toDateString()]) }}"
                       class="p-2 rounded-full hover:bg-gray-100 text-gray-500">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>

            <div class="space-y-4 text-sm">
                {{-- Date/time --}}
                <div class="flex items-start gap-3">
                    <i class="fas fa-clock text-gray-400 mt-0.5 w-5"></i>
                    <div>
                        @if($event->all_day)
                            <p>{{ $event->starts_at->format('l, F j, Y') }} (All day)</p>
                        @else
                            <p>{{ $event->starts_at->format('l, F j, Y') }}</p>
                            <p class="text-gray-500">{{ $event->starts_at->format('g:ia') }} – {{ $event->ends_at->format('g:ia') }}</p>
                        @endif
                        @if($event->isRecurring())
                        <p class="text-xs text-blue-600 mt-0.5"><i class="fas fa-redo mr-1"></i>Recurring</p>
                        @endif
                    </div>
                </div>

                {{-- Location --}}
                @if($event->location)
                <div class="flex items-center gap-3">
                    <i class="fas fa-map-marker-alt text-gray-400 w-5"></i>
                    <span class="text-gray-700">{{ $event->location }}</span>
                </div>
                @endif

                {{-- Meet link --}}
                @if($event->meet_link && str_starts_with($event->meet_link, 'https://'))
                <div class="flex items-center gap-3">
                    <i class="fas fa-video text-blue-500 w-5"></i>
                    <a href="{{ $event->meet_link }}" target="_blank" rel="noopener noreferrer"
                       class="text-blue-600 hover:underline font-medium">Join YG Meet</a>
                </div>
                @endif

                {{-- Calendar --}}
                <div class="flex items-center gap-3">
                    <span class="w-5 h-5 rounded-sm shrink-0" style="background: {{ $event->display_color }}"></span>
                    <span class="text-gray-700">{{ $event->calendar->name }}</span>
                </div>

                {{-- Description --}}
                @if($event->description)
                <div class="flex items-start gap-3">
                    <i class="fas fa-align-left text-gray-400 mt-0.5 w-5"></i>
                    <p class="text-gray-700 whitespace-pre-line">{{ $event->description }}</p>
                </div>
                @endif

                {{-- Attendees --}}
                @if($event->attendees->count())
                <div class="flex items-start gap-3">
                    <i class="fas fa-users text-gray-400 mt-0.5 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium text-gray-700 mb-2">{{ $event->attendees->count() }} guest(s)</p>
                        <div class="space-y-1.5">
                            @foreach($event->attendees as $att)
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                    {{ strtoupper(substr($att->name ?? $att->email, 0, 1)) }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">{{ $att->name ?? $att->email }}</p>
                                    @if($att->is_organizer)
                                    <p class="text-xs text-gray-400">Organizer</p>
                                    @endif
                                </div>
                                <span class="text-xs {{ match($att->response) { 'accepted'=>'text-green-600', 'declined'=>'text-red-500', 'tentative'=>'text-yellow-600', default=>'text-gray-400' } }}">
                                    {{ ucfirst($att->response) }}
                                </span>
                            </div>
                            @endforeach
                        </div>

                        {{-- Respond if this user is an attendee --}}
                        @php
                            $myAttendance = $event->attendees->firstWhere('user_id', auth()->id());
                        @endphp
                        @if($myAttendance && !$myAttendance->is_organizer)
                        <div class="flex gap-2 mt-3">
                            @foreach(['accepted' => '✓ Yes', 'tentative' => '? Maybe', 'declined' => '✕ No'] as $resp => $label)
                            <form method="POST" action="{{ route('calendar.respond', $event->id) }}" class="inline">
                                @csrf
                                <input type="hidden" name="response" value="{{ $resp }}">
                                <button class="px-3 py-1.5 text-xs font-medium rounded-lg border transition
                                               {{ $myAttendance->response === $resp ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                                    {{ $label }}
                                </button>
                            </form>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
