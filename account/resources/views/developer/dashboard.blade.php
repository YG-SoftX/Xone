@extends('developer.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Developer Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Welcome Banner -->
    <div class="gradient-bg rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Welcome back, {{ $user['name'] }}! 👋</h1>
                <p class="text-indigo-100">Manage your projects, credentials, and API usage across the YG ecosystem.</p>
            </div>
            <button onclick="openCreateProjectModal()" class="px-6 py-3 bg-white text-indigo-600 rounded-xl font-semibold hover:bg-indigo-50 transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>New Project
            </button>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover transition-all border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder text-blue-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-500">Total</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ $stats['projects_count'] }}</h3>
            <p class="text-sm text-gray-600">Projects</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover transition-all border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-key text-green-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-500">Active</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ $stats['active_credentials'] }}</h3>
            <p class="text-sm text-gray-600">API Keys</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover transition-all border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-purple-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-500">Today</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ number_format($stats['requests_today']) }}</h3>
            <p class="text-sm text-gray-600">API Requests</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover transition-all border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-yellow-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-500">Balance</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${{ $stats['billing_balance'] }}</h3>
            <p class="text-sm text-gray-600">Current Balance</p>
        </div>
    </div>
    
    <!-- Recent Projects & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Projects -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Projects</h3>
                <a href="{{ route('developer.projects.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            @if(count($recent_projects) > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($recent_projects as $project)
                        <div class="px-6 py-4 hover:bg-gray-50 transition-all">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800 mb-1">{{ $project['name'] }}</h4>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span><i class="fas fa-code mr-1"></i>{{ $project['project_id'] }}</span>
                                        <span><i class="fas fa-layer-group mr-1"></i>{{ $project['subscriptions_count'] }} APIs</span>
                                        <span class="px-2 py-1 rounded-full text-xs {{ $project['environment'] === 'production' ? 'bg-green-100 text-green-700' : ($project['environment'] === 'staging' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                            {{ ucfirst($project['environment']) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs {{ $project['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $project['is_active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                    <a href="{{ route('developer.projects.show', $project['id']) }}" class="text-indigo-600 hover:text-indigo-700">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-open text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-600 mb-4">No projects yet. Create your first project to get started!</p>
                    <button onclick="openCreateProjectModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all">
                        Create Project
                    </button>
                </div>
            @endif
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-6">Quick Actions</h3>
            
            <div class="space-y-3">
                <a href="{{ route('developer.credentials.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all group">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200">
                        <i class="fas fa-key text-indigo-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Manage Credentials</p>
                        <p class="text-xs text-gray-500">API keys & OAuth clients</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="{{ route('developer.analytics.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-all group">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200">
                        <i class="fas fa-chart-bar text-purple-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">View Analytics</p>
                        <p class="text-xs text-gray-500">Usage metrics & trends</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="{{ route('developer.webhooks.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-all group">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200">
                        <i class="fas fa-webhook text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Configure Webhooks</p>
                        <p class="text-xs text-gray-500">Event notifications</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="{{ route('developer.team.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50 transition-all group">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200">
                        <i class="fas fa-users text-orange-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Manage Team</p>
                        <p class="text-xs text-gray-500">Invite members</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="{{ route('developer.billing.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-yellow-300 hover:bg-yellow-50 transition-all group">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:bg-yellow-200">
                        <i class="fas fa-credit-card text-yellow-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Billing & Invoices</p>
                        <p class="text-xs text-gray-500">Payment methods</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Create Project Modal -->
<div id="createProjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Create New Project</h3>
            <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="createProjectForm" class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Name *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="My Awesome App">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Brief description of your project..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Website URL</label>
                <input type="url" name="website_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="https://example.com">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Environment *</label>
                <select name="environment" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="development">Development</option>
                    <option value="staging">Staging</option>
                    <option value="production">Production</option>
                </select>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateProjectModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateProjectModal() {
    document.getElementById('createProjectModal').classList.remove('hidden');
}

function closeCreateProjectModal() {
    document.getElementById('createProjectModal').classList.add('hidden');
    document.getElementById('createProjectForm').reset();
}

document.getElementById('createProjectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/api/developer-console/dashboard/create-project', {
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
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});
</script>
@endpush
@endsection
