@extends('layouts.mail')
@section('title', $email->subject)

@section('mail-content')
<div class="flex flex-col h-full bg-white select-none" x-data="showApp()">

    <!-- Toolbar Header (Gmail Style) -->
    <div class="shrink-0 border-b border-gray-100 px-6 py-4 flex items-center justify-between bg-white">
        <div class="flex items-center gap-3">
            <a href="{{ route('mail.inbox', ['folder' => request('folder', 'inbox')]) }}"
               class="p-2.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition" title="Back to Inbox">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>

            <div class="h-5 w-[1px] bg-gray-200 mx-1"></div>

            <!-- Asynchronous actions or standard forms stylized beautifully -->
            <form action="{{ route('mail.delete', $email->id) }}" method="POST" class="inline"
                  onsubmit="return confirm('{{ $email->folder === 'trash' ? 'Delete this secure message permanently?' : 'Move this secure message to trash?' }}')">
                @csrf
                <button type="submit" class="p-2.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition" title="Delete">
                    <i class="fas fa-trash-can text-sm"></i>
                </button>
            </form>

            <form action="{{ route('mail.move', $email->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="folder" value="{{ $email->folder === 'spam' ? 'inbox' : 'spam' }}">
                <button type="submit" class="p-2.5 text-gray-500 hover:text-yg-600 hover:bg-yg-50 rounded-xl transition"
                        title="{{ $email->folder === 'spam' ? 'Not spam' : 'Report spam' }}">
                    <i class="fas fa-circle-exclamation text-sm"></i>
                </button>
            </form>

            <!-- Star Action Form -->
            <form action="{{ route('mail.star', $email->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="p-2.5 text-gray-500 hover:text-amber-500 hover:bg-amber-50 rounded-xl transition" title="{{ $email->is_starred ? 'Unstar' : 'Star' }}">
                    <i class="text-sm {{ $email->is_starred ? 'fa-solid fa-star text-amber-500' : 'fa-regular fa-star' }}"></i>
                </button>
            </form>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-400">Secure Thread</span>
        </div>
    </div>

    <!-- Email Details Reading Pane -->
    <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
        
        <!-- Subject Header -->
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-2 flex-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl md:text-2xl font-extrabold font-title text-gray-900 tracking-tight leading-tight">
                        {{ $email->subject }}
                    </h1>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center gap-1 shrink-0 select-none">
                        <i class="fas fa-lock text-[8px]"></i> Encrypted
                    </span>
                </div>

                <!-- Labels if any -->
                @if(isset($email->labels) && $email->labels && count($email->labels))
                <div class="flex flex-wrap gap-1.5">
                    @foreach($email->labels as $label)
                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-yg-50 text-yg-700 border border-yg-100">
                        {{ $label }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Sentiment & Priority badges -->
            @if(isset($email->sentiment_label) || isset($email->priority))
            <div class="flex flex-wrap gap-1.5 justify-end shrink-0 select-none">
                @if(isset($email->sentiment_label) && $email->sentiment_label === 'positive')
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-100 flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-face-smile text-emerald-500"></i> Positive
                    </span>
                @elseif(isset($email->sentiment_label) && $email->sentiment_label === 'negative')
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-100 flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-face-frown text-rose-500"></i> Negative
                    </span>
                @elseif(isset($email->sentiment_label) && $email->sentiment_label === 'urgent')
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-100 flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-triangle-exclamation text-amber-500"></i> Urgent
                    </span>
                @endif

                @if(isset($email->priority) && $email->priority === 'high')
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-100 flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-flag text-rose-500"></i> High Priority
                    </span>
                @endif
            </div>
            @endif
        </div>

        <!-- Sender / Recipient Metadata Card (Gmail Details Header) -->
        <div class="flex items-start gap-4 p-5 bg-gray-50/50 rounded-2xl border border-gray-100">
            <!-- Initials Avatar with Gradient -->
            <div class="w-11 h-11 bg-gradient-to-br from-yg-500 to-yg-700 rounded-2xl flex items-center justify-center text-white font-bold text-base shadow-sm shrink-0 select-none">
                {{ strtoupper(substr($email->from, 0, 1)) }}
            </div>

            <!-- Address and Details -->
            <div class="flex-1 min-w-0 space-y-1">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-xs font-extrabold text-gray-900 font-title">
                            {{ explode('@', $email->from)[0] }}
                        </span>
                        <span class="text-[10px] text-gray-400 font-semibold">&lt;{{ $email->from }}&gt;</span>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400">
                        {{ $email->created_at->format('M j, Y \a\t g:i A') }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-4 flex-wrap pt-1">
                    <span class="text-[11px] text-gray-500 font-medium">
                        to <strong class="text-gray-700">{{ $email->to ?? 'me' }}</strong>
                    </span>

                    <!-- YG Pay Integration Button -->
                    <a href="https://pay.ygxone.com/user/send-money?credentials={{ $email->from }}"
                       target="_blank"
                       class="text-[10px] font-extrabold text-blue-600 hover:text-blue-700 flex items-center gap-1.5 bg-blue-50/80 px-3 py-1.5 rounded-xl transition border border-blue-100 shadow-sm">
                        <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                        <span>Send Money via YG PAY</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Security Notification Banner -->
        <div class="p-4 bg-emerald-50/40 border border-emerald-100 rounded-2xl flex items-center gap-3.5 select-none">
            <div class="w-8 h-8 rounded-full bg-emerald-100/60 flex items-center justify-center text-emerald-600 shrink-0">
                <i class="fas fa-shield text-xs"></i>
            </div>
            <div class="text-[11px] text-emerald-800 leading-relaxed font-semibold">
                This secure message was sealed with end-to-end encryption prior to dispatch. Only verified parties hold the private signatures to unlock this message body.
            </div>
        </div>

        <!-- Email Core Content Body -->
        <div class="prose max-w-none text-gray-800 leading-relaxed text-sm px-1 py-3 whitespace-pre-line font-medium">
            {!! nl2br(e($email->body)) !!}
        </div>

        <!-- Attachments Block -->
        @if($email->attachments && count($email->attachments))
        <div class="pt-6 border-t border-gray-100">
            <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2 select-none">
                <i class="fas fa-paperclip text-sm"></i>
                <span>{{ count($email->attachments) }} {{ Str::plural('Attachment', count($email->attachments)) }}</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($email->attachments as $attachment)
                <div class="flex items-center gap-3.5 p-3.5 border border-gray-200/80 rounded-2xl hover:bg-gray-50/60 transition cursor-pointer group relative">
                    <!-- Icon based on type -->
                    <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                        @php
                            $ext = pathinfo($attachment->file_name ?? 'file', PATHINFO_EXTENSION);
                            $icon = in_array($ext, ['pdf']) ? 'fa-file-pdf text-rose-500' :
                                   (in_array($ext, ['doc','docx']) ? 'fa-file-word text-blue-500' :
                                   (in_array($ext, ['xls','xlsx']) ? 'fa-file-excel text-emerald-500' :
                                   (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'fa-file-image text-purple-500' :
                                   (in_array($ext, ['zip','rar','7z']) ? 'fa-file-archive text-amber-500' :
                                   'fa-file text-gray-500'))));
                        @endphp
                        <i class="fas {{ $icon }} text-lg"></i>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0 pr-6">
                        <div class="text-xs font-bold text-gray-800 truncate leading-tight">{{ $attachment->file_name ?? 'Attachment' }}</div>
                        @if(isset($attachment->file_size))
                        <div class="text-[10px] text-gray-400 font-semibold mt-0.5">
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

                    <!-- Action Download Trigger -->
                    <a href="{{ route('mail.attachment.download', $attachment->id) }}"
                       class="p-2 text-gray-400 hover:text-yg-600 hover:bg-yg-50 rounded-xl transition absolute right-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100"
                       title="Download secured attachment">
                        <i class="fas fa-download text-xs"></i>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Reply / Forward Floating Triggers -->
        <div class="pt-8 border-t border-gray-100">
            <div class="flex items-center gap-3 mb-6 select-none" x-show="!showReply">
                <button @click="openReply()"
                        class="px-5 py-3 bg-white hover:bg-gray-50 border border-gray-200 shadow-sm rounded-2xl text-xs font-bold text-gray-700 transition flex items-center gap-2 outline-none">
                    <i class="fas fa-reply text-yg-600"></i> Reply
                </button>
                <button class="px-5 py-3 bg-white hover:bg-gray-50 border border-gray-200 shadow-sm rounded-2xl text-xs font-bold text-gray-700 transition flex items-center gap-2 outline-none">
                    <i class="fas fa-share text-gray-400"></i> Forward
                </button>
            </div>

            <!-- Inline Gmail-like Reply Compose Block -->
            <div x-show="showReply" x-transition x-cloak 
                 class="border border-gray-200/80 rounded-2xl overflow-hidden shadow-lg mt-2">
                <form method="POST" action="{{ route('mail.reply', $email->id) }}">
                    @csrf
                    <!-- Header banner -->
                    <div class="p-4 bg-gray-50/80 border-b border-gray-100 text-[10px] text-gray-400 font-bold uppercase tracking-wider flex items-center justify-between select-none">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-reply text-yg-600"></i>
                            <span>Reply to <strong class="text-gray-700">{{ $email->from }}</strong></span>
                        </span>
                        <button type="button" @click="showReply = false" class="p-1 text-gray-400 hover:text-gray-700 hover:bg-gray-200/50 rounded transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Editor Text Area -->
                    <div class="p-5 bg-white">
                        <textarea name="body" rows="8" placeholder="Type your secure, encrypted response here..."
                                  class="w-full resize-none text-xs leading-relaxed outline-none border-none focus:ring-0 focus:outline-none text-gray-800 placeholder-gray-400 font-medium"></textarea>
                    </div>

                    <!-- Controls Footer -->
                    <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 select-none">
                        <span class="text-[10px] font-bold text-gray-400 flex items-center gap-1">
                            <i class="fas fa-shield text-emerald-600 text-xs"></i> Locked Secure Response
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showReply = false" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-200/60 rounded-xl transition">
                                Discard
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 bg-yg-600 hover:bg-yg-700 text-white text-xs font-bold rounded-xl shadow-md shadow-yg-600/10 hover:shadow-lg transition flex items-center gap-2">
                                <i class="fas fa-paper-plane text-[10px]"></i> Send Response
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function showApp() {
    return {
        showReply: false,

        openReply() {
            this.showReply = !this.showReply;
        }
    };
}
</script>
@endpush
@endsection
