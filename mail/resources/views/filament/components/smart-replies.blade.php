<div class="space-y-2">
    <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
        <x-heroicon-m-sparkles class="w-4 h-4 text-info-500"/>
        <span>YG-AI Smart Replies</span>
    </div>
    
    <div class="flex flex-wrap gap-2">
        @foreach($replies as $reply)
            <button 
                type="button"
                wire:click="$set('body', '{{ addslashes($reply) }}')"
                class="px-4 py-2 text-sm bg-info-50 dark:bg-info-900/30 text-info-700 dark:text-info-300 rounded-full border border-info-200 dark:border-info-800 hover:bg-info-100 dark:hover:bg-info-800/50 transition-all text-left max-w-xs truncate"
                title="{{ $reply }}"
            >
                {{ $reply }}
            </button>
        @endforeach
    </div>
</div>
