@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('mail.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Inbox</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Compose Email</h1>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('mail.send') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- To Field -->
            <div class="mb-4">
                <label for="to" class="block text-sm font-medium text-gray-700 mb-2">To</label>
                <input type="email" name="to" id="to" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="recipient@example.com">
                @error('to')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Subject Field -->
            <div class="mb-4">
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <input type="text" name="subject" id="subject" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Email subject">
                @error('subject')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Body Field -->
            <div class="mb-4">
                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea name="body" id="body" rows="12" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Write your message here..."></textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Attachments -->
            <div class="mb-6">
                <label for="attachments" class="block text-sm font-medium text-gray-700 mb-2">Attachments (Max 10MB each)</label>
                <input type="file" name="attachments[]" id="attachments" multiple
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500">You can select multiple files</p>
                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('mail.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Send Email
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
