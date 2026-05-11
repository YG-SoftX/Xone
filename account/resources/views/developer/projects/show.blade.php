@extends('developer.layouts.app')

@section('title', $project['name'])
@section('page-title', $project['name'])

@section('content')
<div class="space-y-6">
    <!-- Project Header -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $project['name'] }}</h1>
                    <span class="px-3 py-1 rounded-full text-xs {{ $project['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $project['is_active'] ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs {{ $project['environment'] === 'production' ? 'bg-green-100 text-green-700' : ($project['environment'] === 'staging' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ ucfirst($project['environment']) }}
                    </span>
                </div>
                <p class="text-gray-600 mb-4">{{ $project['description'] ?? 'No description' }}</p>
                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <span><i class="fas fa-code mr-2"></i>{{ $project['project_id'] }}</span>
                    @if($project['website_url'])
                        <a href="{{ $project['website_url'] }}" target="_blank" class="hover:text-indigo-600">
                            <i class="fas fa-globe mr-2"></i>{{ $project['website_url'] }}
                        </a>
                    @endif
                    <span><i class="fas fa-calendar mr-2"></i>Created {{ \Carbon\Carbon::parse($project['created_at'])->diffForHumans() }}</span>
                </div>
            </div>
            <div class="flex gap-3">
                <button onclick="toggleProjectStatus()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-power-off mr-2"></i>{{ $project['is_active'] ? 'Deactivate' : 'Activate' }}
                </button>
                <a href="{{ route('developer.projects.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b">
            <nav class="flex">
                <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">
                    <i class="fas fa-info-circle mr-2"></i>Overview
                </button>
                <button onclick="switchTab('credentials')" id="tab-credentials" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-key mr-2"></i>Credentials
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs">{{ count($project['credentials']) }}</span>
                </button>
                <button onclick="switchTab('subscriptions')" id="tab-subscriptions" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-plug mr-2"></i>APIs
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs">{{ count($project['subscriptions']) }}</span>
                </button>
                <button onclick="switchTab('quotas')" id="tab-quotas" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-tachometer-alt mr-2"></i>Quotas
                </button>
                <button onclick="switchTab('webhooks')" id="tab-webhooks" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-webhook mr-2"></i>Webhooks
                </button>
                <button onclick="switchTab('team')" id="tab-team" class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    <i class="fas fa-users mr-2"></i>Team
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs">{{ count($project['members']) + 1 }}</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div id="content-overview" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-bolt text-white text-xl"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ number_format($project['usage_stats']['requests_today']) }}</h3>
                        <p class="text-sm text-gray-600">Requests Today</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ number_format($project['usage_stats']['requests_this_month']) }}</h3>
                        <p class="text-sm text-gray-600">This Month</p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-800 mb-1">{{ count($project['credentials']) }}</h3>
                        <p class="text-sm text-gray-600">Active Credentials</p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Quick Links</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="{{ route('developer.credentials.index', ['projectId' => $project['id']]) }}" class="p-4 bg-white rounded-lg border hover:border-indigo-300 transition-all">
                            <i class="fas fa-key text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium">Manage Keys</p>
                        </a>
                        <a href="{{ route('developer.analytics.index', ['projectId' => $project['id']]) }}" class="p-4 bg-white rounded-lg border hover:border-purple-300 transition-all">
                            <i class="fas fa-chart-bar text-purple-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium">View Analytics</p>
                        </a>
                        <a href="{{ route('developer.quotas.index', ['projectId' => $project['id']]) }}" class="p-4 bg-white rounded-lg border hover:border-green-300 transition-all">
                            <i class="fas fa-tachometer-alt text-green-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium">Check Quotas</p>
                        </a>
                        <a href="{{ route('developer.webhooks.index', ['projectId' => $project['id']]) }}" class="p-4 bg-white rounded-lg border hover:border-orange-300 transition-all">
                            <i class="fas fa-webhook text-orange-600 text-2xl mb-2"></i>
                            <p class="text-sm font-medium">Webhooks</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Credentials Tab -->
            <div id="content-credentials" class="tab-content hidden">
                @if(count($project['credentials']) > 0)
                    <div class="space-y-4">
                        @foreach($project['credentials'] as $credential)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition-all">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-key text-indigo-600"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">{{ $credential['name'] }}</h4>
                                                <p class="text-sm text-gray-500 font-mono">{{ $credential['identifier'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-600">
                                            <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ ucfirst(str_replace('_', ' ', $credential['type'])) }}</span>
                                            <span><i class="fas fa-calendar mr-1"></i>Last used {{ $credential['last_used_at'] ? \Carbon\Carbon::parse($credential['last_used_at'])->diffForHumans() : 'Never' }}</span>
                                            <span><i class="fas fa-bolt mr-1"></i>{{ number_format($credential['total_requests']) }} requests</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="rotateCredential({{ $credential['id'] }})" class="px-3 py-1 border rounded hover:bg-gray-50 text-sm">
                                            <i class="fas fa-sync mr-1"></i>Rotate
                                        </button>
                                        <button onclick="deleteCredential({{ $credential['id'] }})" class="px-3 py-1 border border-red-300 text-red-600 rounded hover:bg-red-50 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-key text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-600 mb-4">No credentials yet</p>
                        <a href="{{ route('developer.credentials.index', ['projectId' => $project['id']]) }}" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Create First Credential
                        </a>
                    </div>
                @endif
            </div>

            <!-- Subscriptions Tab -->
            <div id="content-subscriptions" class="tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($project['subscriptions'] as $subscription)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cube text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">{{ $subscription['display_name'] }}</h4>
                                    <p class="text-xs text-gray-500">{{ $subscription['product_name'] }}</p>
                                </div>
                            </div>
                            <span class="inline-block px-2 py-1 rounded text-xs {{ $subscription['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $subscription['is_active'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quotas Tab -->
            <div id="content-quotas" class="tab-content hidden">
                <div class="space-y-4">
                    @foreach($project['quotas'] as $quota)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-800">{{ $quota['product_name'] }}</h4>
                                <span class="text-sm text-gray-500">Rate Limit: {{ $quota['rate_limit']['per_minute'] }}/min</span>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-gray-600">Daily Usage</span>
                                        <span class="font-medium">{{ number_format($quota['daily']['used']) }} / {{ number_format($quota['daily']['limit']) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ min(100, $quota['daily']['percentage']) }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ $quota['daily']['remaining'] }} remaining ({{ $quota['daily']['percentage'] }}%)</p>
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-gray-600">Monthly Usage</span>
                                        <span class="font-medium">{{ number_format($quota['monthly']['used']) }} / {{ number_format($quota['monthly']['limit']) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-500 h-2 rounded-full transition-all" style="width: {{ min(100, $quota['monthly']['percentage']) }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ $quota['monthly']['remaining'] }} remaining</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Webhooks Tab -->
            <div id="content-webhooks" class="tab-content hidden">
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-webhook text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-600 mb-4">Webhooks management coming soon</p>
                    <a href="{{ route('developer.webhooks.index', ['projectId' => $project['id']]) }}" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Manage Webhooks
                    </a>
                </div>
            </div>

            <!-- Team Tab -->
            <div id="content-team" class="tab-content hidden">
                <div class="space-y-4">
                    <!-- Owner -->
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-indigo-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">{{ $project['owner']['name'] }}</h4>
                                    <p class="text-sm text-gray-500">{{ $project['owner']['email'] }}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Owner</span>
                        </div>
                    </div>
                    
                    <!-- Members -->
                    @foreach($project['members'] as $member)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-800">{{ $member['name'] }}</h4>
                                        <p class="text-sm text-gray-500">{{ $member['email'] }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium capitalize">{{ str_replace('_', ' ', $member['role']) }}</span>
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="pt-4">
                        <a href="{{ route('developer.team.index', ['projectId' => $project['id']]) }}" class="px-6 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50">
                            <i class="fas fa-plus mr-2"></i>Invite Member
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Show selected content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Update tab styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName).classList.add('border-indigo-600', 'text-indigo-600');
}

async function toggleProjectStatus() {
    if (!confirm('Are you sure?')) return;
    
    try {
        const response = await fetch('/api/developer-console/projects/{{ $project["id"] }}/activate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        alert('Failed to update project');
    }
}

async function rotateCredential(id) {
    if (!confirm('This will invalidate the old secret. Continue?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/credentials/${id}/rotate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            alert('New secret: ' + result.credential.new_secret + '\n\nSave it now - it won\'t be shown again!');
        }
    } catch (error) {
        alert('Failed to rotate credential');
    }
}

async function deleteCredential(id) {
    if (!confirm('Delete this credential permanently?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/credentials/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        alert('Failed to delete credential');
    }
}
</script>
@endpush
@endsection
