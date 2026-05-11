@php
    $colorClasses = [
        'white' => 'bg-white border-gray-200 hover:border-gray-300',
        'yellow' => 'bg-yellow-50 border-yellow-200 hover:border-yellow-300',
        'green' => 'bg-green-50 border-green-200 hover:border-green-300',
        'blue' => 'bg-blue-50 border-blue-200 hover:border-blue-300',
        'red' => 'bg-red-50 border-red-200 hover:border-red-300',
        'purple' => 'bg-purple-50 border-purple-200 hover:border-purple-300',
        'orange' => 'bg-orange-50 border-orange-200 hover:border-orange-300',
    ];
    $borderClass = $colorClasses[$note->color] ?? $colorClasses['white'];
@endphp

<div class="note-card {{ $borderClass }} border rounded-xl p-4 group relative animate-fade-in"
     x-data="{ hovered: false, editing: false }"
     @mouseenter="hovered = true"
     @mouseleave="hovered = false">
    
    {{-- Quick Actions (visible on hover) --}}
    <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition bg-white/90 backdrop-blur-sm rounded-lg p-1 shadow-sm"
         x-show="hovered" x-transition>
        <button onclick="togglePin({{ $note->id }})" 
                class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-yellow-600 transition" 
                title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
            <i class="fas fa-thumbtack {{ $note->is_pinned ? 'text-yellow-500' : '' }}"></i>
        </button>
        <button onclick="archiveNote({{ $note->id }})" 
                class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition" 
                title="Archive">
            <i class="fas fa-archive"></i>
        </button>
        <button onclick="deleteNote({{ $note->id }})" 
                class="p-1.5 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-600 transition" 
                title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    </div>

    {{-- Note Content --}}
    <div @click="editNote({{ $note->id }})">
        @if($note->title)
        <h3 class="font-semibold text-gray-900 mb-2 pr-16 line-clamp-2">{{ $note->title }}</h3>
        @endif
        
        @if($note->content)
        <p class="text-sm text-gray-700 whitespace-pre-wrap line-clamp-6">{{ Str::limit($note->content, 300) }}</p>
        @endif

        {{-- Checklist preview --}}
        @if($note->checklists && $note->checklists->count())
        <div class="mt-3 space-y-1">
            @foreach($note->checklists->take(3) as $item)
            <div class="flex items-center gap-2 text-xs">
                <input type="checkbox" {{ $item->is_completed ? 'checked' : '' }} 
                       class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-400"
                       onclick="event.stopPropagation(); toggleChecklistItem({{ $item->id }})">
                <span class="{{ $item->is_completed ? 'line-through text-gray-400' : 'text-gray-700' }}">
                    {{ Str::limit($item->item_text, 40) }}
                </span>
            </div>
            @endforeach
            @if($note->checklists->count() > 3)
            <div class="text-xs text-gray-400 pl-6">+{{ $note->checklists->count() - 3 }} more items</div>
            @endif
        </div>
        @endif

        {{-- Labels --}}
        @if($note->labels && count($note->labels))
        <div class="flex flex-wrap gap-1.5 mt-3">
            @foreach($note->labels as $label)
            <span class="text-xs bg-white/70 px-2.5 py-1 rounded-full font-medium text-gray-600 border border-gray-200">
                {{ $label }}
            </span>
            @endforeach
        </div>
        @endif

        {{-- Updated time --}}
        <div class="text-xs text-gray-400 mt-3 flex items-center gap-2">
            <i class="far fa-clock"></i>
            <span>{{ $note->updated_at->diffForHumans() }}</span>
        </div>
    </div>
</div>

<script>
function togglePin(noteId) {
    fetch(`/notes/${noteId}/pin`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(() => location.reload())
    .catch(error => console.error('Error:', error));
}

function archiveNote(noteId) {
    if (!confirm('Archive this note?')) return;
    fetch(`/notes/${noteId}/archive`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(() => location.reload())
    .catch(error => console.error('Error:', error));
}

function deleteNote(noteId) {
    if (!confirm('Move this note to trash?')) return;
    fetch(`/notes/${noteId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(() => location.reload())
    .catch(error => console.error('Error:', error));
}

function toggleChecklistItem(itemId) {
    fetch(`/checklist/${itemId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(() => location.reload())
    .catch(error => console.error('Error:', error));
}

function editNote(noteId) {
    // TODO: Implement inline editing or open edit modal
    console.log('Edit note:', noteId);
}
</script>
