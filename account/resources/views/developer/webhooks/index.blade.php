@extends('developer.layouts.app')

@section('title', 'Webhooks')
@section('page-title', 'Webhook Configuration')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Webhooks</h1>
            <p class="text-gray-600 mt-1">Configure event notifications and delivery tracking</p>
        </div>
        <button onclick="openCreateWebhookModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i>Create Webhook
        </button>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Project</label>
        <select id="projectSelector" onchange="loadWebhooks()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Choose a project...</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['project_id'] }})</option>
            @endforeach
        </select>
    </div>

    <!-- Webhooks List -->
    <div id="webhooksContainer" class="space-y-4">
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-webhook text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view webhooks</p>
        </div>
    </div>
</div>

<!-- Create Webhook Modal -->
<div id="createWebhookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Create New Webhook</h3>
            <button onclick="closeCreateWebhookModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="createWebhookForm" class="p-6 space-y-6">
            <input type="hidden" name="projectId" id="modalProjectId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Production Webhook">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Endpoint URL *</label>
                <input type="url" name="url" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="https://your-app.com/webhook">
                <p class="text-xs text-gray-500 mt-1">Must be HTTPS for production</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Events *</label>
                <div class="space-y-2 border rounded-lg p-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="user.created" class="rounded">
                        <span class="text-sm">User Created</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="user.updated" class="rounded">
                        <span class="text-sm">User Updated</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="payment.completed" class="rounded">
                        <span class="text-sm">Payment Completed</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="payment.failed" class="rounded">
                        <span class="text-sm">Payment Failed</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="*" class="rounded">
                        <span class="text-sm">All Events</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Secret (Optional)</label>
                <input type="text" name="secret" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Leave empty to auto-generate">
                <p class="text-xs text-gray-500 mt-1">Used for HMAC signature verification</p>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateWebhookModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Webhook</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let currentProjectId = null;

async function loadWebhooks() {
    const projectId = document.getElementById('projectSelector').value;
    if (!projectId) {
        resetDisplay();
        return;
    }
    
    currentProjectId = projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/webhooks`);
        const data = await response.json();
        
        if (data.success) {
            displayWebhooks(data.webhooks);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function displayWebhooks(webhooks) {
    const container = document.getElementById('webhooksContainer');
    
    if (webhooks.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-white rounded-xl border">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-webhook text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 mb-4">No webhooks configured</p>
                <button onclick="openCreateWebhookModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Create First Webhook
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = webhooks.map(webhook => `
        <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-webhook text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">${webhook.name}</h3>
                            <p class="text-sm text-gray-500 font-mono">${webhook.url}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Status</p>
                            <span class="inline-block px-2 py-1 rounded text-xs ${webhook.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                ${webhook.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500">Events</p>
                            <p class="font-medium">${webhook.events_count || webhook.events?.length || 0}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Deliveries</p>
                            <p class="font-medium">${webhook.total_deliveries?.toLocaleString() || 0}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Success Rate</p>
                            <p class="font-medium">${webhook.success_rate || 0}%</p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p class="text-xs text-gray-500">Events: ${webhook.events?.join(', ') || 'All events'}</p>
                    </div>
                </div>
                
                <div class="flex gap-2 ml-4">
                    <button onclick="viewDeliveries(${webhook.id})" class="px-3 py-2 border rounded-lg hover:bg-gray-50 text-sm" title="View Deliveries">
                        <i class="fas fa-list"></i>
                    </button>
                    <button onclick="testWebhook(${webhook.id})" class="px-3 py-2 border rounded-lg hover:bg-gray-50 text-sm" title="Test Webhook">
                        <i class="fas fa-play"></i>
                    </button>
                    <button onclick="toggleWebhook(${webhook.id}, ${webhook.is_active})" class="px-3 py-2 border rounded-lg hover:bg-gray-50 text-sm" title="${webhook.is_active ? 'Deactivate' : 'Activate'}">
                        <i class="fas fa-power-off"></i>
                    </button>
                    <button onclick="deleteWebhook(${webhook.id})" class="px-3 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function openCreateWebhookModal() {
    if (!currentProjectId) {
        alert('Please select a project first');
        return;
    }
    document.getElementById('modalProjectId').value = currentProjectId;
    document.getElementById('createWebhookModal').classList.remove('hidden');
}

function closeCreateWebhookModal() {
    document.getElementById('createWebhookModal').classList.add('hidden');
    document.getElementById('createWebhookForm').reset();
}

document.getElementById('createWebhookForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Process events array
    const events = formData.getAll('events[]');
    data.events = events;
    
    const projectId = data.projectId;
    delete data.projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/webhooks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeCreateWebhookModal();
            loadWebhooks();
            alert('Webhook created successfully! Secret: ' + result.webhook.secret);
        } else {
            alert(result.error || 'Failed to create webhook');
        }
    } catch (error) {
        alert('An error occurred');
    }
});

async function toggleWebhook(id, currentStatus) {
    try {
        const response = await fetch(`/api/developer-console/webhooks/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            loadWebhooks();
        }
    } catch (error) {
        alert('Failed to update webhook');
    }
}

async function testWebhook(id) {
    if (!confirm('Send test event to this webhook?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/webhooks/${id}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Test event sent successfully');
        } else {
            alert(result.error || 'Test failed');
        }
    } catch (error) {
        alert('Test request failed');
    }
}

async function viewDeliveries(id) {
    window.location.href = `/developer/webhooks/${id}`;
}

async function deleteWebhook(id) {
    if (!confirm('Delete this webhook permanently?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/webhooks/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            loadWebhooks();
        }
    } catch (error) {
        alert('Failed to delete webhook');
    }
}

function resetDisplay() {
    document.getElementById('webhooksContainer').innerHTML = `
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-webhook text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view webhooks</p>
        </div>
    `;
}
</script>
@endpush
@endsection
