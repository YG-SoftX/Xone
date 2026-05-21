@extends('layouts.mail')
@section('title', ucfirst($currentFolder))

@section('mail-content')
<div class="flex flex-col h-full bg-white select-none" x-data="inboxApp()">

    <!-- Tabbed Categories (Gmail Style) -->
    @if($currentFolder === 'inbox')
    <div class="flex border-b border-gray-100 shrink-0 bg-white" x-data="{ activeTab: 'primary' }">
        <button @click="activeTab = 'primary'; filterCategory('all')"
                class="flex-1 flex items-center justify-center gap-3 py-4 border-b-2 font-semibold text-[11px] tracking-wider uppercase transition-all duration-200 outline-none"
                :class="activeTab === 'primary' ? 'border-b-yg-600 text-yg-600 bg-yg-50/30' : 'border-b-transparent text-gray-400 hover:text-gray-600 hover:bg-gray-50/50'">
            <i class="fas fa-inbox text-sm"></i>
            <span>Primary</span>
        </button>
        <button @click="activeTab = 'promotions'; filterCategory('promo')"
                class="flex-1 flex items-center justify-center gap-3 py-4 border-b-2 font-semibold text-[11px] tracking-wider uppercase transition-all duration-200 outline-none"
                :class="activeTab === 'promotions' ? 'border-b-yg-600 text-yg-600 bg-yg-50/30' : 'border-b-transparent text-gray-400 hover:text-gray-600 hover:bg-gray-50/50'">
            <i class="fas fa-tags text-sm"></i>
            <span>Promotions</span>
        </button>
        <button @click="activeTab = 'social'; filterCategory('social')"
                class="flex-1 flex items-center justify-center gap-3 py-4 border-b-2 font-semibold text-[11px] tracking-wider uppercase transition-all duration-200 outline-none"
                :class="activeTab === 'social' ? 'border-b-yg-600 text-yg-600 bg-yg-50/30' : 'border-b-transparent text-gray-400 hover:text-gray-600 hover:bg-gray-50/50'">
            <i class="fas fa-users text-sm"></i>
            <span>Social</span>
        </button>
        <button @click="activeTab = 'updates'; filterCategory('update')"
                class="flex-1 flex items-center justify-center gap-3 py-4 border-b-2 font-semibold text-[11px] tracking-wider uppercase transition-all duration-200 outline-none"
                :class="activeTab === 'updates' ? 'border-b-yg-600 text-yg-600 bg-yg-50/30' : 'border-b-transparent text-gray-400 hover:text-gray-600 hover:bg-gray-50/50'">
            <i class="fas fa-info-circle text-sm"></i>
            <span>Updates</span>
        </button>
    </div>
    @endif

    <!-- Toolbar Action Bar -->
    <div class="shrink-0 border-b border-gray-100 px-6 py-3.5 flex items-center gap-3 bg-white">
        <!-- Master Checkbox -->
        <label class="flex items-center gap-2.5 cursor-pointer p-1 rounded hover:bg-gray-100 transition shrink-0">
            <input type="checkbox" @change="toggleSelectAll($event.target.checked)" :checked="isAllSelected()"
                   class="rounded border-gray-300 text-yg-600 focus:ring-yg-400 w-4 h-4 cursor-pointer">
        </label>

        <!-- Refresh Button -->
        <a href="{{ route('mail.inbox', ['folder' => $currentFolder]) }}" 
           class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition" title="Refresh">
            <i class="fas fa-arrow-rotate-right text-sm"></i>
        </a>

        <!-- Dynamic Selection Actions (Slide-in) -->
        <div class="flex items-center gap-1.5 border-l border-gray-200 pl-3 ml-2 transition-all duration-300"
             x-show="selectedEmails.length > 0" x-transition:enter="opacity-0 translate-x-[-10px]" x-transition:leave="opacity-0 translate-x-[-10px]" x-cloak>
            
            <button @click="deleteSelected()" 
                    class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition flex items-center gap-1.5" title="Move to Trash">
                <i class="fas fa-trash-can text-sm"></i>
                <span class="text-xs font-semibold hidden sm:inline">Delete</span>
            </button>

            <button @click="markSelectedAsRead(true)" 
                    class="p-2 text-gray-500 hover:text-yg-600 hover:bg-yg-50 rounded-xl transition flex items-center gap-1.5" title="Mark as Read">
                <i class="fas fa-envelope-open text-sm"></i>
                <span class="text-xs font-semibold hidden sm:inline">Read</span>
            </button>

            <button @click="markSelectedAsRead(false)" 
                    class="p-2 text-gray-500 hover:text-yg-600 hover:bg-yg-50 rounded-xl transition flex items-center gap-1.5" title="Mark as Unread">
                <i class="fas fa-envelope text-sm"></i>
                <span class="text-xs font-semibold hidden sm:inline">Unread</span>
            </button>
            
            <span class="text-xs font-bold text-yg-600 bg-yg-50 px-2.5 py-1 rounded-full border border-yg-100 ml-2">
                <span x-text="selectedEmails.length"></span> selected
            </span>
        </div>

        <div class="flex-1"></div>

        <!-- Custom Pagination Info -->
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-gray-500">
                {{ $emails->firstItem() ?? 0 }}-{{ $emails->lastItem() ?? 0 }} of {{ $emails->total() }}
            </span>
            <div class="flex items-center gap-1">
                @if($emails->onFirstPage())
                    <span class="p-1.5 text-gray-300 cursor-default"><i class="fas fa-chevron-left text-xs"></i></span>
                @else
                    <a href="{{ $emails->previousPageUrl() }}" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-lg transition"><i class="fas fa-chevron-left text-xs"></i></a>
                @endif

                @if($emails->hasMorePages())
                    <a href="{{ $emails->nextPageUrl() }}" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-lg transition"><i class="fas fa-chevron-right text-xs"></i></a>
                @else
                    <span class="p-1.5 text-gray-300 cursor-default"><i class="fas fa-chevron-right text-xs"></i></span>
                @endif
            </div>
        </div>
    </div>

    <!-- Email List Container -->
    <div class="flex-1 overflow-y-auto">
        @if($emails->count())
        <div class="divide-y divide-gray-100/60">
            @foreach($emails as $email)
            <!-- Email Row -->
            <div class="email-row px-6 py-3.5 flex items-center gap-4 cursor-pointer group relative transition-all duration-150 border-l-4"
                 :class="{
                     'bg-yg-50/20 border-l-yg-600': isSelected({{ $email->id }}),
                     'border-l-transparent hover:bg-gray-50/60': !isSelected({{ $email->id }})
                 }"
                 @click="viewEmail({{ $email->id }})"
                 x-show="shouldShowEmail('{{ $email->subject }}', '{{ $email->id }}')">

                <!-- Checkbox & Star Action Group -->
                <div class="flex items-center gap-3 shrink-0" @click.stop>
                    <input type="checkbox" :checked="isSelected({{ $email->id }})" @change="toggleSelect({{ $email->id }}, $event.target.checked)"
                           class="rounded border-gray-300 text-yg-600 focus:ring-yg-400 w-4 h-4 cursor-pointer">
                    
                    <button class="text-gray-300 hover:text-amber-400 transition"
                            @click="toggleStar({{ $email->id }})">
                        <i class="text-sm" :class="isStarred({{ $email->id }}, {{ $email->is_starred ? 'true' : 'false' }}) ? 'fa-solid fa-star text-amber-400' : 'fa-regular fa-star'"></i>
                    </button>
                </div>

                <!-- Secure E2E Lock Badge -->
                <div class="shrink-0">
                    <span class="w-5 h-5 flex items-center justify-center rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100" title="End-to-End Encrypted Security">
                        <i class="fas fa-lock text-[9px]"></i>
                    </span>
                </div>

                <!-- Sender Name -->
                <div class="w-32 sm:w-48 shrink-0 truncate">
                    <span class="text-xs {{ !$email->read ? 'text-gray-900 font-extrabold font-title' : 'text-gray-600 font-medium' }}">
                        {{ explode('@', $email->from)[0] }}
                    </span>
                </div>

                <!-- Subject & Content Preview Snippet -->
                <div class="flex-1 min-w-0 pr-4">
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs {{ !$email->read ? 'text-gray-900 font-extrabold font-title' : 'text-gray-700 font-semibold' }} truncate">
                            {{ $email->subject }}
                        </span>
                        @if($email->body)
                        <span class="text-[11px] text-gray-400 truncate hidden md:inline font-medium">
                            – {{ Str::limit(strip_tags($email->body), 100) }}
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Right Metadata Indicators -->
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Attachment Icon -->
                    @if($email->attachments->count())
                        <i class="fas fa-paperclip text-gray-400 text-[10px]" title="Has attachments"></i>
                    @endif
                    
                    <!-- Date -->
                    <span class="text-[11px] font-bold text-gray-400 group-hover:invisible w-14 text-right">
                        {{ $email->created_at->format('M j') }}
                    </span>
                </div>

                <!-- Premium Floating Hover Row Actions -->
                <div class="hidden group-hover:flex items-center gap-1.5 absolute right-6 top-1/2 -translate-y-1/2 bg-white/95 backdrop-blur-sm border border-gray-100 shadow-xl rounded-xl px-2 py-1 z-10"
                     @click.stop>
                    <button class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                            @click="deleteEmail({{ $email->id }})" title="Delete secure email">
                        <i class="fas fa-trash-can text-[11px]"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-yg-600 hover:bg-yg-50 rounded-lg transition" 
                            @click="toggleRead({{ $email->id }}, {{ $email->read ? 'true' : 'false' }})" title="Mark as {{ $email->read ? 'unread' : 'read' }}">
                        <i class="fas {{ $email->read ? 'fa-envelope' : 'fa-envelope-open' }} text-[11px]"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition" 
                            @click="toggleStar({{ $email->id }})" title="Star secure email">
                        <i class="fas fa-star text-[11px]"></i>
                    </button>
                </div>

            </div>
            @endforeach
        </div>

        @else
        <!-- Empty State Illustration -->
        <div class="flex flex-col items-center justify-center py-24 px-6 text-center h-full">
            <div class="w-24 h-24 mb-6 bg-gradient-to-br from-yg-50 to-yg-100 rounded-3xl flex items-center justify-center shadow-inner">
                <i class="fas fa-box-open text-4xl text-yg-500/70"></i>
            </div>
            <h3 class="text-lg font-extrabold text-gray-800 font-title mb-1.5">No Secure Emails Found</h3>
            <p class="text-xs text-gray-400 max-w-sm leading-relaxed mb-6 font-medium">
                @if($currentFolder === 'search')
                    We couldn't find matches for your current keyword query. Try searching with simplified terms.
                @else
                    Your folder is completely clear of secure messages. Click below to compose a private, encrypted note.
                @endif
            </p>
            <button @click="openCompose()" class="px-5 py-2.5 bg-yg-600 hover:bg-yg-700 text-white text-xs font-bold rounded-xl shadow-md shadow-yg-600/10 hover:shadow-lg transition">
                <i class="fas fa-plus mr-1.5"></i> Compose Draft
            </button>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function inboxApp() {
    return {
        selectedEmails: [],
        starredEmails: {},
        readEmails: {},
        categoryFilter: 'all',

        init() {
            // Setup pre-starred values
            @foreach($emails as $email)
                this.starredEmails[{{ $email->id }}] = {{ $email->is_starred ? 'true' : 'false' }};
                this.readEmails[{{ $email->id }}] = {{ $email->read ? 'true' : 'false' }};
            @endforeach
        },

        viewEmail(emailId) {
            window.location.href = `{{ url('mail') }}/${emailId}`;
        },

        // Tabs filtering
        filterCategory(category) {
            this.categoryFilter = category;
        },

        shouldShowEmail(subject, id) {
            if (this.categoryFilter === 'all') return true;
            
            // Client-side categorization mock for high-fidelity interactive feel
            const sub = subject.toLowerCase();
            if (this.categoryFilter === 'promo') {
                return sub.includes('offer') || sub.includes('deal') || sub.includes('promo') || sub.includes('sale') || sub.includes('discount') || id % 4 === 1;
            }
            if (this.categoryFilter === 'social') {
                return sub.includes('social') || sub.includes('facebook') || sub.includes('twitter') || sub.includes('network') || sub.includes('joined') || id % 4 === 2;
            }
            if (this.categoryFilter === 'update') {
                return sub.includes('update') || sub.includes('alert') || sub.includes('security') || sub.includes('billing') || id % 4 === 3;
            }
            return true;
        },

        // Stars
        isStarred(id, serverDefault) {
            return this.starredEmails[id] !== undefined ? this.starredEmails[id] : serverDefault;
        },

        async toggleStar(emailId) {
            const current = this.isStarred(emailId, false);
            this.starredEmails[emailId] = !current;
            
            try {
                await fetch(`{{ url('mail') }}/${emailId}/star`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
            } catch (error) {
                console.error('Error toggling star:', error);
                this.starredEmails[emailId] = current; // Revert
            }
        },

        // Selection
        isSelected(id) {
            return this.selectedEmails.includes(id);
        },

        toggleSelect(id, checked) {
            if (checked) {
                if (!this.selectedEmails.includes(id)) this.selectedEmails.push(id);
            } else {
                this.selectedEmails = this.selectedEmails.filter(x => x !== id);
            }
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedEmails = [];
                @foreach($emails as $email)
                    this.selectedEmails.push({{ $email->id }});
                @endforeach
            } else {
                this.selectedEmails = [];
            }
        },

        isAllSelected() {
            return this.selectedEmails.length > 0 && this.selectedEmails.length === {{ $emails->count() }};
        },

        // Bulk operations
        async deleteSelected() {
            if (!confirm(`Are you sure you want to delete ${this.selectedEmails.length} messages?`)) return;
            
            const idsToDelete = [...this.selectedEmails];
            this.selectedEmails = [];
            
            try {
                for (const id of idsToDelete) {
                    await fetch(`{{ url('mail') }}/${id}/delete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        }
                    });
                }
                location.reload();
            } catch (error) {
                console.error('Error deleting messages:', error);
            }
        },

        // Mark read/unread
        async toggleRead(emailId, currentServerState) {
            const isRead = this.readEmails[emailId] !== undefined ? this.readEmails[emailId] : currentServerState;
            this.readEmails[emailId] = !isRead;

            // In our system, markRead uses PATCH to /api/mail/{id}/read, but we can do a standard reload or POST to change state
            // For now let's just trigger direct API PATCH or refresh
            try {
                await fetch(`{{ url('api/mail') }}/${emailId}/read`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                location.reload();
            } catch (e) {
                location.reload();
            }
        },

        async deleteEmail(id) {
            try {
                await fetch(`{{ url('mail') }}/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                location.reload();
            } catch (error) {
                console.error('Error deleting message:', error);
            }
        }
    }
}
</script>
@endpush
@endsection
