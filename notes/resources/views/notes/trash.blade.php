@extends('layouts.app')
@section('title', 'Trash')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="fas fa-trash text-gray-500 mr-2"></i>Trash
        </h1>
        <p class="text-sm text-gray-500">Deleted notes are permanently removed after 30 days</p>
    </div>

    @if($notes->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($notes as $note)
        @php
            $colorClasses = [
                'white' => 'bg-white border-gray-200',
                'yellow' => 'bg-yellow-50 border-yellow-200',
                'green' => 'bg-green-50 border-green-200',
                'blue' => 'bg-blue-50 border-blue-200',
                'red' => 'bg-red-50 border-red-200',
                'purple' => 'bg-purple-50 border-purple-200',
                'orange' => 'bg-orange-50 border-orange-200',
            ];
            $borderClass = $colorClasses[$note->color] ?? $colorClasses['white'];
        @endphp
        
        <div class="{{ $borderClass }} border rounded-xl p-4 opacity-60 hover:opacity-100 transition group relative">
            {{-- Actions --}}
            <div class="absolute top-2 right-2 flex items-center gap-1">
                <button onclick="restoreNote({{ $note->id }})" 
                        class="p-1.5 rounded-lg bg-green-500 text-white hover:bg-green-600 transition shadow-sm" 
                        title="Restore">
                    <i class="fas fa-undo text-xs"></i>
                </button>
                <button onclick="deletePermanently({{ $note->id }})" 
                        class="p-1.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition shadow-sm" 
                        title="Delete Permanently">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            @if($note->title)
            <h3 class="font-semibold text-gray-900 mb-2 pr-16">{{ $note->title }}</h3>
            @endif
            
            @if($note->content)
            <p class="text-sm text-gray-700 whitespace-pre-wrap line-clamp-4">{{ Str::limit($note->content, 200) }}</p>
            @endif

            <div class="text-xs text-gray-400 mt-3">
                Deleted {{ $note->deleted_at->diffForHumans() }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-20">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <i class="fas fa-trash text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Trash is empty</h3>
        <p class="text-gray-500">Deleted notes will appear here</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
function restoreNote(noteId) {
    if (!confirm('Restore this note?')) return;
    fetch(`/notes/${noteId}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    }).then(() => location.reload());
}

function deletePermanently(noteId) {
    if (!confirm('This action cannot be undone. Delete permanently?')) return;
    fetch(`/notes/${noteId}/permanent`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    }).then(() => location.reload());
}
</script>
@endpush
@endsection
