@extends('admin.master-layout')

@section('title', 'Features & Services Management')

@section('content')
<div class="space-y-6">
    <!-- Features Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Platform Features</h3>
            <p class="text-sm text-gray-500 mt-1">Enable or disable features across the platform</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visibility</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($features as $feature)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $feature->feature_name }}</div>
                                <div class="text-xs text-gray-500">{{ $feature->feature_key }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.features.toggle', $feature->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_enabled" value="{{ $feature->is_enabled ? '0' : '1' }}">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $feature->is_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $feature->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.features.visibility', $feature->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_public" value="{{ $feature->is_public ? '0' : '1' }}">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $feature->is_public ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $feature->is_public ? 'Public' : 'Hidden' }}
                                    </span>
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $feature->description }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Services Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">YG Services</h3>
            <p class="text-sm text-gray-500 mt-1">Manage service status and maintenance modes</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Maintenance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Health Check</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($services as $service)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $service->service_name }}</div>
                                <div class="text-xs text-gray-500">{{ $service->url }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.services.toggle', $service->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $service->is_active ? '0' : '1' }}">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.services.maintenance', $service->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_maintenance" value="{{ $service->is_maintenance ? '0' : '1' }}">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $service->is_maintenance ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $service->is_maintenance ? 'Yes' : 'No' }}
                                    </span>
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ ucfirst($service->status) }}</div>
                                <div class="text-xs text-gray-500">{{ $service->last_health_check?->diffForHumans() ?? 'Never' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.services.health', $service->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Check Health</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
