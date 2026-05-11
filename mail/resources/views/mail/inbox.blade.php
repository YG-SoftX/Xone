@extends('layouts.mail')
@section('title', 'Inbox')

@section('mail-content')
<div class="flex flex-col h-full" x-data="mailApp()">
    
    {{-- Toolbar --}}
    <div class="shrink-0 border-b border-gray-200 px-4 py-3 flex items-center gap-3 bg-white">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" @change="selectAll = $event.target.checked" 
                   class="rounded border-gray-300 text-red-500 focus:ring-red-400">
            <span class="text-sm text-gray-600">Select</span>
        </label>
        
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Refresh">
            <i class="fas fa-redo-alt"></i>
        </button>
        
        <div class="flex-1"></div>
        
        <button class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="More">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        
        <span class="text-xs text-gray-500">{{ $emails->total() }} messages</span>
    </div>

    {{-- Email List --}}
    <div class="flex-1 overflow-y-auto">
        @if($emails->count())
        <div class="divide-y divide-gray-100">
            @foreach($emails as $email)
            <div class="email-row px-4 py-3 flex items-start gap-3 cursor-pointer group"
                 @click="viewEmail({{ $email->id }})">
                
                {{-- Checkbox and star --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" class="rounded border-gray-300 text-red-500 focus:ring-red-400"
                           onclick="event.stopPropagation()">
                    <button class="text-gray-300 hover:text-yellow-500 transition" 
                            onclick="event.stopPropagation(); toggleStar({{ $email->id }})">
                        <i class="{{ $email->is_starred ? 'fas text-yellow-500' : 'far' }} fa-star"></i>
                    </button>
                </div>

                {{-- Sender --}}
                <div class="w-48 shrink-0">
                    <div class="text-sm font-medium {{ $email->is_read ? 'text-gray-700' : 'text-gray-900 font-semibold' }}">
                        {{ $email->sender_name ?? $email->from }}
                    </div>
                </div>

                {{-- Subject and preview --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm {{ $email->is_read ? 'text-gray-600' : 'text-gray-900 font-semibold' }}">
                            {{ Str::limit($email->subject, 80) }}
                        </span>
                        <span class="text-xs text-gray-500 truncate">
                            - {{ Str::limit(strip_tags($email->body_preview ?? ''), 120) }}
                        </span>
                    </div>
                </div>

                {{-- Date/Time --}}
                <div class="text-xs text-gray-500 shrink-0 w-20 text-right">
                    {{ $email->created_at->format('M j') }}
                </div>

                {{-- Hover actions --}}
                <div class="hidden group-hover:flex items-center gap-1 absolute right-4 bg-white shadow-lg rounded-lg p-1 border border-gray-200">
                    <button class="p-1.5 hover:bg-gray-100 rounded text-gray-600" title="Archive">
                        <i class="fas fa-archive text-sm"></i>
                    </button>
                    <button class="p-1.5 hover:bg-gray-100 rounded text-gray-600" title="Delete">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                    <button class="p-1.5 hover:bg-gray-100 rounded text-gray-600" title="Mark as unread">
                        <i class="fas fa-envelope text-sm"></i>
                    </button>
                    <button class="p-1.5 hover:bg-gray-100 rounded text-gray-600" title="Snooze">
                        <i class="fas fa-clock text-sm"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $emails->links() }}
        </div>
        @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center h-full py-20">
            <div class="w-24 h-24 mb-4 bg-gradient-to-br from-red-100 to-pink-100 rounded-full flex items-center justify-center">
                <i class="fas fa-inbox text-4xl text-red-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Your inbox is empty</h3>
            <p class="text-gray-500">New emails will appear here</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function mailApp() {
    return {
        selectAll: false,
        
        viewEmail(emailId) {
            window.location.href = `/mail/${emailId}`;
        },
        
        async toggleStar(emailId) {
            try {
                const response = await fetch(`/mail/${emailId}/star`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }
}

// Polling for new emails every 30 seconds
setInterval(async () => {
    try {
        const response = await fetch('{{ route("mail.poll") }}');
        const data = await response.json();
        if (data.new_emails > 0) {
            // Show notification
            console.log(`${data.new_emails} new emails`);
        }
    } catch (error) {
        console.error('Poll error:', error);
    }
}, 30000);
</script>
@endpush
@endsection
