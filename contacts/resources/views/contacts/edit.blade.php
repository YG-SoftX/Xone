@extends('layouts.app')
@section('title', 'Edit ' . $contact->full_name)
@section('contacts-content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('contacts.show', $contact->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Edit — {{ $contact->full_name }}</h1>
    </div>
    @include('contacts._form', ['action' => route('contacts.update', $contact->id), 'method' => 'PUT', 'contact' => $contact, 'groups' => $groups])
</div>
@endsection
