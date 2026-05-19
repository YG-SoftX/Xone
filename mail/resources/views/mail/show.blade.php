@extends('layouts.mail')
@section('title', $email->subject)

@section('mail-content')
<div class="flex flex-col h-full bg-white">

    {{-- Toolbar --}}
    <div class="shrink-0 border-b border-gray-200 px-4 py-3 flex items-center gap-3 bg-white">
        <a href="{{ route('mail.inbox', ['folder' => request('folder', 'inbox')]) }}"
           class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Back to Inbox">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="flex-1"></div>

        <form action="{{ route('mail.star', $email->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="p-2 text-gray-600 hover:bg-yellow-100 hover:text-yellow-600 rounded-lg transition" title="{{ $email->is_starred ? 'Unstar' : 'Star' }}">
                <i class="{{ $email->is_starred ? 'fas text-yellow-500' : 'far' }} fa-star"></i>
            </button>
        </form>

        <form action="{{ route('mail.delete', $email->id) }}" method="POST" class="inline"
              onsubmit="return confirm('{{ $email->folder === 'trash' ? 'Delete this email permanently?' : 'Move this email to trash?' }}')">
            @csrf
            <button type="submit" class="p-2 text-gray-600 hover:bg-red-100 hover:text-red-600 rounded-lg transition" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </form>

        <form action="{{ route('mail.move', $email->id) }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="folder" value="{{ $email->folder === 'spam' ? 'inbox' : 'spam' }}">
            <button type="submit" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                    title="{{ $email->folder === 'spam' ? 'Not spam' : 'Report spam' }}">
                <i class="fas fa-shield-halved"></i>
            </button>
        </form>
    </div>

    {{-- Email Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        {{-- Subject --}}
        <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $email->subject }}</h1>

        {{-- Labels --}}
        @if(isset($email->labels) && $email->labels && count($email->labels))
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
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-white font-bold shrink-0 shadow-sm">
                {{ strtoupper(substr($email->from, 0, 1)) }}
            </div>
            <div class="flex-1">
                <div class="flex items-baseline justify-between">
                    <div>
                        <span class="font-semibold text-gray-900">{{ $email->from }}</span>
                        @if($email->sender_name && $email->sender_name !== $email->from)
                        <span class="text-sm text-gray-500 ml-2">&lt;{{ $email->sender_name }}&gt;</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 whitespace-nowrap ml-4">
                        {{ $email->created_at->format('M j, Y g:i A') }}
                    </div>
                </div>
                <div class="text-sm text-gray-600 mt-1 flex items-center justify-between">
                    <span>to <strong>{{ $email->to ?? 'me' }}</strong></span>
                    <a href="https://pay.ygxone.com/user/send-money?credentials={{ $email->from }}"
                       target="_blank"
                       class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 bg-blue-50 px-2 py-1 rounded-md transition border border-blue-100">
                       <i class="fas fa-hand-holding-usd"></i> Send Money via YG PAY
                    </a>
                </div>
            </div>
        </div>

        {{-- Email body --}}
        <div class="prose max-w-none text-gray-800 leading-relaxed text-sm">
            {!! nl2br(e($email->body)) !!}
        </div>

        {{-- AI Sentiment & Priority Badges --}}
        @if(isset($email->sentiment_label) || isset($email->priority))
        <div class="mt-6 flex flex-wrap gap-2">
            @if(isset($email->sentiment_label) && $email->sentiment_label === 'positive')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                    <i class="fas fa-smile mr-1.5"></i> Positive
                </span>
            @elseif(isset($email->sentiment_label) && $email->sentiment_label === 'negative')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                    <i class="fas fa-frown mr-1.5"></i> Negative
                </span>
            @elseif(isset($email->sentiment_label) && $email->sentiment_label === 'urgent')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200">
                    <i class="fas fa-exclamation-triangle mr-1.5"></i> Urgent
                </span>
            @endif

            @if(isset($email->priority) && $email->priority === 'high')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                    <i class="fas fa-flag mr-1.5"></i> High Priority
                </span>
            @endif
        </div>
        @endif

        {{-- Attachments --}}
        @if($email->attachments && count($email->attachments))
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-paperclip text-gray-400"></i>
                {{ count($email->attachments) }} {{ Str::plural('Attachment', count($email->attachments)) }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($email->attachments as $attachment)
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition cursor-pointer group">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                        @php
                            $ext = pathinfo($attachment->file_name ?? 'file', PATHINFO_EXTENSION);
                            $icon = in_array($ext, ['pdf']) ? 'fa-file-pdf text-red-500' :
                                   (in_array($ext, ['doc','docx']) ? 'fa-file-word text-blue-500' :
                                   (in_array($ext, ['xls','xlsx']) ? 'fa-file-excel text-green-500' :
                                   (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'fa-file-image text-purple-500' :
                                   (in_array($ext, ['zip','rar','7z']) ? 'fa-file-archive text-yellow-500' :
                                   'fa-file text-gray-500'))));
                        @endphp
                        <i class="fas {{ $icon }} text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $attachment->file_name ?? 'Attachment' }}</div>
                        @if(isset($attachment->file_size))
                        <div class="text-xs text-gray-500">
                            @if($attachment->file_size > 1048576)
                                {{ round($attachment->file_size / 1048576, 1) }} MB
                            @elseif($attachment->file_size > 1024)
                                {{ round($attachment->file_size / 1024, 0) }} KB
                            @else
                                {{ $attachment->file_size }} B
                            @endif
                        </div>
                        @endif
                    </div>
                    <a href="{{ route('mail.attachment.download', $attachment->id) }}"
                       class="p-2 text-gray-600 hover:bg-gray-200 rounded-lg transition opacity-0 group-hover:opacity-100"
                       title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Reply area --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex items-center gap-3 mb-6">
                <button @click="openReply()"
                        class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2 shadow-sm">
                    <i class="fas fa-reply"></i> Reply
                </button>
                <button class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition flex items-center gap-2 shadow-sm">
                    <i class="fas fa-share"></i> Forward
                </button>
            </div>

            {{-- Reply form --}}
            <div x-show="showReply" x-cloak class="border border-gray-200 rounded-xl overflow-hidden">
                <form method="POST" action="{{ route('mail.reply', $email->id) }}">
                    @csrf
                    <div class="p-4 bg-gray-50 border-b border-gray-200 text-xs text-gray-500 flex items-center gap-2">
                        <i class="fas fa-reply"></i>
                        Reply to <strong class="text-gray-700">{{ $email->from }}</strong>
                    </div>
                    <div class="p-4">
                        <textarea name="body" rows="6" placeholder="Write your reply..."
                                  class="w-full resize-none text-sm leading-relaxed focus:outline-none text-gray-900 placeholder-gray-400"></textarea>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <span class="text-xs text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i> Encrypted
                        </span>
                        <button type="submit"
                                class="px-5 py-2 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white rounded-xl text-sm font-medium transition shadow-md">
                            <i class="fas fa-paper-plane mr-1.5"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mailLayout() {
    return {
        ...window.mailLayout ? window.mailLayout() : {},
        showReply: false,

        openReply() {
            this.showReply = !this.showReply;
        }
    };
}
</script>
@endpush
@endsection
