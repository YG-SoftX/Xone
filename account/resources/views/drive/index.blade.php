@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Drive Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">YG Drive</h1>
            <p class="text-sm text-gray-600">Cloud Storage</p>
        </div>
        <div class="flex gap-3">
            <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                📁 Upload File
            </button>
            <button onclick="document.getElementById('folderModal').classList.remove('hidden')" 
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                📂 New Folder
            </button>
        </div>
    </div>

    <!-- Breadcrumb -->
    @if($folder)
        <nav class="mb-4 text-sm text-gray-600">
            <a href="{{ route('drive.index') }}" class="hover:text-blue-600">My Drive</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ $folder->name }}</span>
        </nav>
    @endif

    <!-- Storage Usage Bar -->
    <div class="mb-6 bg-white rounded-lg shadow-md p-4">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Storage Used</span>
            <span class="text-sm text-gray-600">
                {{ number_format($storageUsed / 1073741824, 2) }} GB / {{ number_format($storageQuota / 1073741824, 2) }} GB
            </span>
        </div>
        <div class="bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all" 
                 style="width: {{ min(100, ($storageUsed / $storageQuota) * 100) }}%"></div>
        </div>
    </div>

    <!-- Folders Section -->
    @if($folders->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Folders</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($folders as $driveFolder)
                    <a href="{{ route('drive.index', ['folder' => $driveFolder->id]) }}" 
                       class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <svg class="w-12 h-12 text-yellow-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-center text-gray-700 truncate">{{ $driveFolder->name }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Files Section -->
    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Files</h2>
        @if($files->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modified</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($files as $file)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-900">{{ $file->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ number_format($file->size_bytes / 1024, 2) }} KB
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $file->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('drive.file.download', $file->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Download</a>
                                    <button onclick="shareFile({{ $file->id }})" class="text-green-600 hover:text-green-900 mr-3">Share</button>
                                    <form action="{{ route('drive.file.destroy', $file->id) }}" method="POST" class="inline" onsubmit="return confirm('Move to trash?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No files uploaded yet</p>
            </div>
        @endif
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Upload File</h3>
        <form action="{{ route('drive.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="folder_id" value="{{ $folder->id ?? '' }}">
            <div class="mb-4">
                <input type="file" name="file" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p class="mt-1 text-xs text-gray-500">Max file size: 100MB</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Folder Modal -->
<div id="folderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">New Folder</h3>
        <form action="{{ route('drive.folder.create') }}" method="POST">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $folder->id ?? '' }}">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Folder Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('folderModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Create</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function shareFile(fileId) {
    const email = prompt('Enter email to share with:');
    if (email) {
        const permission = prompt('Permission (view/edit/comment):', 'view');
        fetch(`/drive/file/${fileId}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ email, permission })
        }).then(response => {
            if (response.ok) {
                alert('File shared successfully!');
            } else {
                alert('Failed to share file');
            }
        });
    }
}
</script>
@endpush
@endsection
