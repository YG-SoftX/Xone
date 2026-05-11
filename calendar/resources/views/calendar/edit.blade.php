@extends('layouts.app')
@section('title', 'Edit Event')

@section('calendar-content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('calendar.show', $event->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Edit Event</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <form method="POST" action="{{ route('calendar.update', $event->id) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required
                       class="w-full text-2xl font-light border-b border-gray-300 pb-2 focus:outline-none focus:border-blue-500"
                       placeholder="Event title">
                @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Start</label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" required
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">End</label>
                    <input type="datetime-local" name="ends_at"
                           value="{{ old('ends_at', $event->ends_at->format('Y-m-d\TH:i')) }}" required
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="all_day" {{ $event->all_day ? 'checked' : '' }} class="rounded">
                All day
            </label>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Location</label>
                <input type="text" name="location" value="{{ old('location', $event->location) }}"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Add location">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">YG Meet Link</label>
                <input type="url" name="meet_link" value="{{ old('meet_link', $event->meet_link) }}"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://meet.ygxone.com/...">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                          placeholder="Add description">{{ old('description', $event->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Calendar</label>
                <select name="calendar_id"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    @foreach($calendars as $cal)
                    <option value="{{ $cal->id }}" {{ $event->calendar_id === $cal->id ? 'selected' : '' }}>
                        {{ $cal->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    @foreach(['confirmed' => 'Confirmed', 'tentative' => 'Tentative', 'cancelled' => 'Cancelled'] as $val => $label)
                    <option value="{{ $val }}" {{ $event->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-between pt-2">
                <a href="{{ route('calendar.show', $event->id) }}"
                   class="px-5 py-2 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
