@extends('admin.master-layout')
@section('title', 'Services & Features')
@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Service Status</h3>
        <form method="POST" action="{{ route('admin.services.check-all') }}" class="inline">@csrf<button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Check All Health</button></form>
    </div>
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y">
            <thead class="bg-gray-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Maintenance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Check</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr></thead>
            <tbody class="bg-white divide-y">
                @foreach($services as $service)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">{{ $service->service_name }}</td>
                    <td class="px-6 py-4 text-sm text-blue-600"><a href="{{ $service->url ?? '#' }}" target="_blank">{{ $service->url ?? '—' }}</a></td>
                    <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full {{ $service->status === 'operational' ? 'bg-green-100 text-green-800' : ($service->status === 'down' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">{{ $service->status }}</span></td>
                    <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $service->is_active ? 'Yes' : 'No' }}</span></td>
                    <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full {{ $service->is_maintenance ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">{{ $service->is_maintenance ? 'Yes' : 'No' }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $service->last_health_check?->diffForHumans() ?? 'Never' }}</td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <form method="POST" action="{{ route('admin.services.toggle', $service->id) }}" class="inline"><button class="text-blue-600">Toggle</button></form>
                        <form method="POST" action="{{ route('admin.services.check', $service->id) }}" class="inline"><button class="text-green-600">Check</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div>
    <h3 class="text-lg font-semibold mb-4">Feature Toggles</h3>
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y">
            <thead class="bg-gray-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enabled</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Public</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr></thead>
            <tbody class="bg-white divide-y">
                @foreach($features as $feature)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">{{ $feature->feature_name }}</td>
                    <td class="px-6 py-4 text-sm font-mono">{{ $feature->feature_key }}</td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('admin.features.toggle', $feature->id) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="is_enabled" value="{{ $feature->is_enabled ? '0' : '1' }}">
                            <span class="px-2 py-1 text-xs rounded-full {{ $feature->is_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $feature->is_enabled ? 'On' : 'Off' }}</span>
                            <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                        </form>
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('admin.features.visibility', $feature->id) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="is_public" value="{{ $feature->is_public ? '0' : '1' }}">
                            <span class="px-2 py-1 text-xs rounded-full {{ $feature->is_public ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">{{ $feature->is_public ? 'Visible' : 'Hidden' }}</span>
                            <button type="submit" class="text-xs text-blue-600 hover:underline">Toggle</button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $feature->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
