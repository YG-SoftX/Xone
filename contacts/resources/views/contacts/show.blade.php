@extends('layouts.app')
@section('title', $contact->full_name)

@section('contacts-content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-gray-50 px-6 py-8 flex flex-col sm:flex-row items-center gap-5">
            @if($contact->avatar)
            <img src="{{ $contact->avatar }}" class="w-24 h-24 rounded-full object-cover shadow" alt="">
            @else
            <div class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-bold shadow">
                {{ $contact->initials }}
            </div>
            @endif
            <div class="text-center sm:text-left flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $contact->full_name }}</h1>
                @if($contact->job_title || $contact->company)
                <p class="text-gray-500">
                    {{ $contact->job_title }}
                    @if($contact->job_title && $contact->company) at @endif
                    {{ $contact->company }}
                </p>
                @endif
                @if($contact->groups->count())
                <div class="flex flex-wrap gap-1.5 mt-2 justify-center sm:justify-start">
                    @foreach($contact->groups as $group)
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-medium text-white"
                          style="background: {{ $group->color }}">{{ $group->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <form method="POST" action="{{ route('contacts.star', $contact->id) }}">
                    @csrf
                    <button class="p-2.5 rounded-full border border-gray-200 hover:bg-gray-100 transition {{ $contact->is_starred ? 'text-yellow-400' : 'text-gray-400' }}">
                        <i class="fas fa-star"></i>
                    </button>
                </form>
                <a href="{{ route('contacts.edit', $contact->id) }}"
                   class="p-2.5 rounded-full border border-gray-200 hover:bg-gray-100 transition text-gray-500">
                    <i class="fas fa-pen"></i>
                </a>
                <form method="POST" action="{{ route('contacts.destroy', $contact->id) }}"
                      onsubmit="return confirm('Delete this contact?')">
                    @csrf @method('DELETE')
                    <button class="p-2.5 rounded-full border border-gray-200 hover:bg-red-50 hover:border-red-200 hover:text-red-600 transition text-gray-500">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Contact details --}}
        <div class="p-6 space-y-5">

            {{-- Emails --}}
            @if($contact->emails)
            @foreach($contact->emails as $emailEntry)
            <div class="flex items-center gap-4">
                <div class="w-8 flex justify-center text-gray-400">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="flex-1">
                    <a href="mailto:{{ $emailEntry['email'] }}" class="text-blue-600 hover:underline">
                        {{ $emailEntry['email'] }}
                    </a>
                    <p class="text-xs text-gray-400 capitalize">{{ $emailEntry['type'] ?? 'Email' }}</p>
                </div>
            </div>
            @endforeach
            @endif

            {{-- Phones --}}
            @if($contact->phones)
            @foreach($contact->phones as $phone)
            <div class="flex items-center gap-4">
                <div class="w-8 flex justify-center text-gray-400">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="flex-1">
                    <a href="tel:{{ $phone['number'] }}" class="text-gray-900">{{ $phone['number'] }}</a>
                    <p class="text-xs text-gray-400 capitalize">{{ $phone['type'] ?? 'Phone' }}</p>
                </div>
            </div>
            @endforeach
            @endif

            {{-- Birthday --}}
            @if($contact->birthday)
            <div class="flex items-center gap-4">
                <div class="w-8 flex justify-center text-gray-400"><i class="fas fa-birthday-cake"></i></div>
                <p class="text-gray-700">{{ $contact->birthday->format('F j, Y') }}</p>
            </div>
            @endif

            {{-- Notes --}}
            @if($contact->notes)
            <div class="flex items-start gap-4">
                <div class="w-8 flex justify-center text-gray-400 mt-0.5"><i class="fas fa-sticky-note"></i></div>
                <p class="text-gray-700 whitespace-pre-line flex-1">{{ $contact->notes }}</p>
            </div>
            @endif

        </div>

        {{-- Footer nav --}}
        <div class="px-6 py-4 border-t border-gray-100 flex justify-between">
            <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to contacts
            </a>
            <a href="{{ route('contacts.edit', $contact->id) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Edit contact
            </a>
        </div>
    </div>
</div>
@endsection
