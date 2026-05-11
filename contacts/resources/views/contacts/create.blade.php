@extends('layouts.app')
@section('title', 'New Contact')
@section('contacts-content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-xl font-bold text-gray-900 mb-6">New Contact</h1>
    @include('contacts._form', ['action' => route('contacts.store'), 'method' => 'POST', 'groups' => $groups, 'contact' => new \App\Models\Contact()])
</div>
@endsection
