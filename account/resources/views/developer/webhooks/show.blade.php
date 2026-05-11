@extends('developer.layouts.app')

@section('title', 'Webhook Details')
@section('page-title', $webhook['name'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $webhook['name'] }}</h1>
            <p class="text-gray-600 mt-1 font-mono">{{ $webhook['url'] }}</p>
        </div>
        <div class="flex gap-3">
            <button onclick="testWebhook()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-play mr-2"></i>Test
            </button>
            <a href="{{ route('developer.webhooks.index', ['projectId' => $webhook['project_id']]) }}" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Webhook Info -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-gray-500 mb-1">Status</p>
                <span class="inline-block px-3 py-1 rounded-full text-sm {{ $webhook['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $webhook['is_active'] ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">Events</p>
                <p class="font-medium">{{ count(is_string($webhook['events']) ? json_decode($webhook['events'], true) : $webhook['events']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Deliveries</p>
                <p class="font-medium">{{ number_format($webhook['total_deliveries']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">Success Rate</p>
                <p class="font-medium">{{ $webhook['success_rate'] }}%</p>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t">
            <p class="text-sm text-gray-500 mb-2">Secret Key (for HMAC verification)</p>
            <div class="flex gap-2">
                <input type="password" id="secretKey" value="{{ $webhook['secret'] }}" readonly class="flex-1 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-mono text-sm">
                <button onclick="toggleSecretVisibility()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="copySecret()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Delivery History -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Delivery History</h3>
            <button onclick="loadDeliveries()" class="px-3 py-1 border rounded-lg hover:bg-gray-50 text-sm">
                <i class="fas fa-sync mr-1"></i>Refresh
            </button>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Response Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempts</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody id="deliveriesTable" class="divide-y divide-gray-100">
                @forelse($deliveries as $delivery)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($delivery['created_at'])->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 font-mono text-sm">{{ $delivery['event_type'] }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $delivery['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $delivery['success'] ? 'Success' : 'Failed' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $delivery['status_code'] ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $delivery['attempt'] }}</td>
                        <td class="px-6 py-4">
                            <button onclick="redeliver({{ $delivery['id'] }})" class="px-2 py-1 border rounded hover:bg-gray-50 text-xs">
                                Redeliver
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No deliveries yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function toggleSecretVisibility() {
    const input = document.getElementById('secretKey');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function copySecret() {
    const input = document.getElementById('secretKey');
    input.select();
    document.execCommand('copy');
    alert('Secret copied to clipboard!');
}

async function testWebhook() {
    if (!confirm('Send test event?')) return;
    
    try {
        const response = await fetch('/api/developer-console/webhooks/{{ $webhook["id"] }}/test', {
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

async function redeliver(deliveryId) {
    if (!confirm('Redeliver this event?')) return;
    
    try {
        const response = await fetch(`/api/developer-console/webhooks/deliveries/${deliveryId}/redeliver`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Redelivery queued');
            loadDeliveries();
        }
    } catch (error) {
        alert('Redelivery failed');
    }
}

async function loadDeliveries() {
    try {
        const response = await fetch('/api/developer-console/webhooks/{{ $webhook["id"] }}/deliveries');
        const data = await response.json();
        
        if (data.success) {
            // Update table with new deliveries
            window.location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
@endpush
@endsection
