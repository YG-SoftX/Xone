<div class="google-card border-brand/30 bg-brand/[0.02] !p-6 mb-8 relative overflow-hidden group">
    <!-- Conquest Background Accent -->
    <div class="absolute right-0 top-0 bottom-0 w-2 bg-brand/10 group-hover:bg-brand transition-all"></div>
    
    <div class="flex items-start gap-5">
        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm border border-brand/10 shrink-0">
            <i class="fas fa-exchange-alt text-brand"></i>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-0.5 bg-brand/10 text-brand rounded text-[9px] font-black uppercase tracking-widest border border-brand/20">
                    {{ $badge ?? 'Sovereign Alternative' }}
                </span>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Sponsored by YG Xone</span>
            </div>
            <h3 class="text-xl font-normal text-brand mb-2 group-hover:underline">{{ $title }}</h3>
            <p class="text-[14px] text-gray-600 leading-relaxed mb-6">{{ $content }}</p>
            
            <a href="{{ $link }}" class="inline-flex items-center gap-2 px-6 py-2 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all shadow-md">
                Switch to Sovereignty <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
