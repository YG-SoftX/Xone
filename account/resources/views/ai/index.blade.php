@extends('layouts.platform')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- AI Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">YG AI Assistant</h1>
        <p class="text-sm text-gray-600">Your intelligent helper across all services</p>
    </div>

    <!-- Chat Interface -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Ask YG AI</h2>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages" class="p-6 space-y-4 max-h-96 overflow-y-auto">
            <div class="flex items-start">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">
                    AI
                </div>
                <div class="bg-gray-100 rounded-lg px-4 py-2 max-w-2xl">
                    <p class="text-sm text-gray-900">Hello! I'm your YG AI assistant. How can I help you today?</p>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 p-4">
            <form id="aiQueryForm" class="flex gap-3">
                <select id="serviceSelect" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="general">General</option>
                    <option value="mail">Mail</option>
                    <option value="drive">Drive</option>
                    <option value="docs">Docs</option>
                    <option value="meet">Meet</option>
                    <option value="pay">Pay</option>
                </select>
                <input type="text" id="queryInput" placeholder="Type your question..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Send
                </button>
            </form>
        </div>
    </div>

    <!-- Recent Queries -->
    @if($recentQueries->isNotEmpty())
        <div class="bg-white rounded-lg shadow-md">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Queries</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($recentQueries as $query)
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $query->query }}</p>
                                @if($query->response)
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ substr($query->response, 0, 150) }}...</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $query->service }} • {{ $query->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if($query->completed_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Completed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Processing
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('aiQueryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const query = document.getElementById('queryInput').value.trim();
    const service = document.getElementById('serviceSelect').value;
    
    if (!query) return;
    
    // Add user message to chat
    addMessage(query, 'user');
    document.getElementById('queryInput').value = '';
    
    // Show loading indicator
    addMessage('Thinking...', 'ai', true);
    
    // Send query to API
    fetch('{{ route('ai.query') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ query, service })
    })
    .then(response => response.json())
    .then(data => {
        // Remove loading message
        removeLoadingMessage();
        
        // Add AI response
        addMessage(data.response, 'ai');
        
        // Show suggestions if available
        if (data.suggestions && data.suggestions.length > 0) {
            showSuggestions(data.suggestions);
        }
    })
    .catch(error => {
        removeLoadingMessage();
        addMessage('Sorry, I encountered an error. Please try again.', 'ai');
        console.error('AI query error:', error);
    });
});

function addMessage(text, sender, isLoading = false) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex items-start' + (sender === 'user' ? ' flex-row-reverse' : '');
    
    const avatar = document.createElement('div');
    avatar.className = `w-8 h-8 ${sender === 'user' ? 'bg-gray-600' : 'bg-blue-600'} rounded-full flex items-center justify-center text-white text-sm font-bold ${sender === 'user' ? 'ml-3' : 'mr-3'}`;
    avatar.textContent = sender === 'user' ? 'You' : 'AI';
    
    const bubble = document.createElement('div');
    bubble.className = `${sender === 'user' ? 'bg-blue-100' : 'bg-gray-100'} rounded-lg px-4 py-2 max-w-2xl`;
    
    const textP = document.createElement('p');
    textP.className = 'text-sm text-gray-900';
    textP.textContent = text;
    
    if (isLoading) {
        messageDiv.id = 'loadingMessage';
        textP.classList.add('italic', 'text-gray-500');
    }
    
    bubble.appendChild(textP);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(bubble);
    chatMessages.appendChild(messageDiv);
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function removeLoadingMessage() {
    const loadingMsg = document.getElementById('loadingMessage');
    if (loadingMsg) loadingMsg.remove();
}

function showSuggestions(suggestions) {
    const chatMessages = document.getElementById('chatMessages');
    const suggestionDiv = document.createElement('div');
    suggestionDiv.className = 'px-6 pb-4';
    
    const title = document.createElement('p');
    title.className = 'text-xs text-gray-500 mb-2';
    title.textContent = 'Suggestions:';
    
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'flex flex-wrap gap-2';
    
    suggestions.forEach(suggestion => {
        const button = document.createElement('button');
        button.className = 'px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-full text-xs text-gray-700 transition-colors';
        button.textContent = suggestion;
        button.onclick = () => {
            document.getElementById('queryInput').value = suggestion;
        };
        buttonsDiv.appendChild(button);
    });
    
    suggestionDiv.appendChild(title);
    suggestionDiv.appendChild(buttonsDiv);
    chatMessages.appendChild(suggestionDiv);
}

// Allow Enter key to submit
document.getElementById('queryInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('aiQueryForm').dispatchEvent(new Event('submit'));
    }
});
</script>
@endpush
@endsection
