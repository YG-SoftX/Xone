@extends('layouts.app')
@section('title', 'Chat')

@section('chat-content')
<div class="flex flex-col items-center justify-center h-full text-center text-gray-500 px-8">
    <div class="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center text-4xl mb-6">
        💬
    </div>
    <h2 class="text-xl font-bold text-gray-900 mb-2">Welcome to YG Chat</h2>
    <p class="text-gray-500 max-w-md mb-6">
        Start a conversation with your team. Create spaces for projects, teams, or topics,
        or send a direct message to anyone in the ecosystem.
    </p>
    <div class="flex gap-3">
        <button onclick="document.getElementById('new-space-modal').classList.remove('hidden')"
                class="bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-700 transition">
            <i class="fas fa-hashtag mr-2"></i>Create a Space
        </button>
    </div>
</div>
@endsection
