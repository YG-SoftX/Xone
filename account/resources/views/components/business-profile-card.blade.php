<!-- Google-Style Business Profile Card (People Card) -->
<div x-data="{ open: false }" 
     @mouseenter="open = true" 
     @mouseleave="open = false" 
     class="relative inline-block">
    
    <!-- Trigger (usually the name or avatar) -->
    <slot name="trigger"></slot>

    <!-- The Hover Card -->
    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute z-[300] w-72 bg-white border border-gray-200 rounded-3xl shadow-2xl p-6 -left-4 mt-2 animate-fade-in">
        
        <!-- Header -->
        <div class="flex items-center gap-4">
            <div class="relative shrink-0">
                @php
                    $frameClass = '';
                    $orgFrame = auth()->user()->organization->avatar_frame ?? 'default';
                    if ($orgFrame === 'gold') $frameClass = 'border-yellow-400 shadow-[0_0_10px_rgba(250,204,21,0.5)]';
                    elseif ($orgFrame === 'brand') $frameClass = 'border-brand shadow-[0_0_10px_rgba(var(--brand-primary-rgb),0.3)]';
                    elseif ($orgFrame === 'silver') $frameClass = 'border-gray-300 shadow-sm';
                @endphp
                <img :src="user.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=f1f3f4&color=5f6368'" 
                     class="w-12 h-12 rounded-full border-2 {{ $frameClass }} transition-transform group-hover:scale-105" 
                     alt="">
                <template x-if="user.status === 'online'">
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full shadow-sm"></span>
                </template>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-gray-900 truncate" x-text="user.name"></h4>
                
                <!-- Custom Role Badge (Sovereign Identity) -->
                <div class="flex items-center gap-1.5 mt-1">
                    <span :class="user.role_color || 'bg-gray-100 text-gray-600'" 
                          class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest border border-black/5 flex items-center gap-1">
                        <i :class="user.role_icon || 'fas fa-user'" class="text-[7px]"></i>
                        <span x-text="user.role || 'Staff Member'"></span>
                    </span>
                </div>

                <p class="text-[10px] text-brand font-bold mt-1" x-text="user.department || 'YG Organization'"></p>
            </div>
        </div>

        <!-- Contact Actions -->
        <div class="flex justify-between items-center gap-2 mb-6">
            <button class="flex-1 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-600 p-2.5 rounded-xl transition-colors group">
                <i class="fas fa-envelope text-sm"></i>
            </button>
            <button class="flex-1 bg-gray-50 hover:bg-green-50 text-gray-600 hover:text-green-600 p-2.5 rounded-xl transition-colors">
                <i class="fas fa-comments text-sm"></i>
            </button>
            <button class="flex-1 bg-gray-50 hover:bg-purple-50 text-gray-600 hover:text-purple-600 p-2.5 rounded-xl transition-colors">
                <i class="fas fa-video text-sm"></i>
            </button>
            <button class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 p-2.5 rounded-xl transition-colors">
                <i class="fas fa-calendar-plus text-sm"></i>
            </button>
        </div>

        <!-- Footer -->
        <div class="pt-4 border-t border-gray-100">
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <i class="fas fa-building text-gray-300"></i>
                <span x-text="user.org_name || 'Imperial Organization'"></span>
            </div>
            <a href="#" class="mt-4 block text-center text-[10px] font-bold text-blue-600 uppercase tracking-widest hover:underline">View full profile</a>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 0.2s ease-out; }
</style>
