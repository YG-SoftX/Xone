@extends('developer.layouts.app')

@section('title', 'Projects')
@section('page-title', 'Projects')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Your Projects</h1>
            <p class="text-gray-600 mt-1">Manage your API projects across the YG ecosystem</p>
        </div>
        <button onclick="openCreateProjectModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i>New Project
        </button>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="searchInput" placeholder="Search projects..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <select id="environmentFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">All Environments</option>
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
            </select>
            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>
    
    <!-- Projects Grid -->
    <div id="projectsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($projects as $project)
            <div class="project-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover transition-all" 
                 data-name="{{ strtolower($project['name']) }}" 
                 data-environment="{{ $project['environment'] }}"
                 data-active="{{ $project['is_active'] }}">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center">
                            <i class="fas fa-folder text-white text-xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs {{ $project['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $project['is_active'] ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    
                    <h3 class="font-semibold text-gray-800 mb-2">{{ $project['name'] }}</h3>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $project['description'] ?? 'No description' }}</p>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-gray-600">
                            <i class="fas fa-code w-4"></i>
                            <span class="font-mono text-xs">{{ $project['project_id'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <i class="fas fa-layer-group w-4"></i>
                            <span>{{ $project['subscriptions_count'] }} APIs enabled</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <i class="fas fa-key w-4"></i>
                            <span>{{ $project['active_credentials_count'] }} active keys</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <i class="fas fa-globe w-4"></i>
                            <span class="px-2 py-0.5 rounded text-xs {{ $project['environment'] === 'production' ? 'bg-green-100 text-green-700' : ($project['environment'] === 'staging' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                {{ ucfirst($project['environment']) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                    <a href="{{ route('developer.projects.show', $project['id']) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                        View Details <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                    <div class="flex gap-2">
                        <button onclick="toggleProject({{ $project['id'] }}, {{ $project['is_active'] ? 'false' : 'true' }})" class="text-gray-400 hover:text-gray-600" title="{{ $project['is_active'] ? 'Deactivate' : 'Activate' }}">
                            <i class="fas fa-power-off"></i>
                        </button>
                        <button onclick="deleteProject({{ $project['id'] }}, '{{ $project['name'] }}')" class="text-gray-400 hover:text-red-600" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">No projects found</h3>
                <p class="text-gray-600 mb-6">Create your first project to start building with YG APIs</p>
                <button onclick="openCreateProjectModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all">
                    Create Your First Project
                </button>
            </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    @if($pagination['last_page'] > 1)
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4">
            <p class="text-sm text-gray-600">
                Showing {{ $pagination['current_page'] }} of {{ $pagination['last_page'] }} pages ({{ $pagination['total'] }} total)
            </p>
            <div class="flex gap-2">
                @if($pagination['current_page'] > 1)
                    <a href="?page={{ $pagination['current_page'] - 1 }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Previous</a>
                @endif
                @if($pagination['current_page'] < $pagination['last_page'])
                    <a href="?page={{ $pagination['current_page'] + 1 }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Next</a>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Create Project Modal (same as dashboard) -->
<div id="createProjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Create New Project</h3>
            <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="createProjectForm" class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Name *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="My Awesome App">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Brief description..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Website URL</label>
                <input type="url" name="website_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="https://example.com">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Environment *</label>
                <select name="environment" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="development">Development</option>
                    <option value="staging">Staging</option>
                    <option value="production">Production</option>
                </select>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateProjectModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Project</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Filter functionality
document.getElementById('searchInput').addEventListener('input', filterProjects);
document.getElementById('environmentFilter').addEventListener('change', filterProjects);
document.getElementById('statusFilter').addEventListener('change', filterProjects);

function filterProjects() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const environment = document.getElementById('environmentFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    document.querySelectorAll('.project-card').forEach(card => {
        const name = card.dataset.name;
        const env = card.dataset.environment;
        const active = card.dataset.active;
        
        const matchesSearch = !search || name.includes(search);
        const matchesEnv = !environment || env === environment;
        const matchesStatus = !status || active === status;
        
        card.style.display = (matchesSearch && matchesEnv && matchesStatus) ? 'block' : 'none';
    });
}

// Modal functions
function openCreateProjectModal() {
    document.getElementById('createProjectModal').classList.remove('hidden');
}

function closeCreateProjectModal() {
    document.getElementById('createProjectModal').classList.add('hidden');
    document.getElementById('createProjectForm').reset();
}

// Form submission
document.getElementById('createProjectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/api/developer-console/projects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.error || 'Failed to create project');
        }
    } catch (error) {
        alert('An error occurred');
    }
});

// Toggle project status
async function toggleProject(id, newStatus) {
    if (!confirm(`Are you sure you want to ${newStatus ? 'activate' : 'deactivate'} this project?`)) return;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${id}/activate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        }
    } catch (error) {
        alert('Failed to update project');
    }
}

// Delete project
async function deleteProject(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) return;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        }
    } catch (error) {
        alert('Failed to delete project');
    }
}
</script>
@endpush
@endsection
