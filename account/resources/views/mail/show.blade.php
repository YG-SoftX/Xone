@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('mail.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Inbox</a>
    </div>

    <!-- Email Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $message->subject }}</h1>
                <div class="mt-2 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ substr($message->from_name ?? $message->from_email, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $message->from_name ?? $message->from_email }}</p>
                        <p class="text-xs text-gray-500">&lt;{{ $message->from_email }}&gt;</p>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($message->received_at)->format('M d, Y h:i A') }}</p>
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($message->received_at)->diffForHumans() }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 border-t border-gray-200 pt-4">
            <button onclick="replyEmail()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                ↩️ Reply
            </button>
            <button onclick="forwardEmail()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors text-sm">
                ➡️ Forward
            </button>
            <form action="{{ route('mail.destroy', $message->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-red-600 hover:bg-red-50 transition-colors text-sm" onclick="return confirm('Move to trash?')">
                    🗑️ Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Email Body -->
    <div class="bg-white rounded-lg shadow-md p-6">
        @if($message->body_html)
            <div class="prose max-w-none">
                {!! $message->body_html !!}
            </div>
        @else
            <div class="whitespace-pre-wrap text-gray-900 leading-relaxed">
                {{ $message->body_plain }}
            </div>
        @endif
    </div>

    <!-- Attachments -->
    @if($message->attachments->isNotEmpty())
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Attachments ({{ $message->attachments->count() }})</h3>
            <div class="space-y-2">
                @foreach($message->attachments as $attachment)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $attachment->filename }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($attachment->size_bytes / 1024, 2) }} KB</p>
                            </div>
                        </div>
                        <a href="{{ route('mail.attachment.download', $attachment->id) }}" 
                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                            Download
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function replyEmail() {
    window.location.href = '{{ route('mail.compose') }}' + '?reply_to=' + encodeURIComponent('{{ $message->from_email }}') + '&subject=' + encodeURIComponent('Re: {{ $message->subject }}');
}

function forwardEmail() {
    window.location.href = '{{ route('mail.compose') }}' + '?forward=' + '{{ $message->id }}';
}
</script>
@endpush
@endsection
