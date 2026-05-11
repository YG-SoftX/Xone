@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Docs Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">YG Docs</h1>
            <p class="text-sm text-gray-600">Productivity Suite</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            ➕ New Document
        </button>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('docs.index', ['type' => 'all']) }}" 
               class="{{ request('type', 'all') === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All Documents
            </a>
            <a href="{{ route('docs.index', ['type' => 'document']) }}" 
               class="{{ request('type') === 'document' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📄 Documents
            </a>
            <a href="{{ route('docs.index', ['type' => 'spreadsheet']) }}" 
               class="{{ request('type') === 'spreadsheet' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📊 Spreadsheets
            </a>
            <a href="{{ route('docs.index', ['type' => 'presentation']) }}" 
               class="{{ request('type') === 'presentation' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📽️ Presentations
            </a>
        </nav>
    </div>

    <!-- Documents Grid -->
    @if($documents->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($documents as $doc)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <a href="{{ route('docs.edit', $doc->id) }}" class="block p-6">
                        <div class="flex items-start justify-between mb-4">
                            @if($doc->type === 'document')
                                <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            @elseif($doc->type === 'spreadsheet')
                                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            @else
                                <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            @endif
                            
                            <form action="{{ route('docs.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate">{{ $doc->title }}</h3>
                        <p class="text-xs text-gray-500">
                            Last edited {{ $doc->updated_at->diffForHumans() }}
                        </p>
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($documents->hasPages())
            <div class="mt-6">
                {{ $documents->links() }}
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">No documents yet</p>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium">
                Create your first document →
            </button>
        </div>
    @endif
</div>

<!-- Create Document Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Create New Document</h3>
        <form action="{{ route('docs.create') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Untitled Document">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="document">📄 Document</option>
                    <option value="spreadsheet">📊 Spreadsheet</option>
                    <option value="presentation">📽️ Presentation</option>
                </select>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
