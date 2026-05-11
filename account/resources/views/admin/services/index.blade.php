@extends('admin.master-layout')

@section('title', 'Service Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Service Management</h1>
            <p class="text-sm text-gray-600 mt-1">Enable or disable any YG Platform service</p>
        </div>
        <form action="{{ route('admin.services.seed') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                🔄 Seed Services
            </button>
        </form>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($services as $service)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Service Header -->
                <div class="p-6 border-b {{ $service->is_enabled ? 'border-green-200 bg-green-50' : ($service->is_maintenance_mode ? 'border-yellow-200 bg-yellow-50' : 'border-red-200 bg-red-50') }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 {{ $service->is_enabled ? 'bg-green-600' : ($service->is_maintenance_mode ? 'bg-yellow-600' : 'bg-red-600') }} rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <div class="flex flex-col gap-2">
                            @if($service->is_enabled && !$service->is_maintenance_mode)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Enabled
                                </span>
                            @elseif($service->is_maintenance_mode)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    🔧 Maintenance
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ✗ Disabled
                                </span>
                            @endif
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $service->service_name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $service->description }}</p>
                </div>

                <!-- Service Stats -->
                <div class="p-4 bg-gray-50 border-b">
                    @php
                        $stats = $usageStats[$service->service_key] ?? null;
                    @endphp
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500">Active Users</p>
                            <p class="font-semibold text-gray-900">{{ $stats ? number_format($stats->active_users) : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">API Calls</p>
                            <p class="font-semibold text-gray-900">{{ $stats ? number_format($stats->api_calls) : 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="p-4 space-y-2">
                    @if($service->is_enabled)
                        <form action="{{ route('admin.services.disable', $service->service_key) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to disable {{ $service->service_name }}? This will block all user access.')">
                            @csrf
                            <input type="hidden" name="reason" value="Disabled by admin via dashboard">
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm">
                                Disable Service
                            </button>
                        </form>
                        
                        @if(!$service->is_maintenance_mode)
                            <button onclick="showMaintenanceModal('{{ $service->service_key }}')" 
                                    class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors text-sm">
                                Enable Maintenance Mode
                            </button>
                        @else
                            <form action="{{ route('admin.services.maintenance.disable', $service->service_key) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm">
                                    Disable Maintenance Mode
                                </button>
                            </form>
                        @endif
                    @else
                        <form action="{{ route('admin.services.enable', $service->service_key) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm">
                                Enable Service
                            </button>
                        </form>
                    @endif

                    <div class="flex gap-2 pt-2">
                        <a href="{{ route('admin.services.logs', $service->service_key) }}" 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors text-center text-xs">
                            View Logs
                        </a>
                        <a href="{{ route('admin.services.analytics', $service->service_key) }}" 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors text-center text-xs">
                            Analytics
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Quick Actions Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">ℹ️ Service Management Guide</h3>
        <ul class="space-y-2 text-sm text-blue-800">
            <li><strong>Disable Service:</strong> Completely blocks all user access to the service. Users will see a "Service Unavailable" error.</li>
            <li><strong>Maintenance Mode:</strong> Temporarily restricts access with a custom message. Useful for updates and maintenance.</li>
            <li><strong>Enable Service:</strong> Restores full access to the service for all users.</li>
            <li><strong>View Logs:</strong> See complete audit trail of who enabled/disabled the service and when.</li>
            <li><strong>Analytics:</strong> Monitor service usage, active users, API calls, and performance metrics.</li>
        </ul>
    </div>
</div>

<!-- Maintenance Mode Modal -->
<div id="maintenanceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Enable Maintenance Mode</h3>
            <form id="maintenanceForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Message (Optional)</label>
                    <textarea name="maintenance_message" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="This service is temporarily under maintenance. Please try again later."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeMaintenanceModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                        Enable Maintenance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentServiceKey = '';

function showMaintenanceModal(serviceKey) {
    currentServiceKey = serviceKey;
    document.getElementById('maintenanceForm').action = `/admin/services/${serviceKey}/maintenance/enable`;
    document.getElementById('maintenanceModal').classList.remove('hidden');
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('hidden');
    currentServiceKey = '';
}

// Close modal on outside click
document.getElementById('maintenanceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMaintenanceModal();
    }
});
</script>
@endpush
@endsection
