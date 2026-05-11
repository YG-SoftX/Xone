<div class="p-4 space-y-6">
    @if(isset($error))
        <div class="bg-red-900/20 border border-red-500/50 p-4 rounded-2xl text-red-400 text-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Access Error
            </div>
            {{ $error }}
        </div>
    @else
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 border border-amber-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold tracking-tight text-white">{{ $file->name }}</h3>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Local Intelligence Brief</p>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-amber-500/20 rounded-full"></div>
            <div class="pl-6 text-sm leading-relaxed text-gray-300 italic">
                {!! nl2br(e($summary)) !!}
            </div>
        </div>

        <div class="pt-6 border-t border-white/5 flex justify-between items-center text-[10px] text-gray-500 font-bold uppercase tracking-tighter">
            <span>Model: YugaLM Sovereign</span>
            <span>Generated on Local Node</span>
        </div>
    @endif
</div>
