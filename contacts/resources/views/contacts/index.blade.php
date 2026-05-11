@extends('layouts.app')
@section('title', 'Contacts')

@section('contacts-content')
<div class="px-4 py-4">

    {{-- Stats bar --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            {{ $contacts->total() }} contact{{ $contacts->total() !== 1 ? 's' : '' }}
            @if($search) matching "{{ $search }}" @endif
        </p>
        <a href="{{ route('contacts.create') }}"
           class="hidden sm:flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800">
            <i class="fas fa-plus"></i> New
        </a>
    </div>

    @if($contacts->count())

    {{-- Alphabetical list --}}
    @php $grouped = $contacts->getCollection()->groupBy(fn($c) => strtoupper(substr($c->first_name, 0, 1))); @endphp

    @foreach($grouped as $letter => $group)
    <div class="mb-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-widest py-1 border-b border-gray-100 mb-1">
            {{ $letter }}
        </div>
        @foreach($group as $contact)
        <a href="{{ route('contacts.show', $contact->id) }}"
           class="flex items-center gap-4 py-2.5 px-2 hover:bg-gray-50 rounded-xl transition group">

            {{-- Avatar --}}
            @if($contact->avatar)
            <img src="{{ $contact->avatar }}" class="w-10 h-10 rounded-full object-cover shrink-0" alt="">
            @else
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                {{ $contact->initials }}
            </div>
            @endif

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 flex items-center gap-2">
                    {{ $contact->full_name }}
                    @if($contact->is_starred)
                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                    @endif
                </p>
                <p class="text-sm text-gray-500 truncate">
                    {{ $contact->company ?? $contact->primary_email ?? $contact->primary_phone ?? '' }}
                </p>
            </div>

            {{-- Quick actions --}}
            @if($contact->primary_email)
            <a href="mailto:{{ $contact->primary_email }}" onclick="event.stopPropagation()"
               class="hidden group-hover:flex items-center gap-1 text-xs text-gray-500 hover:text-blue-600 px-2 py-1 rounded-lg hover:bg-blue-50">
                <i class="fas fa-envelope"></i>
            </a>
            @endif
            @if($contact->primary_phone)
            <a href="tel:{{ $contact->primary_phone }}" onclick="event.stopPropagation()"
               class="hidden group-hover:flex items-center gap-1 text-xs text-gray-500 hover:text-green-600 px-2 py-1 rounded-lg hover:bg-green-50">
                <i class="fas fa-phone"></i>
            </a>
            @endif
        </a>
        @endforeach
    </div>
    @endforeach

    <div class="mt-4">{{ $contacts->withQueryString()->links() }}</div>

    @else
    <div class="text-center py-24 text-gray-400">
        <i class="fas fa-address-book text-6xl text-gray-200 mb-4"></i>
        <h2 class="text-lg font-medium text-gray-700">
            {{ $search ? 'No contacts match "' . $search . '"' : 'No contacts yet' }}
        </h2>
        @if(!$search)
        <a href="{{ route('contacts.create') }}"
           class="mt-4 inline-block bg-blue-600 text-white font-medium px-6 py-2.5 rounded-xl hover:bg-blue-700">
            Add your first contact
        </a>
        @endif
    </div>
    @endif
</div>
@endsection
