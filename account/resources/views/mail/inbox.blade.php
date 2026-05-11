@extends('layouts.platform')
@php /** @var \Illuminate\Pagination\LengthAwarePaginator $messages */ @endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Mail Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">YG Mail</h1>
            <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>
        </div>
        <a href="{{ route('mail.compose') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            ✉️ Compose
        </a>
    </div>

    <!-- Mail Layout -->
    <div class="flex gap-6">
        <!-- Sidebar -->
        <div class="w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow-md p-4">
                <nav class="space-y-2">
                    <a href="{{ route('mail.index', ['folder' => 'inbox']) }}" 
                       class="block px-3 py-2 rounded-md {{ request('folder', 'inbox') === 'inbox' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        📥 Inbox
                        @if($unreadCount > 0)
                            <span class="ml-2 bg-red-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('mail.index', ['folder' => 'sent']) }}" 
                       class="block px-3 py-2 rounded-md {{ request('folder') === 'sent' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        📤 Sent
                    </a>
                    <a href="{{ route('mail.index', ['folder' => 'drafts']) }}" 
                       class="block px-3 py-2 rounded-md {{ request('folder') === 'drafts' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        📝 Drafts
                    </a>
                    <a href="{{ route('mail.index', ['folder' => 'trash']) }}" 
                       class="block px-3 py-2 rounded-md {{ request('folder') === 'trash' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        🗑️ Trash
                    </a>
                </nav>

                <hr class="my-4">

                <!-- Storage Usage -->
                <div class="px-3">
                    <p class="text-xs text-gray-600 mb-2">Storage Used</p>
                    <div class="bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($mailbox->storage_used_bytes / $mailbox->storage_quota_bytes) * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ number_format($mailbox->storage_used_bytes / 1073741824, 2) }} GB / 
                        {{ number_format($mailbox->storage_quota_bytes / 1073741824, 2) }} GB
                    </p>
                </div>
            </div>
        </div>

        <!-- Email List -->
        <div class="flex-1">
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900 capitalize">{{ $folder }}</h2>
                </div>

                <div class="divide-y divide-gray-200">
                    @forelse($messages as $message)
                        <div class="px-6 py-4 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location.href='{{ route('mail.show', $message->id) }}'">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $message->from_name ?? $message->from_email }}
                                        </p>
                                        @if(!$message->is_read)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                New
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 truncate mt-1">{{ $message->subject }}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ \Carbon\Carbon::parse($message->received_at)->diffForHumans() }}
                                    </p>
                                </div>
                                @if($message->has_attachments)
                                    <svg class="h-5 w-5 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No emails in {{ $folder }}</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($messages->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
