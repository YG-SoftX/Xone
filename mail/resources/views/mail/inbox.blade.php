@extends('layouts.mail')
@section('title', 'Inbox')

@section('mail-content')
<div class="flex flex-col h-full" x-data="mailApp()">

    {{-- Toolbar --}}
    <div class="shrink-0 border-b border-gray-200 px-4 py-3 flex items-center gap-3 bg-white">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" @change="selectAll = $event.target.checked"
                   class="rounded border-gray-300 text-red-500 focus:ring-red-400">
            <span class="text-sm text-gray-600 hidden sm:inline">Select</span>
        </label>

        <a href="{{ route('mail.inbox') }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Refresh">
            <i class="fas fa-redo-alt"></i>
        </a>

        @if($currentFolder === 'trash')
        <span class="text-xs text-gray-400 ml-2">Trash empties after 30 days</span>
        @endif

        <div class="flex-1"></div>

        <span class="text-xs text-gray-500">{{ $emails->total() }} {{ Str::plural('message', $emails->total()) }}</span>
    </div>

    {{-- Email List --}}
    <div class="flex-1 overflow-y-auto">
        @if($emails->count())
        <div class="divide-y divide-gray-100">
            @foreach($emails as $email)
            <div class="email-row px-4 py-3 flex items-start gap-3 cursor-pointer group relative hover:bg-gray-50 transition"
                 @click="viewEmail({{ $email->id }})">

                {{-- Checkbox and star --}}
                <div class="flex items-center gap-2 pt-0.5 shrink-0" @click.stop>
                    <input type="checkbox" class="rounded border-gray-300 text-red-500 focus:ring-red-400">
                    <button class="text-gray-300 hover:text-yellow-500 transition text-sm"
                            onclick="event.stopPropagation(); toggleStar({{ $email->id }})">
                        <i class="{{ $email->is_starred ? 'fas text-yellow-500' : 'far' }} fa-star"></i>
                    </button>
                </div>

                {{-- Sender --}}
                <div class="w-36 md:w-48 shrink-0">
                    <div class="text-sm {{ !$email->read ? 'text-gray-900 font-semibold' : 'text-gray-700' }} truncate">
                        {{ $email->from }}
                    </div>
                </div>

                {{-- Subject and preview --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm {{ !$email->read ? 'text-gray-900 font-semibold' : 'text-gray-600' }} truncate">
                            {{ Str::limit($email->subject, 80) }}
                        </span>
                        @if($email->body)
                        <span class="text-xs text-gray-400 truncate hidden md:inline">
                            – {{ Str::limit(strip_tags($email->body), 120) }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Date/Time --}}
                <div class="text-xs text-gray-500 shrink-0 w-16 text-right">
                    {{ $email->created_at->format('M j') }}
                </div>

                {{-- Hover actions --}}
                <div class="hidden group-hover:flex items-center gap-0.5 absolute right-2 top-2 bg-white shadow-lg rounded-lg border border-gray-200 z-10"
                     @click.stop>
                    <form action="{{ route('mail.delete', $email->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-2 hover:bg-gray-100 rounded text-gray-500 hover:text-red-500 transition" title="{{ $currentFolder === 'trash' ? 'Delete permanently' : 'Move to trash' }}">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </form>
                    @if($currentFolder !== 'starred')
                    <button class="p-2 hover:bg-gray-100 rounded text-gray-500 hover:text-yellow-500 transition" title="Star"
                            onclick="toggleStar({{ $email->id }})">
                        <i class="fas fa-star text-xs"></i>
                    </button>
                    @endif
                    @if($email->read)
                    <form action="{{ route('mail.star', $email->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-2 hover:bg-gray-100 rounded text-gray-500 hover:text-blue-500 transition" title="Mark as unread">
                            <i class="fas fa-envelope text-xs"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-4 border-t border-gray-200 bg-white">
            {{ $emails->appends(request()->query())->links() }}
        </div>
        @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center h-full py-20">
            <div class="w-20 h-20 mb-4 bg-gradient-to-br from-red-50 to-pink-50 rounded-full flex items-center justify-center">
                <i class="fas fa-inbox text-3xl text-red-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">
                @if($currentFolder === 'sent')
                No sent messages
                @elseif($currentFolder === 'trash')
                Trash is empty
                @elseif($currentFolder === 'spam')
                No spam messages
                @elseif($currentFolder === 'starred')
                No starred messages
                @elseif($currentFolder === 'search')
                No results found
                @else
                Your inbox is empty
                @endif
            </h3>
            <p class="text-gray-500">
                @if($currentFolder === 'search')
                Try a different search term
                @elseif($currentFolder === 'sent')
                <a href="#" @click.prevent="openCompose()" class="text-red-600 hover:text-red-700 font-medium">Compose your first email</a>
                @else
                New emails will appear here
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Compose Button (Floating) --}}
    <button @click="openCompose()"
            class="fixed bottom-6 right-6 z-40 w-14 h-14 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white rounded-full shadow-lg hover:shadow-xl transition-all flex items-center justify-center">
        <i class="fas fa-pen text-lg"></i>
    </button>

    {{-- Compose Modal --}}
    <div x-show="composeOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/30 backdrop-blur-sm p-0 sm:p-4"
         @click.away="closeCompose()">
        <div class="bg-white w-full sm:max-w-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
             @click.stop>
            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">New Message</h3>
                <button @click="closeCompose()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Compose form --}}
            <form id="compose-form" action="{{ route('mail.send') }}" method="POST"
                  enctype="multipart/form-data"
                  class="flex-1 overflow-y-auto">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">To</label>
                        <input type="email" name="to" required
                               placeholder="recipient@example.com"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Subject</label>
                        <input type="text" name="subject" required
                               placeholder="What's this about?"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Message</label>
                        <textarea name="body" rows="12" required
                                  placeholder="Write your message..."
                                  class="w-full resize-none text-sm leading-relaxed border border-gray-300 rounded-xl p-4 text-gray-900 placeholder-gray-400 focus:border-red-300 focus:ring-2 focus:ring-red-100 outline-none transition"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Attachments</label>
                        <input type="file" name="attachments[]" multiple
                               class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition">
                    </div>
                </div>
            </form>

            {{-- Modal footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">
                        <i class="fas fa-shield-alt mr-1"></i> End-to-end encrypted
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="closeCompose()"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200 rounded-lg transition">
                        Discard
                    </button>
                    <button type="submit" form="compose-form"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white rounded-xl font-medium transition shadow-md">
                        <i class="fas fa-paper-plane mr-1.5"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mailApp() {
    return {
        selectAll: false,
        composeOpen: new URLSearchParams(window.location.search).get('compose') === '1',

        viewEmail(emailId) {
            window.location.href = `{{ url('mail') }}/${emailId}`;
        },

        openCompose() {
            this.composeOpen = true;
        },

        closeCompose() {
            if (confirm('Discard this message?')) {
                this.composeOpen = false;
                document.getElementById('compose-form')?.reset();
            }
        },

        async toggleStar(emailId) {
            try {
                const response = await fetch(`{{ url('mail') }}/${emailId}/star`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    // Reload to reflect changes
                    location.reload();
                }
            } catch (error) {
                console.error('Error starring email:', error);
            }
        }
    }
}

// Polling for new emails every 30 seconds
setInterval(async () => {
    try {
        const response = await fetch('{{ route("mail.poll") }}', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.new_emails > 0 && data.new_emails !== undefined) {
            // Gentle notification - user can refresh manually
            const title = document.title;
            document.title = `(${data.new_emails}) ${title}`;
            setTimeout(() => { document.title = title; }, 5000);
        }
    } catch (error) {
        // Silent fail for polling
    }
}, 30000);
</script>
@endpush
@endsection
