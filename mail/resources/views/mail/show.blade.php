@extends('layouts.mail')
@section('title', $email->subject)

@section('mail-content')
<div class="flex flex-col h-full bg-white">
    
    {{-- Toolbar --}}
    <div class="shrink-0 border-b border-gray-200 px-4 py-3 flex items-center gap-3 bg-white">
        <a href="{{ route('mail.inbox') }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Back to Inbox">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="flex-1"></div>
        
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Archive">
            <i class="fas fa-archive"></i>
        </button>
        <button class="p-2 text-gray-600 hover:bg-red-100 hover:text-red-600 rounded-lg transition" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Mark as unread">
            <i class="fas fa-envelope"></i>
        </button>
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Move to">
            <i class="fas fa-folder"></i>
        </button>
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Labels">
            <i class="fas fa-tag"></i>
        </button>
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="More">
            <i class="fas fa-ellipsis-v"></i>
        </button>
    </div>

    {{-- Email Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        {{-- Subject --}}
        <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $email->subject }}</h1>
        
        {{-- Labels --}}
        @if($email->labels && count($email->labels))
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($email->labels as $label)
            <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-medium">
                {{ $label }}
            </span>
            @endforeach
        </div>
        @endif

        {{-- Sender info --}}
        <div class="flex items-start gap-3 mb-6 pb-6 border-b border-gray-200">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold shrink-0">
                {{ strtoupper(substr($email->sender_name ?? $email->from, 0, 1)) }}
            </div>
            <div class="flex-1">
                <div class="flex items-baseline justify-between">
                    <div>
                        <span class="font-semibold text-gray-900">{{ $email->sender_name ?? $email->from }}</span>
                        <span class="text-sm text-gray-500 ml-2">&lt;{{ $email->from }}&gt;</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $email->created_at->format('M j, Y g:i A') }}
                    </div>
                </div>
                <div class="text-sm text-gray-600 mt-1 flex items-center justify-between">
                    <span>to me</span>
                    <a href="https://pay.ygxone.com/user/send-money?credentials={{ $email->from }}" 
                       target="_blank"
                       class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 bg-blue-50 px-2 py-1 rounded-md transition border border-blue-100">
                       <i class="fas fa-hand-holding-usd"></i> Send Money via YG PAY
                    </a>
                </div>
            </div>
        </div>

        {{-- Email body --}}
        <div class="prose max-w-none text-gray-800 leading-relaxed">
            {!! nl2br(e($email->body)) !!}
        </div>

        {{-- AI Sentiment & Priority Badges --}}
        @if($email->sentiment_label || $email->priority !== 'normal')
        <div class="mt-6 flex flex-wrap gap-2">
            @if($email->sentiment_label === 'positive')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                    <i class="fas fa-smile mr-1.5"></i> Positive Tone
                </span>
            @elseif($email->sentiment_label === 'negative')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                    <i class="fas fa-frown mr-1.5"></i> Negative Tone
                </span>
            @endif
            
            @if($email->priority === 'high')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                    <i class="fas fa-exclamation-triangle mr-1.5"></i> High Priority
                </span>
            @endif
        </div>
        @endif

        {{-- Attachments --}}
        @if($email->attachments && count($email->attachments))
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-paperclip mr-2"></i>{{ count($email->attachments) }} Attachment(s)
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($email->attachments as $attachment)
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition cursor-pointer">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file text-gray-500 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $attachment['name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $attachment['size'] }}</div>
                    </div>
                    <button class="p-2 text-gray-600 hover:bg-gray-200 rounded-lg transition">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Reply actions --}}
        <div class="mt-8 pt-6 border-t border-gray-200 flex items-center gap-3">
            <button onclick="openReplyModal({{ $email->id }})" 
                    class="px-6 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-reply"></i> Reply
            </button>
            <button class="px-6 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-reply-all"></i> Reply all
            </button>
            <button class="px-6 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-share"></i> Forward
            </button>
        </div>
    </div>
</div>

{{-- Reply Modal --}}
<div id="reply-modal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-sm p-0 sm:p-4">
    <div class="bg-white w-full sm:max-w-2xl sm:rounded-2xl shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Reply</h3>
            <button onclick="closeReplyModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="reply-form" method="POST" action="/mail/{{ $email->id }}/reply" class="flex-1 overflow-y-auto p-6">
            @csrf
            <div class="mb-4 p-4 bg-gray-50 rounded-xl text-sm text-gray-600">
                <div class="font-medium mb-1">On {{ $email->created_at->format('M j, Y') }}, {{ $email->sender_name ?? $email->from }} wrote:</div>
                <blockquote class="border-l-4 border-gray-300 pl-4 mt-2 italic">
                    {{ Str::limit($email->body, 200) }}
                </blockquote>
            </div>
            
            <textarea name="body" rows="8" placeholder="Write your reply..." 
                      class="w-full resize-none text-sm focus:outline-none leading-relaxed border border-gray-200 rounded-xl p-4"></textarea>
        </form>
        
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
            <button type="submit" form="reply-form" 
                    class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white rounded-xl font-medium transition shadow-md">
                Send
            </button>
            <div class="flex items-center gap-2">
                <button class="p-2.5 text-gray-600 hover:bg-gray-200 rounded-lg transition" title="Attach files">
                    <i class="fas fa-paperclip"></i>
                </button>
                <button onclick="closeReplyModal()" class="p-2.5 text-gray-600 hover:bg-gray-200 rounded-lg transition" title="Discard">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openReplyModal(emailId) {
    document.getElementById('reply-modal').classList.remove('hidden');
}

function closeReplyModal() {
    if (confirm('Discard this reply?')) {
        document.getElementById('reply-modal').classList.add('hidden');
        document.getElementById('reply-form').reset();
    }
}
</script>
@endpush
@endsection
