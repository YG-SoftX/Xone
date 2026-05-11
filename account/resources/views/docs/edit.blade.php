@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('docs.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Documents</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $document->title }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow-md">
        <!-- Toolbar -->
        <div class="border-b border-gray-200 px-6 py-3 flex justify-between items-center">
            <div class="flex gap-2">
                <button onclick="formatText('bold')" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm font-bold">B</button>
                <button onclick="formatText('italic')" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm italic">I</button>
                <button onclick="formatText('underline')" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm underline">U</button>
            </div>
            <button onclick="saveDocument()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                💾 Save
            </button>
        </div>

        <!-- Editor -->
        <div class="p-6">
            <textarea id="docEditor" rows="30" 
                      class="w-full px-4 py-3 border-0 focus:outline-none resize-none text-gray-900 leading-relaxed"
                      placeholder="Start typing...">{{ old('content', $document->content) }}</textarea>
        </div>

        <!-- Status Bar -->
        <div class="border-t border-gray-200 px-6 py-2 flex justify-between items-center text-xs text-gray-500">
            <span id="wordCount">{{ str_word_count($document->content ?? '') }} words</span>
            <span>Last saved: {{ $document->updated_at->diffForHumans() }}</span>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="mt-6 bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Comments</h3>
        <div id="commentsList" class="space-y-4 mb-4">
            @foreach($document->comments as $comment)
                <div class="border-l-4 border-blue-500 pl-4 py-2">
                    <p class="text-sm text-gray-900">{{ $comment->content }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $comment->user->name }} • {{ $comment->created_at->diffForHumans() }}
                    </p>
                </div>
            @endforeach
        </div>

        <form id="commentForm" class="flex gap-2">
            <input type="text" id="commentInput" placeholder="Add a comment..." 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                Comment
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
let autoSaveTimer;

// Auto-save every 30 seconds
setInterval(() => {
    saveDocument();
}, 30000);

function formatText(command) {
    const textarea = document.getElementById('docEditor');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    let formattedText;
    if (command === 'bold') {
        formattedText = `**${text.substring(start, end)}**`;
    } else if (command === 'italic') {
        formattedText = `*${text.substring(start, end)}*`;
    } else {
        formattedText = `<${command}>${text.substring(start, end)}</${command}>`;
    }
    
    textarea.value = text.substring(0, start) + formattedText + text.substring(end);
    updateWordCount();
}

function saveDocument() {
    const content = document.getElementById('docEditor').value;
    
    fetch('{{ route('docs.update', $document->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Document saved!');
        }
    })
    .catch(error => console.error('Save error:', error));
}

function updateWordCount() {
    const text = document.getElementById('docEditor').value;
    const count = text.trim().split(/\s+/).filter(word => word.length > 0).length;
    document.getElementById('wordCount').textContent = `${count} words`;
}

// Comment form
document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const content = document.getElementById('commentInput').value;
    
    fetch('{{ route('docs.comment.add', $document->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('commentInput').value = '';
        location.reload();
    });
});

document.getElementById('docEditor').addEventListener('input', updateWordCount);

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-md shadow-lg';
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 2000);
}
</script>
@endpush
@endsection
