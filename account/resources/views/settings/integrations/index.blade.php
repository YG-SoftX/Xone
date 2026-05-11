@extends('layouts.app')
@php /** @var \Illuminate\Database\Eloquent\Collection $webhooks */ @endphp

@section('title', 'Integration Settings')
@section('page-title', 'API Access & Webhooks')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="space-y-6">
        <!-- API Tokens Management -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-key text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">API Tokens</h2>
                        <p class="text-sm text-gray-500">Manage access tokens for API authentication</p>
                    </div>
                </div>
                <button onclick="openCreateTokenModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-plus mr-2"></i>Create Token
                </button>
            </div>
            
            @if(session('new_token'))
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                    <p class="text-yellow-700 text-sm"><strong>Important:</strong> Copy your new token now. You won't be able to see it again!</p>
                    <div class="mt-2 flex gap-2">
                        <input type="text" value="{{ session('new_token') }}" readonly class="flex-1 px-3 py-2 bg-white border border-yellow-300 rounded font-mono text-sm">
                        <button onclick="copyToClipboard(this.previousElementSibling)" class="px-3 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            @endif
            
            @if($apiTokens->count() > 0)
                <div class="space-y-3">
                    @foreach($apiTokens as $token)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="font-medium text-gray-800">{{ $token->name }}</h4>
                                        @if($token->last_used_at)
                                            <span class="text-xs text-gray-500">Last used {{ \Carbon\Carbon::parse($token->last_used_at)->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Created {{ \Carbon\Carbon::parse($token->created_at)->format('M d, Y') }}</span>
                                        @if($token->expires_at)
                                            <span><i class="fas fa-clock mr-1"></i> Expires {{ \Carbon\Carbon::parse($token->expires_at)->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <form action="{{ route('settings.integrations.tokens.revoke', $token->id) }}" method="POST" onsubmit="return confirm('Revoke this token? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">
                                        <i class="fas fa-trash mr-1"></i> Revoke
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-key text-4xl mb-3 opacity-50"></i>
                    <p>No API tokens yet. Create one to get started!</p>
                </div>
            @endif
        </div>

        <!-- Webhook Endpoints -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-webhook text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Webhook Endpoints</h2>
                        <p class="text-sm text-gray-500">Receive real-time event notifications</p>
                    </div>
                </div>
                <button onclick="openCreateWebhookModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add Webhook
                </button>
            </div>
            
            @if($webhooks->count() > 0)
                <div class="space-y-3">
                    @foreach($webhooks as $webhook)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="font-medium text-gray-800">{{ $webhook->url }}</h4>
                                        <span class="px-2 py-1 {{ $webhook->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} rounded text-xs">
                                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    @if($webhook->description)
                                        <p class="text-sm text-gray-600 mb-2">{{ $webhook->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span><i class="fas fa-check-circle mr-1 text-green-600"></i> Success: {{ $webhook->success_count }}</span>
                                        <span><i class="fas fa-times-circle mr-1 text-red-600"></i> Failed: {{ $webhook->failure_count }}</span>
                                        @if($webhook->last_triggered_at)
                                            <span><i class="fas fa-clock mr-1"></i> Last triggered {{ \Carbon\Carbon::parse($webhook->last_triggered_at)->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="testWebhook({{ $webhook->id }})" class="px-3 py-2 border border-blue-300 text-blue-600 rounded hover:bg-blue-50 text-sm">
                                        <i class="fas fa-paper-plane"></i> Test
                                    </button>
                                    <form action="{{ route('settings.integrations.webhooks.toggle', $webhook->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-2 border border-gray-300 text-gray-600 rounded hover:bg-gray-50 text-sm">
                                            <i class="fas fa-{{ $webhook->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('settings.integrations.webhooks.delete', $webhook->id) }}" method="POST" onsubmit="return confirm('Delete this webhook?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 border border-red-300 text-red-600 rounded hover:bg-red-50 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded p-3">
                                <p class="text-xs text-gray-600 mb-1">Events subscribed:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(json_decode($webhook->events) as $event)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ $event }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-webhook text-4xl mb-3 opacity-50"></i>
                    <p>No webhook endpoints configured. Add one to receive event notifications!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create API Token Modal -->
<div id="createTokenModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Create API Token</h3>
            <button onclick="closeCreateTokenModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="{{ route('settings.integrations.tokens.create') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Token Name</label>
                    <input type="text" name="token_name" required placeholder="e.g., Production API Key" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (Optional)</label>
                    <input type="date" name="expires_at" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for no expiration</p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeCreateTokenModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Create Token
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Webhook Modal -->
<div id="createWebhookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl max-w-lg w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Add Webhook Endpoint</h3>
            <button onclick="closeCreateWebhookModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="{{ route('settings.integrations.webhooks.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                    <input type="url" name="url" required placeholder="https://your-domain.com/webhook" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="What is this webhook for?" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Events to Subscribe</label>
                    <div class="space-y-2 border border-gray-300 rounded-lg p-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="events[]" value="user.created" class="rounded">
                            <span class="text-sm">User Created</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="events[]" value="user.updated" class="rounded">
                            <span class="text-sm">User Updated</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="events[]" value="login.success" class="rounded">
                            <span class="text-sm">Successful Login</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="events[]" value="login.failed" class="rounded">
                            <span class="text-sm">Failed Login</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="events[]" value="payment.completed" class="rounded">
                            <span class="text-sm">Payment Completed</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Select at least one event</p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeCreateWebhookModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Add Webhook
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateTokenModal() {
    document.getElementById('createTokenModal').classList.remove('hidden');
}

function closeCreateTokenModal() {
    document.getElementById('createTokenModal').classList.add('hidden');
}

function openCreateWebhookModal() {
    document.getElementById('createWebhookModal').classList.remove('hidden');
}

function closeCreateWebhookModal() {
    document.getElementById('createWebhookModal').classList.add('hidden');
}

function copyToClipboard(input) {
    input.select();
    document.execCommand('copy');
    
    // Show feedback
    const originalValue = input.value;
    input.value = 'Copied!';
    setTimeout(() => {
        input.value = originalValue;
    }, 1000);
}

function testWebhook(webhookId) {
    if (confirm('Send test webhook to this endpoint?')) {
        fetch(`/settings/integrations/webhooks/${webhookId}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            alert('Test webhook dispatched! Check your endpoint.');
            location.reload();
        })
        .catch(error => {
            alert('Error sending test webhook');
        });
    }
}
</script>
@endsection
