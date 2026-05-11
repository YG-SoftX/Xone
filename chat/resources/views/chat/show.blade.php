@extends('layouts.app')

@section('title', $space->isDm() ? $space->displayName(auth()->id()) : '# ' . $space->name)

@section('chat-content')
<div class="flex flex-col h-full bg-[#020617] text-white select-none" x-data="chatApp({{ $space->id }}, {{ $messages->flatten()->last()?->id ?? 0 }})">

    {{-- ── Sovereign Space Header ────────────────────────────────────────── --}}
    <div class="shrink-0 flex items-center gap-6 px-8 h-20 bg-white/[0.02] backdrop-blur-2xl border-b border-white/5 z-10">
        <div class="relative group">
            @if($space->isDm())
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-lg font-black shadow-lg border border-white/10 group-hover:scale-110 transition-transform">
                    {{ strtoupper(substr($space->displayName(auth()->id()), 0, 1)) }}
                </div>
            @else
                <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-teal-400 font-black shadow-lg border border-white/10 group-hover:scale-110 transition-transform">
                    <i class="fas fa-hashtag text-xl"></i>
                </div>
            @endif
            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-[#020617] rounded-full animate-pulse"></div>
        </div>
        
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-black text-white truncate tracking-tight uppercase">
                {{ $space->isDm() ? $space->displayName(auth()->id()) : $space->name }}
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-teal-400 font-black uppercase tracking-widest">{{ $space->isDm() ? __('Direct Signal') : __('Sovereign Space') }}</span>
                @if(!$space->isDm() && $space->description)
                    <span class="w-1 h-1 bg-gray-700 rounded-full"></span>
                    <p class="text-[10px] text-gray-500 truncate font-bold uppercase tracking-widest">{{ $space->description }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-xl">
                <i class="fas fa-users text-[10px] text-gray-500"></i>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $space->members->count() }} Members</span>
            </div>
            <button class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>

    {{-- ── Secure Message Feed ────────────────────────────────────────────── --}}
    <div id="messages-container" class="flex-1 overflow-y-auto px-8 md:px-12 py-8 space-y-6 scroll-smooth bg-black/10">

        @forelse($messages as $date => $dayMessages)
            <div class="flex items-center gap-6 my-12">
                <div class="flex-1 h-px bg-white/5"></div>
                <span class="text-[10px] font-black text-gray-600 bg-[#020617] px-6 py-2 rounded-full border border-white/5 uppercase tracking-[0.3em]">
                    {{ \Illuminate\Support\Carbon::parse($date)->isToday() ? 'Today' : (\Illuminate\Support\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Illuminate\Support\Carbon::parse($date)->format('M d, Y')) }}
                </span>
                <div class="flex-1 h-px bg-white/5"></div>
            </div>

            @foreach($dayMessages as $message)
                @php
                    $isMe = $message->user_id === auth()->id();
                    $showAvatar = !isset($prevUserId) || $prevUserId !== $message->user_id;
                    $prevUserId = $message->user_id;
                @endphp
                <div class="flex gap-4 group relative animate-fade-in {{ $isMe ? 'flex-row-reverse' : '' }}" id="msg-{{ $message->id }}">
                    
                    @if(!$isMe)
                        <div class="shrink-0 w-10">
                            @if($showAvatar)
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-black shadow-lg border border-white/10">
                                    {{ strtoupper(substr($message->user?->name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex-1 max-w-[75%] min-w-0 {{ $isMe ? 'items-end flex flex-col' : '' }}">
                        @if($showAvatar && !$isMe)
                            <div class="flex items-baseline gap-3 mb-2 px-1">
                                <span class="text-xs font-black text-white uppercase tracking-tight">{{ $message->user?->name }}</span>
                                <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest">{{ $message->created_at->format('H:i') }}</span>
                            </div>
                        @endif

                        <div class="relative group/bubble">
                            <div class="px-6 py-4 rounded-[2rem] text-sm leading-relaxed border transition-all
                                 {{ $isMe ? 'bg-teal-500/10 border-teal-500/20 text-white rounded-tr-sm' : 'bg-white/5 border-white/10 text-gray-300 rounded-tl-sm' }}"
                                 style="white-space:pre-wrap;word-break:break-word">
                                {{ $message->body }}
                            </div>

                            {{-- Actions overlay --}}
                            <div class="absolute -top-4 {{ $isMe ? 'right-4' : 'left-4' }} opacity-0 group-hover/bubble:opacity-100 transition-all flex items-center gap-1 bg-white/5 backdrop-blur-2xl border border-white/10 rounded-xl p-1 shadow-2xl">
                                <button onclick="setReplyTo({{ $message->id }}, '{{ e(Str::limit($message->body, 40)) }}')" class="p-2 rounded-lg hover:bg-white/10 text-gray-500 hover:text-white transition-all">
                                    <i class="fas fa-reply text-[10px]"></i>
                                </button>
                                <button onclick="showReactionPicker({{ $message->id }})" class="p-2 rounded-lg hover:bg-white/10 text-gray-500 hover:text-yellow-500 transition-all">
                                    <i class="fas fa-face-smile text-[10px]"></i>
                                </button>
                                @if($isMe)
                                    <button onclick="deleteMsg({{ $message->id }})" class="p-2 rounded-lg hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($isMe)
                            <div class="mt-2 px-1">
                                <span class="text-[9px] text-gray-700 font-bold uppercase tracking-widest">{{ $message->created_at->format('H:i') }} • Signal Delivered</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @empty
            <div class="flex flex-col items-center justify-center py-32 bg-white/[0.02] border border-white/5 rounded-[3rem] border-dashed">
                <div class="w-20 h-20 bg-white/5 rounded-[2rem] flex items-center justify-center mb-6">
                    <i class="fas fa-comments text-3xl text-gray-700"></i>
                </div>
                <h3 class="text-xl font-black text-gray-500 uppercase tracking-tight mb-2">No Dialogue Artifacts</h3>
                <p class="text-gray-600 text-[10px] uppercase tracking-widest">Initialize the first signal in this node.</p>
            </div>
        @endforelse

        <div id="live-messages" class="space-y-6"></div>
    </div>

    {{-- ── Reply Signal Indicator ────────────────────────────────────────── --}}
    <div id="reply-banner" class="hidden shrink-0 bg-teal-500/5 border-t border-teal-500/20 px-8 py-4 flex items-center gap-6">
        <i class="fas fa-reply text-teal-500"></i>
        <div class="flex-1 min-w-0">
            <p class="text-[10px] text-teal-400 font-black uppercase tracking-widest mb-1">Targeting Signal:</p>
            <span class="text-xs text-gray-400 truncate block font-bold" id="reply-preview"></span>
        </div>
        <button onclick="clearReply()" class="w-8 h-8 rounded-lg hover:bg-white/5 text-gray-500 hover:text-white transition-all">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- ── Message Input Console ────────────────────────────────────────── --}}
    <div class="shrink-0 p-8 pt-0">
        <form id="send-form" method="POST" action="{{ route('chat.send', $space->id) }}" enctype="multipart/form-data"
              @submit.prevent="sendMessage" class="flex items-end gap-4">
            @csrf
            <input type="hidden" name="reply_to_id" id="reply-to-input">

            <div class="flex-1 premium-glass rounded-[2rem] overflow-hidden focus-within:border-teal-500/50 transition-all p-2">
                <textarea name="body" id="message-input" rows="1" 
                          placeholder="Transmit signal to {{ $space->isDm() ? $space->displayName(auth()->id()) : '#' . $space->name }}..."
                          class="w-full bg-transparent px-6 py-4 text-sm text-white placeholder-gray-600 focus:outline-none resize-none max-h-48"
                          @keydown.enter.exact.prevent="sendMessage"></textarea>
                
                <div class="flex items-center justify-between px-4 pb-2 border-t border-white/5 pt-4 mt-2">
                    <div class="flex items-center gap-2">
                        <label class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/5 text-gray-500 hover:text-teal-400 transition-all cursor-pointer">
                            <i class="fas fa-paperclip text-sm"></i>
                            <input type="file" name="file" class="sr-only">
                        </label>
                        <button type="button" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/5 text-gray-500 hover:text-yellow-500 transition-all">
                            <i class="fas fa-face-smile text-sm"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-teal-600 to-blue-600 text-white rounded-xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-teal-600/20 hover:scale-105 active:scale-95 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Transmit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Reaction Module (Standalone) --}}
