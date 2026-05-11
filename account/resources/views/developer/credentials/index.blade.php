@extends('developer.layouts.app')

@section('title', 'Credentials')
@section('page-title', 'API Credentials')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">API Credentials</h1>
            <p class="text-gray-600 mt-1">Manage API keys, OAuth clients, and service accounts</p>
        </div>
        <button onclick="openCreateCredentialModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i>Create Credential
        </button>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Project</label>
        <select id="projectSelector" onchange="loadCredentials()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Choose a project...</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['project_id'] }})</option>
            @endforeach
        </select>
    </div>

    <!-- Credentials List -->
    <div id="credentialsContainer" class="space-y-4">
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-key text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view credentials</p>
        </div>
    </div>
</div>

<!-- Create Credential Modal -->
<div id="createCredentialModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Create New Credential</h3>
            <button onclick="closeCreateCredentialModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="createCredentialForm" class="p-6 space-y-6">
            <input type="hidden" name="projectId" id="modalProjectId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Credential Type *</label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="api_key">API Key</option>
                    <option value="oauth_client">OAuth Client</option>
                    <option value="service_account">Service Account</option>
                    <option value="webhook_secret">Webhook Secret</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Production API Key">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Scopes (Optional)</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="scopes[]" value="read" class="rounded">
                        <span class="text-sm">Read access</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="scopes[]" value="write" class="rounded">
                        <span class="text-sm">Write access</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="scopes[]" value="delete" class="rounded">
                        <span class="text-sm">Delete access</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Restrictions (Optional)</label>
                <textarea name="restrictions[ip_addresses]" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="203.0.113.0, 198.51.100.0"></textarea>
                <p class="text-xs text-gray-500 mt-1">Comma-separated IP addresses. Leave empty for no restriction.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (Optional)</label>
                <input type="date" name="expires_at" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateCredentialModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Credential</button>
            </div>
        </form>
    </div>
</div>

<!-- Secret Display Modal -->
<div id="secretModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b bg-yellow-50 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>Save Your Secret Key</h3>
            <button onclick="closeSecretModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <p class="text-red-700 text-sm"><strong>Warning:</strong> This secret will only be shown once. Save it securely now!</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Your Secret Key</label>
                <div class="flex gap-2">
                    <input type="text" id="secretValue" readonly class="flex-1 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-mono text-sm">
                    <button onclick="copySecret()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                <p class="text-blue-700 text-sm"><strong>Tip:</strong> Store this in a secure location like a password manager or environment variable.</p>
            </div>
            
            <button onclick="closeSecretModal()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-check mr-2"></i>I've Saved My Secret
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentProjectId = null;

async function loadCredentials() {
    const projectId = document.getElementById('projectSelector').value;
    if (!projectId) {
        document.getElementById('credentialsContainer').innerHTML = `
            <div class="text-center py-12 bg-white rounded-xl border">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600">Select a project to view credentials</p>
            </div>
        `;
        return;
    }
    
    currentProjectId = projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/credentials`);
        const data = await response.json();
        
        if (data.success) {
            displayCredentials(data.credentials);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function displayCredentials(credentials) {
    const container = document.getElementById('credentialsContainer');
    
    if (credentials.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-white rounded-xl border">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 mb-4">No credentials yet</p>
                <button onclick="openCreateCredentialModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Create First Credential
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = credentials.map(cred => `
        <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-all">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-key text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">${cred.name}</h3>
                            <p class="text-sm text-gray-500 font-mono">${cred.identifier}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Type</p>
                            <p class="font-medium capitalize">${cred.type.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Status</p>
                            <span class="inline-block px-2 py-1 rounded text-xs ${cred.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                ${cred.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500">Requests</p>
                            <p class="font-medium">${cred.total_requests.toLocaleString()}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Last Used</p>
                            <p class="font-medium">${cred.last_used_at ? new Date(cred.last_used_at).toLocaleDateString() : 'Never'}</p>
                        </div>
                    </div>
                    
                    ${cred.expires_at ? `
                        <div class="mt-3 text-sm">
                            <span class="text-gray-500">Expires:</span>
                            <span class="font-medium ${new Date(cred.expires_at) < new Date() ? 'text-red-600' : 'text-gray-800'}">
                                ${new Date(cred.expires_at).toLocaleDateString()}
                            </span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="flex gap-2 ml-4">
                    <button onclick="rotateCredential(${cred.id})" class="px-3 py-2 border rounded-lg hover:bg-gray-50 text-sm" title="Rotate Secret">
                        <i class="fas fa-sync"></i>
                    </button>
                    <button onclick="toggleCredential(${cred.id}, ${cred.is_active})" class="px-3 py-2 border rounded-lg hover:bg-gray-50 text-sm" title="${cred.is_active ? 'Deactivate' : 'Activate'}">
                        <i class="fas fa-power-off"></i>
                    </button>
                    <button onclick="deleteCredential(${cred.id})" class="px-3 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function openCreateCredentialModal() {
    if (!currentProjectId) {
        alert('Please select a project first');
        return;
    }
    document.getElementById('modalProjectId').value = currentProjectId;
    document.getElementById('createCredentialModal').classList.remove('hidden');
}

function closeCreateCredentialModal() {
    document.getElementById('createCredentialModal').classList.add('hidden');
    document.getElementById('createCredentialForm').reset();
}

document.getElementById('createCredentialForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Process scopes array
    const scopes = formData.getAll('scopes[]');
    if (scopes.length > 0) {
        data.scopes = scopes;
    }
    
    // Process IP restrictions
    if (data['restrictions[ip_addresses]']) {
        data.restrictions = {
            ip_addresses: data['restrictions[ip_addresses]'].split(',').map(ip => ip.trim()).filter(ip => ip)
        };
        delete data['restrictions[ip_addresses]'];
    }
    
    const projectId = data.projectId;
    delete data.projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/credentials`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeCreateCredentialModal();
            showSecret(result.credential.secret);
            loadCredentials();
        } else {
            alert(result.error || 'Failed to create credential');
        }
    } catch (error) {
        alert('An error occurred');
    }
});

function showSecret(secret) {
    document.getElementById('secretValue').value = secret;
    document.getElementById('secretModal').classList.remove('hidden');
}

function closeSecretModal() {
    document.getElementById('secretModal').classList.add('hidden');
}

function copySecret() {
    const secretInput = document.getElementById('secretValue');
    secretInput.select();
    document.execCommand('copy');
    alert('Secret copied to clipboard!');
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
            showSecret(result.credential.new_secret);
            loadCredentials();
        }
    } catch (error) {
        alert('Failed to rotate credential');
    }
}

async function toggleCredential(id, currentStatus) {
    try {
        const response = await fetch(`/api/developer-console/credentials/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            loadCredentials();
        }
    } catch (error) {
        alert('Failed to update credential');
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
        
        const result = await response.json();
        if (result.success) {
            loadCredentials();
        }
    } catch (error) {
        alert('Failed to delete credential');
    }
}
</script>
@endpush
@endsection
