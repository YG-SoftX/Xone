@extends('developer.layouts.app')

@section('title', 'Team Management')
@section('page-title', 'Team Members')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Team Management</h1>
            <p class="text-gray-600 mt-1">Manage project members and permissions</p>
        </div>
        <button onclick="openInviteModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
            <i class="fas fa-user-plus mr-2"></i>Invite Member
        </button>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Project</label>
        <select id="projectSelector" onchange="loadTeam()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Choose a project...</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['project_id'] }})</option>
            @endforeach
        </select>
    </div>

    <!-- Team Members List -->
    <div id="teamContainer" class="space-y-4">
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view team members</p>
        </div>
    </div>
</div>

<!-- Invite Member Modal -->
<div id="inviteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">Invite Team Member</h3>
            <button onclick="closeInviteModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="inviteForm" class="p-6 space-y-6">
            <input type="hidden" name="projectId" id="modalProjectId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="colleague@example.com">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="viewer">Viewer - Read-only access</option>
                    <option value="editor">Editor - Can manage credentials and settings</option>
                    <option value="billing_admin">Billing Admin - Can manage billing only</option>
                    <option value="owner">Owner - Full access (transfer ownership)</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message (Optional)</label>
                <textarea name="message" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Join our project on YG Developer Console..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeInviteModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Send Invitation</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let currentProjectId = null;

async function loadTeam() {
    const projectId = document.getElementById('projectSelector').value;
    if (!projectId) {
        resetDisplay();
        return;
    }
    
    currentProjectId = projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/team`);
        const data = await response.json();
        
        if (data.success) {
            displayTeam(data.members, data.owner);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function displayTeam(members, owner) {
    const container = document.getElementById('teamContainer');
    
    let html = '';
    
    // Owner section
    html += `
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Project Owner</h3>
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Owner</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-crown text-purple-600"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-800">${owner.name}</h4>
                    <p class="text-sm text-gray-500">${owner.email}</p>
                </div>
            </div>
        </div>
    `;
    
    // Members section
    if (members.length > 0) {
        html += `
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Team Members (${members.length})</h3>
                <div class="space-y-4">
        `;
        
        members.forEach(member => {
            html += `
                <div class="border rounded-lg p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800">${member.name}</h4>
                            <p class="text-sm text-gray-500">${member.email}</p>
                            <p class="text-xs text-gray-400">Joined ${new Date(member.joined_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select onchange="updateRole(${member.id}, this.value)" class="px-3 py-1 border rounded-lg text-sm capitalize">
                            <option value="viewer" ${member.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="editor" ${member.role === 'editor' ? 'selected' : ''}>Editor</option>
                            <option value="billing_admin" ${member.role === 'billing_admin' ? 'selected' : ''}>Billing Admin</option>
                        </select>
                        <button onclick="removeMember(${member.id})" class="px-3 py-1 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="bg-white rounded-xl shadow-sm border p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 mb-4">No team members yet</p>
                <button onclick="openInviteModal()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Invite First Member
                </button>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function openInviteModal() {
    if (!currentProjectId) {
        alert('Please select a project first');
        return;
    }
    document.getElementById('modalProjectId').value = currentProjectId;
    document.getElementById('inviteModal').classList.remove('hidden');
}

function closeInviteModal() {
    document.getElementById('inviteModal').classList.add('hidden');
    document.getElementById('inviteForm').reset();
}

document.getElementById('inviteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    const projectId = data.projectId;
    delete data.projectId;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/team`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeInviteModal();
            loadTeam();
            alert('Invitation sent successfully!');
        } else {
            alert(result.error || 'Failed to send invitation');
        }
    } catch (error) {
        alert('An error occurred');
    }
});

async function updateRole(memberId, newRole) {
    try {
        const response = await fetch(`/api/developer-console/projects/${currentProjectId}/team/${memberId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ role: newRole })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Role updated successfully');
        } else {
            alert(result.error || 'Failed to update role');
        }
    } catch (error) {
        alert('Update failed');
    }
}

async function removeMember(memberId) {
    if (!confirm('Remove this team member?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/projects/${currentProjectId}/team/${memberId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            loadTeam();
        } else {
            alert(result.error || 'Failed to remove member');
        }
    } catch (error) {
        alert('Removal failed');
    }
}

function resetDisplay() {
    document.getElementById('teamContainer').innerHTML = `
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view team members</p>
        </div>
    `;
}
</script>
@endpush
@endsection