<div id="emoji-picker" class="hidden fixed bottom-32 right-12 bg-[#0a0f1e] border border-white/10 rounded-3xl shadow-2xl p-6 z-50 backdrop-blur-3xl animate-fade-in">
    <div class="grid grid-cols-4 gap-3">
        @foreach(['👍','❤️','😂','😮','😢','🎉','🔥','👏','✅','❌','⭐','💯','🚀','💡','🙏','😊'] as $emoji)
        <button onclick="selectEmoji('{{ $emoji }}')" 
                class="w-10 h-10 hover:bg-white/5 rounded-xl text-xl transition-all flex items-center justify-center border border-transparent hover:border-white/10">{{ $emoji }}</button>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
let currentReactionMsgId = null;

function chatApp(spaceId, lastId) {
    return {
        lastId,
        spaceId,
        init() {
            this.scrollBottom();
            this.startPolling();
        },
        scrollBottom() {
            const c = document.getElementById('messages-container');
            if (c) setTimeout(() => c.scrollTop = c.scrollHeight, 100);
        },
        startPolling() {
            const poll = async () => {
                try {
                    const res = await fetch(`/s/${this.spaceId}/poll?since=${this.lastId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.messages && data.messages.length > 0) {
                        this.appendMessages(data.messages);
                        this.lastId = data.last_id;
                    }
                } catch (e) {}
                setTimeout(() => poll(), 3000);
            };
            setTimeout(() => poll(), 3000);
        },
        appendMessages(messages) {
            const container = document.getElementById('live-messages');
            messages.forEach(msg => {
                const div = document.createElement('div');
                const isMe = msg.user_id == {{ auth()->id() }};
                div.className = `flex gap-4 my-6 ${isMe ? 'flex-row-reverse' : ''} animate-fade-in`;
                const safeName = (msg.user_name?.[0]?.toUpperCase() ?? '?');
                const safeBody = msg.body
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                div.innerHTML = `
                    ${!isMe ? `<div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-black shadow-lg border border-white/10">
                        ${safeName}
                    </div>` : ''}
                    <div class="flex-1 max-w-[75%] min-w-0 ${isMe ? 'items-end flex flex-col' : ''}">
                        <div class="px-6 py-4 rounded-[2rem] text-sm leading-relaxed border transition-all ${isMe ? 'bg-teal-500/10 border-teal-500/20 text-white rounded-tr-sm' : 'bg-white/5 border-white/10 text-gray-300 rounded-tl-sm'}">${safeBody}</div>
                    </div>`;
                container.appendChild(div);
            });
            this.scrollBottom();
        },
        sendMessage() {
            const form = document.getElementById('send-form');
            const input = document.getElementById('message-input');
            const body = input.value.trim();
            if (!body) return;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            }).then(r => r.json()).then(data => {
                input.value = '';
                clearReply();
                const safeBody = body
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                this.appendMessages([{
                    id: data.message.id,
                    user_id: {{ auth()->id() }},
                    user_name: '{{ e(auth()->user()->name) }}',
                    body: safeBody,
                    created_at: new Date().toISOString(),
                }]);
                this.lastId = data.message.id;
            }).catch(() => form.submit());
        }
    };
}

function setReplyTo(id, preview) {
    document.getElementById('reply-to-input').value = id;
    document.getElementById('reply-preview').textContent = preview;
    document.getElementById('reply-banner').classList.remove('hidden');
    document.getElementById('message-input').focus();
}

function clearReply() {
    document.getElementById('reply-to-input').value = '';
    document.getElementById('reply-banner').classList.add('hidden');
}

function deleteMsg(id) {
    if (!confirm('Purge this signal?')) return;
    fetch(`/messages/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => location.reload());
}

function showReactionPicker(msgId) {
    currentReactionMsgId = msgId;
    document.getElementById('emoji-picker').classList.toggle('hidden');
}

function selectEmoji(emoji) {
    if (currentReactionMsgId) {
        fetch(`/messages/${currentReactionMsgId}/react`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ emoji }),
        }).then(() => location.reload());
    }
}
</script>
@endpush
@endsection
