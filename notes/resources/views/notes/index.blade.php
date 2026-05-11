@extends('layouts.app')
@section('title', 'My Notes')

@section('content')
<div class="max-w-7xl mx-auto p-6" x-data="notesApp()">
    
    {{-- Pinned Notes --}}
    @if($pinnedNotes->count())
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
            <i class="fas fa-thumbtack text-yellow-500"></i> Pinned
        </h2>
        <div class="{{ $view === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4' : 'space-y-3' }}">
            @foreach($pinnedNotes as $note)
                @include('notes.components.note-card', ['note' => $note])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Other Notes --}}
    @if($otherNotes->count())
    <div>
        @if($pinnedNotes->count())
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Others</h2>
        @endif
        <div class="{{ $view === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4' : 'space-y-3' }}">
            @foreach($otherNotes as $note)
                @include('notes.components.note-card', ['note' => $note])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty State --}}
    @if($pinnedNotes->count() === 0 && $otherNotes->count() === 0)
    <div class="text-center py-20">
        <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-full flex items-center justify-center">
            <i class="fas fa-lightbulb text-5xl text-yellow-500"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Notes you add appear here</h3>
        <p class="text-gray-500 mb-6">Click "Create Note" to add your first note</p>
        <button onclick="openCreateModal()" 
                class="px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl font-medium transition shadow-md">
            <i class="fas fa-plus mr-2"></i>Create Your First Note
        </button>
    </div>
    @endif
</div>

{{-- Create Note Modal --}}
<div id="create-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fade-in">
        <form id="create-note-form" method="POST" action="{{ route('notes.store') }}" class="p-6">
            @csrf
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">New Note</h3>
                <button type="button" onclick="closeCreateModal()" 
                        class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <input type="text" name="title" placeholder="Title" 
                   class="w-full text-xl font-semibold mb-3 focus:outline-none placeholder-gray-400 px-2">
            
            <textarea name="content" rows="6" placeholder="Take a note..." 
                      class="w-full resize-none focus:outline-none placeholder-gray-400 text-gray-700 px-2"></textarea>
            
            <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    <select name="color" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        <option value="white">⚪ White</option>
                        <option value="yellow">🟡 Yellow</option>
                        <option value="green">🟢 Green</option>
                        <option value="blue">🔵 Blue</option>
                        <option value="red">🔴 Red</option>
                        <option value="purple">🟣 Purple</option>
                        <option value="orange">🟠 Orange</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeCreateModal()" 
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                    <button type="submit" 
                            class="px-5 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-lg font-medium transition shadow-md">
                        Save Note
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('create-modal').classList.remove('hidden');
    setTimeout(() => {
        document.querySelector('#create-modal input[name="title"]').focus();
    }, 100);
}

function closeCreateModal() {
    document.getElementById('create-modal').classList.add('hidden');
    document.getElementById('create-note-form').reset();
}

function notesApp() {
    return {
        init() {
            // Start polling for updates every 30 seconds
            setInterval(() => this.pollUpdates(), 30000);
        },
        
        async pollUpdates() {
            try {
                const response = await fetch('{{ route("notes.poll") }}?since=' + new Date().toISOString());
                const data = await response.json();
                if (data.notes && data.notes.length > 0) {
                    // Show notification or refresh
                    console.log('Notes updated:', data.notes.length);
                }
            } catch (error) {
                console.error('Poll error:', error);
            }
        }
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
    }
});
</script>
@endpush
@endsection
