@extends('layouts.app')

@section('title', 'Sovereign Society — YGXONE')

@section('content')
<div class="p-8 md:p-12" x-data="{ 
    showLightbox: false, 
    lightboxImage: '',
    showReportModal: false,
    showGiftModal: false,
    targetUserId: null,
    targetUserName: '',
    reportingPostId: null,
    openLightbox(img) {
        this.lightboxImage = img;
        this.showLightbox = true;
    },
    openReportModal(postId) {
        this.reportingPostId = postId;
        this.showReportModal = true;
    },
    openGiftModal(userId, userName) {
        this.targetUserId = userId;
        this.targetUserName = userName;
        this.showGiftModal = true;
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
        
        {{-- Sidebar Left: Sovereign Identity --}}
        <div class="hidden lg:block lg:col-span-1 space-y-8">
            <div class="bg-white/[0.02] backdrop-blur-3xl p-10 rounded-[3rem] border border-white/5 text-center shadow-2xl">
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <div class="w-full h-full rounded-3xl border-2 border-blue-500/30 p-1 overflow-hidden">
                        <img src="{{ Auth::user()->userImage }}" class="w-full h-full object-cover rounded-2xl">
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 border-4 border-[#020617] rounded-full"></div>
                </div>
                
                <h3 class="text-xl font-black text-white tracking-tight flex items-center justify-center gap-2 mb-1 uppercase">
                    {{ Auth::user()->name }}
                    <span class="text-blue-500 text-xs"><i class="fas fa-certificate"></i></span>
                </h3>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mb-8">{{ Auth::user()->username ?? '@SOVEREIGN' }}</p>
                
                <div class="grid grid-cols-3 gap-4 pt-8 border-t border-white/5">
                    <div>
                        <p class="text-lg font-black text-blue-400" id="my-stones">{{ Auth::user()->stones }}</p>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-600">Stones</p>
                    </div>
                    <div>
                        <p class="text-lg font-black text-white">0</p>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-600">Echoes</p>
                    </div>
                    <div>
                        <p class="text-lg font-black text-white">0</p>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-600">Nodes</p>
                    </div>
                </div>
            </div>

            <nav class="p-8 bg-white/[0.02] backdrop-blur-3xl rounded-[3rem] border border-white/5 shadow-2xl">
                <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-700 mb-6">Social Discovery</h4>
                <div class="space-y-4">
                    <a href="{{ route('society.index', ['filter' => 'trending']) }}" class="flex items-center gap-4 p-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all {{ request('filter') === 'trending' ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-gray-500 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-fire-flame-curved"></i> Trending Signal
                    </a>
                    <a href="{{ route('society.index') }}" class="flex items-center gap-4 p-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all {{ !request('filter') ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-gray-500 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-bolt"></i> Live Pulse
                    </a>
                </div>
            </nav>
        </div>

        {{-- Main Feed: The Pulse --}}
        <div class="lg:col-span-2 space-y-10">
            
            {{-- Create Signal --}}
            <div class="bg-white/[0.03] backdrop-blur-3xl p-10 rounded-[3.5rem] border border-white/5 shadow-2xl" x-data="{ expanded: false }">
                <div class="flex items-center gap-6 mb-8">
                    <div class="w-14 h-14 rounded-2xl overflow-hidden shadow-xl border border-white/10">
                        <img src="{{ Auth::user()->userImage }}" class="w-full h-full object-cover">
                    </div>
                    <button @click="expanded = true" class="flex-1 bg-white/5 text-left px-8 py-5 rounded-[2rem] text-gray-500 font-black text-xs uppercase tracking-widest hover:bg-white/10 transition-all border border-white/5">
                        Broadcast a new signal...
                    </button>
                </div>

                <div x-show="expanded" x-transition class="pt-8 border-t border-white/5">
                    <form action="{{ route('society.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <textarea name="content" class="w-full bg-transparent border-none focus:ring-0 text-xl font-black text-white placeholder-gray-800 mb-8 resize-none" rows="4" placeholder="What is the current pulse?"></textarea>
                        
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <label class="w-12 h-12 bg-white/5 text-gray-400 rounded-2xl flex items-center justify-center cursor-pointer hover:bg-blue-600 hover:text-white transition-all border border-white/10">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" name="media" class="hidden">
                                </label>
                                <select name="flair" class="bg-white/5 border border-white/10 rounded-2xl px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400 focus:ring-2 focus:ring-blue-500/50 focus:bg-white/10 outline-none transition-all">
                                    <option value="">No Protocol</option>
                                    <option value="Announcement">📢 Protocol Signal</option>
                                    <option value="Discussion">💬 Open Dialogue</option>
                                    <option value="Media">🎨 Artifact</option>
                                    <option value="Project">🚀 Deployment</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <button type="button" @click="expanded = false" class="px-8 py-4 text-gray-600 font-black uppercase tracking-widest text-[10px]">Cancel</button>
                                <button type="submit" class="px-12 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-[10px]">Broadcast Signal</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Signal Stream --}}
            @foreach($posts as $post)
                <div class="bg-white/[0.02] backdrop-blur-3xl rounded-[3.5rem] border border-white/5 shadow-2xl overflow-hidden group hover:border-blue-500/20 transition-all">
                    <div class="p-10 flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden shadow-xl border border-white/10">
                                <img src="{{ $post->user->userImage }}" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white tracking-tight flex items-center gap-3 uppercase">
                                    {{ $post->user->name }}
                                    <span class="text-blue-500 text-[10px]"><i class="fas fa-shield-check"></i></span>
                                    @if($post->flair)
                                        <span class="px-3 py-1 bg-white/5 text-blue-400 rounded-lg text-[8px] font-black uppercase tracking-widest border border-white/5">{{ $post->flair }}</span>
                                    @endif
                                </h4>
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600 mt-1">{{ $post->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @if($post->user_id !== Auth::id())
                                <button @click="openGiftModal({{ $post->user_id }}, '{{ $post->user->name }}')" class="w-11 h-11 bg-white/5 text-blue-400 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all border border-white/5 shadow-lg">
                                    <i class="fas fa-gem text-xs"></i>
                                </button>
                            @endif
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="w-11 h-11 text-gray-700 hover:text-white transition-all"><i class="fas fa-ellipsis-v"></i></button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-4 w-56 bg-[#0a0f1e] rounded-2xl shadow-2xl border border-white/10 p-2 z-10" x-cloak x-transition>
                                    @if($post->user_id === Auth::id())
                                        <form action="{{ route('society.destroy', $post) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-full text-left px-5 py-3 text-red-500 font-black text-[10px] uppercase tracking-widest hover:bg-red-500/10 rounded-xl transition-all">Purge Signal</button>
                                        </form>
                                    @else
                                        <button @click="openReportModal({{ $post->id }})" class="w-full text-left px-5 py-3 text-orange-500 font-black text-[10px] uppercase tracking-widest hover:bg-orange-500/10 rounded-xl transition-all">Alert Guardians</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($post->content)
                        <div class="px-10 pb-8">
                            <p class="text-gray-300 text-lg leading-relaxed font-bold tracking-tight">{{ $post->content }}</p>
                        </div>
                    @endif

                    @if($post->media_url)
                        <div class="px-10 pb-10">
                            <div class="rounded-[2.5rem] overflow-hidden shadow-2xl border border-white/10 bg-black/20 group-hover:border-blue-500/30 transition-all">
                                @if($post->media_type == 'image')
                                    <img src="{{ asset('storage/' . $post->media_url) }}" 
                                         @click="openLightbox('{{ asset('storage/' . $post->media_url) }}')"
                                         class="w-full h-auto cursor-zoom-in hover:scale-105 transition duration-1000 ease-out">
                                @elseif($post->media_type == 'video')
                                    <video src="{{ asset('storage/' . $post->media_url) }}" 
                                           class="w-full h-auto society-video" 
                                           loop muted playsinline controls></video>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="px-10 py-8 bg-white/[0.01] border-t border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-10">
                            <button onclick="toggleLike(this, {{ $post->id }})" class="flex items-center gap-3 font-black uppercase tracking-[0.2em] text-[10px] transition-all {{ $post->isLikedBy(Auth::user()) ? 'text-blue-500' : 'text-gray-600 hover:text-white' }}">
                                <i class="fas fa-bolt text-lg"></i>
                                <span>{{ $post->likes_count }} Echoes</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="pt-12">
                {{ $posts->links() }}
            </div>
        </div>

        {{-- Sidebar Right: High Council --}}
        <div class="hidden lg:block lg:col-span-1 space-y-8">
            <div class="bg-white/[0.02] backdrop-blur-3xl p-10 rounded-[3rem] border border-white/5 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-400 border border-blue-500/20">
                        <i class="fas fa-crown text-sm"></i>
                    </div>
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-white">Sovereign Council</h4>
                </div>
                
                <div class="space-y-8">
                    @foreach($leaderboard as $member)
                        <div class="flex items-center justify-between group cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white/5 overflow-hidden shadow-lg border border-white/5 group-hover:border-blue-500/50 transition-all p-0.5">
                                    <img src="{{ $member->userImage }}" class="w-full h-full object-cover rounded-lg">
                                </div>
                                <div>
                                    <p class="text-[11px] font-black text-white uppercase tracking-tight group-hover:text-blue-400 transition-colors">{{ $member->name }}</p>
                                    <p class="text-[9px] font-black uppercase tracking-widest text-blue-500/70">{{ $member->stones }} Stones</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Stone Gift Modal (Cinematic Overhaul) --}}
    <div x-show="showGiftModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-3xl p-4" x-cloak x-transition>
        <div class="bg-[#0a0f1e] max-w-md w-full rounded-[3.5rem] shadow-2xl p-12 border border-white/10 relative overflow-hidden" @click.away="showGiftModal = false">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-600 shadow-xl shadow-blue-500/50"></div>
            <h3 class="text-3xl font-black text-white mb-3 tracking-tighter uppercase">Reward Merit</h3>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-10 leading-relaxed text-center">Transferring stones to <span class="text-blue-400" x-text="targetUserName"></span></p>

            <form @submit.prevent="const res = await fetch('{{ route('society.transfer') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ recipient_id: targetUserId, amount: $refs.amount.value })
            }); const data = await res.json(); if(data.success) { location.reload(); } else { alert(data.message); }">
                <div class="space-y-8">
                    <div class="relative">
                        <input type="number" x-ref="amount" class="w-full bg-white/5 border border-white/10 rounded-2xl py-6 px-8 text-2xl font-black text-white focus:border-blue-500 outline-none text-center" placeholder="00" required min="1">
                        <div class="absolute inset-y-0 right-8 flex items-center pointer-events-none">
                            <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">STONES</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4">
                        <button type="submit" class="py-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-blue-600/30 hover:scale-[1.02] active:scale-95 transition-all">Confirm Transfer</button>
                        <button type="button" @click="showGiftModal = false" class="py-4 font-black text-gray-700 uppercase tracking-widest text-[10px] hover:text-white transition-all">Abort Protocol</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
async function toggleLike(btn, postId) {
    try {
        const res = await fetch(`/society/posts/${postId}/like`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        const span = btn.querySelector('span');
        span.innerText = `${data.count} Echoes`;
        if (data.status === 'liked') {
            btn.classList.add('text-blue-500');
            btn.classList.remove('text-gray-600');
        } else {
            btn.classList.remove('text-blue-500');
            btn.classList.add('text-gray-600');
        }
    } catch (e) { console.error('Signal Error'); }
}
</script>
@endsection
