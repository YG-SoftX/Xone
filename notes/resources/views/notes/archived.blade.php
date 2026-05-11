@extends('layouts.app')
@section('title', 'Archived Notes')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="fas fa-archive text-gray-500 mr-2"></i>Archived Notes
        </h1>
        <p class="text-sm text-gray-500">Notes you've archived appear here</p>
    </div>

    @if($notes->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($notes as $note)
            @include('notes.components.note-card', ['note' => $note])
        @endforeach
    </div>
    @else
    <div class="text-center py-20">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <i class="fas fa-archive text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No archived notes</h3>
        <p class="text-gray-500">Archived notes will appear here</p>
    </div>
    @endif
</div>
@endsection
