<!-- Google-Style Notification Center -->
<div x-data="notificationCenter()" class="relative">
    <!-- Bell Trigger -->
    <button @click="open = !open; if(open) fetchNotifications()" 
            class="p-2.5 hover:bg-gray-100 rounded-full transition relative group">
        <i class="far fa-bell text-gray-600 text-xl group-hover:text-blue-600"></i>
        <template x-if="unreadCount > 0">
            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </template>
    </button>

    <!-- Notification Dropdown -->
    <div x-show="open" 
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded-3xl shadow-2xl z-[200] overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-sm font-bold text-gray-700">Notifications</h3>
            <button @click="markAllAsRead" class="text-[10px] font-bold text-blue-600 uppercase tracking-widest hover:underline">Mark all as read</button>
        </div>

        <!-- Staff Milestones (Sovereign Broadcast) -->
        @php
            $orgId = auth()->user()->organization_id;
            $milestones = $orgId
                ? \Illuminate\Support\Facades\Cache::remember(
                    "milestones_{$orgId}_" . now()->format('m-d'),
                    3600,
                    fn () => \App\Models\User::where('organization_id', $orgId)
                        ->whereNotNull('joined_at')
                        ->whereRaw("DATE_FORMAT(joined_at, '%m-%d') = ?", [now()->format('m-d')])
                        ->where('id', '!=', auth()->id())
                        ->get()
                )
                : collect();
        @endphp

        @if($milestones->count() > 0)
            <div class="px-6 py-4 bg-brand/5 border-b border-gray-100">
                <h4 class="text-[10px] font-bold text-brand uppercase tracking-widest mb-3 flex items-center gap-2">
                    <i class="fas fa-award"></i> Imperial Milestones
                </h4>
                @foreach($milestones as $staff)
                <div class="flex items-center gap-3 mb-3 last:mb-0 group cursor-pointer">
                    <div class="relative">
                        <img src="{{ $staff->user_image }}" class="w-8 h-8 rounded-full border-2 border-white shadow-sm" alt="">
                        <div class="absolute -right-1 -bottom-1 w-4 h-4 bg-yellow-400 rounded-full border-2 border-white flex items-center justify-center">
                            <i class="fas fa-star text-[6px] text-white"></i>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-gray-900 leading-tight">
                            <strong>{{ $staff->name }}</strong> is celebrating <strong>{{ now()->diffInYears($staff->joined_at) }} Years</strong>!
                        </p>
                        <p class="text-[9px] text-brand font-bold uppercase tracking-tighter mt-0.5">Sovereign Salute</p>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <!-- Notification List -->
        <div class="max-h-[400px] overflow-y-auto divide-y divide-gray-50">
            <template x-for="n in notifications" :key="n.id">
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors flex gap-4 cursor-pointer" @click="if(n.action_url) window.location.href = n.action_url">
                    <div :class="getServiceColor(n.service)" class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <i :class="getServiceIcon(n.service)" class="fas text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-0.5">
                            <h4 class="text-xs font-bold text-gray-900 truncate" x-text="n.title"></h4>
                            <span class="text-[9px] font-bold text-gray-400 uppercase" x-text="n.time_ago"></span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-2" x-text="n.message"></p>
                    </div>
                    <template x-if="!n.is_read">
                        <div class="w-2 h-2 bg-blue-600 rounded-full mt-2 shrink-0"></div>
                    </template>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="notifications.length === 0" class="p-10 text-center">
                <i class="far fa-bell-slash text-gray-200 text-4xl mb-4"></i>
                <p class="text-sm text-gray-400 font-medium">All clear! No new alerts.</p>
            </div>
        </div>

        <!-- Footer -->
        <a href="#" class="block text-center py-3 text-xs font-bold text-gray-500 hover:bg-gray-50 transition border-t border-gray-100">
            View All Notifications
        </a>
    </div>
</div>

<script>
function notificationCenter() {
    return {
        open: false,
        notifications: [],
        unreadCount: 0,
        async fetchNotifications() {
            try {
                const response = await fetch('/api/notifications');
                const data = await response.json();
                if (data.success) {
                    const oldUnreadCount = this.unreadCount;
                    this.notifications = data.data.notifications;
                    this.unreadCount = data.data.unread_count;
                    
                    // Play custom chime if unread count increased
                    if (this.unreadCount > oldUnreadCount) {
                        this.playNotificationSound();
                    }
                }
            } catch (e) {
                console.error('Failed to fetch notifications');
            }
        },
        playNotificationSound() {
            const sound = "{{ Str::slug(auth()->user()->organization?->notification_sound ?? 'default') }}";
            const audioPath = `/assets/audio/notifications/${sound}.mp3`;
            const audio = new Audio(audioPath);
            audio.play().catch(e => console.log('Audio playback prevented by browser'));
        },
        async markAllAsRead() {
            try {
                await fetch('/api/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
            } catch (e) {}
        },
        getServiceIcon(service) {
            const icons = { mail: 'fa-envelope', pay: 'fa-wallet', drive: 'fa-folder', security: 'fa-shield-alt' };
            return icons[service] || 'fa-bell';
        },
        getServiceColor(service) {
            const colors = { mail: 'bg-red-500', pay: 'bg-purple-500', drive: 'bg-blue-500', security: 'bg-red-600' };
            return colors[service] || 'bg-gray-400';
        }
    }
}
</script>
