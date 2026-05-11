@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Meet Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">YG Meet</h1>
            <p class="text-sm text-gray-600">Video Conferencing</p>
        </div>
        <button onclick="document.getElementById('scheduleModal').classList.remove('hidden')" 
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            📅 Schedule Meeting
        </button>
    </div>

    <!-- Upcoming Meetings -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Meetings</h2>
        @if($upcomingMeetings->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($upcomingMeetings as $meeting)
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $meeting->title }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ \Carbon\Carbon::parse($meeting->scheduled_at)->format('M d, Y h:i A') }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Duration: {{ $meeting->duration_minutes }} min</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Scheduled
                            </span>
                        </div>
                        
                        <div class="space-y-2">
                            <a href="{{ route('meet.show', $meeting->meeting_code) }}" 
                               class="block w-full text-center bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                Join Meeting
                            </a>
                            <p class="text-xs text-center text-gray-500">Code: {{ $meeting->meeting_code }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($upcomingMeetings->hasPages())
                <div class="mt-6">
                    {{ $upcomingMeetings->links() }}
                </div>
            @endif
        @else
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No upcoming meetings</p>
            </div>
        @endif
    </div>

    <!-- Past Meetings -->
    @if($pastMeetings->isNotEmpty())
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Meetings</h2>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Participants</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($pastMeetings as $meeting)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $meeting->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($meeting->scheduled_at)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $meeting->participants->count() }} participants
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    @if($meeting->recording_url)
                                        <a href="{{ $meeting->recording_url }}" class="text-blue-600 hover:text-blue-900">View Recording</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Schedule Meeting Modal -->
<div id="scheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Schedule Meeting</h3>
        <form action="{{ route('meet.schedule') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date & Time</label>
                <input type="datetime-local" name="scheduled_at" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                <input type="number" name="duration_minutes" value="60" min="15" max="480" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Schedule</button>
            </div>
        </form>
    </div>
</div>
@endsection
