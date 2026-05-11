@extends('layouts.app')
@section('title', 'My Drive')

@section('drive-content')
<div class="max-w-7xl mx-auto">
    
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
        <a href="{{ route('drive.index') }}" class="hover:text-blue-600 transition">My Drive</a>
        @if(isset($currentFolder))
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 font-medium">{{ $currentFolder->name }}</span>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Drive</h1>
        <div class="flex items-center gap-2">
            <div class="bg-gray-100 rounded-lg p-1 flex">
                <button class="p-2 rounded bg-white shadow-sm text-blue-600">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="p-2 rounded text-gray-500 hover:text-gray-700">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Folders --}}
    @if(isset($folders) && $folders->count())
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Folders</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach($folders as $folder)
            <a href="{{ route('drive.folder', $folder->id) }}" 
               class="file-card bg-white border border-gray-200 rounded-xl p-4 hover:border-blue-300 group">
                <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                    <i class="fas fa-folder text-3xl text-blue-500"></i>
                </div>
                <div class="text-sm font-medium text-gray-900 text-center truncate">{{ $folder->name }}</div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Files --}}
    @if(isset($files) && $files->count())
    <div>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Files</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach($files as $file)
            @php
                $iconClass = match($file->mime_type ?? '') {
                    'application/pdf' => 'fa-file-pdf text-red-500',
                    'image/jpeg', 'image/png', 'image/gif' => 'fa-file-image text-purple-500',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word text-blue-500',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel text-green-500',
                    default => 'fa-file text-gray-500'
                };
                $bgClass = match($file->mime_type ?? '') {
                    'application/pdf' => 'from-red-100 to-red-200',
                    'image/jpeg', 'image/png', 'image/gif' => 'from-purple-100 to-purple-200',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'from-blue-100 to-blue-200',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'from-green-100 to-green-200',
                    default => 'from-gray-100 to-gray-200'
                };
            @endphp
            
            <div class="file-card bg-white border border-gray-200 rounded-xl p-4 hover:border-blue-300 group cursor-pointer relative"
                 onclick="previewFile({{ $file->id }})">
                
                {{-- Actions menu --}}
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                    <button class="p-1.5 bg-white rounded-lg shadow-md hover:bg-gray-50">
                        <i class="fas fa-ellipsis-v text-gray-600 text-xs"></i>
                    </button>
                </div>

                <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br {{ $bgClass }} rounded-xl flex items-center justify-center">
                    <i class="fas {{ $iconClass }} text-3xl"></i>
                </div>
                <div class="text-sm font-medium text-gray-900 text-center truncate mb-1">{{ $file->name }}</div>
                <div class="text-xs text-gray-500 text-center">{{ $file->size_formatted ?? 'Unknown size' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if((!isset($folders) || !$folders->count()) && (!isset($files) || !$files->count()))
    <div class="text-center py-20">
        <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-blue-100 to-green-100 rounded-full flex items-center justify-center">
            <i class="fab fa-google-drive text-5xl text-blue-500"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Drive is empty</h3>
        <p class="text-gray-500 mb-6">Upload files or create folders to get started</p>
        <button onclick="openUploadModal()" 
                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-green-500 hover:from-blue-600 hover:to-green-600 text-white rounded-xl font-medium transition shadow-md">
            <i class="fas fa-upload mr-2"></i>Upload Files
        </button>
    </div>
    @endif
</div>

{{-- Upload Modal --}}
<div id="upload-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fade-in">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Upload to Drive</h3>
            <button onclick="closeUploadModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center hover:border-blue-400 transition cursor-pointer"
                 onclick="document.getElementById('file-input').click()">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-700 font-medium mb-2">Drag & drop files here</p>
                <p class="text-sm text-gray-500">or click to browse</p>
                <input type="file" id="file-input" multiple class="hidden" onchange="handleFileSelect(event)">
            </div>
            
            <div id="upload-progress" class="mt-4 hidden">
                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                    <span>Uploading...</span>
                    <span id="upload-percent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="upload-bar" class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all" style="width: 0%"></div>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
            <button onclick="closeUploadModal()" class="px-5 py-2.5 text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-100 transition">Cancel</button>
            <button onclick="startUpload()" class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-green-500 hover:from-blue-600 hover:to-green-600 text-white rounded-xl font-medium transition shadow-md">Upload</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openUploadModal() {
    document.getElementById('upload-modal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('upload-modal').classList.add('hidden');
    document.getElementById('upload-progress').classList.add('hidden');
}

function handleFileSelect(event) {
    const files = event.target.files;
    console.log('Selected files:', files.length);
    // TODO: Implement file upload logic
}

function startUpload() {
    document.getElementById('upload-progress').classList.remove('hidden');
    // Simulate upload progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        document.getElementById('upload-bar').style.width = progress + '%';
        document.getElementById('upload-percent').textContent = progress + '%';
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                closeUploadModal();
                location.reload();
            }, 500);
        }
    }, 200);
}

function previewFile(fileId) {
    window.location.href = `/drive/file/${fileId}/preview`;
}
</script>
@endpush
@endsection
