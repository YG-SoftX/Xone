@extends('admin.master-layout')

@section('title', 'Third-Party App Details: ' . $app['name'])

@section('content')
<div class="space-y-6">
    <!-- App Details Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">App Information</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">App Name</label>
                <p class="mt-1 text-sm text-gray-900">{{ $app['name'] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Slug</label>
                <p class="mt-1 text-sm text-gray-900">{{ $app['slug'] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <p class="mt-1 text-sm text-gray-900">{{ $app['description'] ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Website</label>
                <a href="{{ $app['website_url'] }}" target="_blank" class="mt-1 text-sm text-blue-600 hover:underline">{{ $app['website_url'] ?? 'N/A' }}</a>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Client ID</label>
                <code class="mt-1 block text-xs bg-gray-100 px-3 py-2 rounded break-all">{{ $app['client_id'] }}</code>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                <code class="mt-1 block text-xs bg-gray-100 px-3 py-2 rounded break-all">{{ substr($app['client_secret'], 0, 20) }}...</code>
                <p class="text-xs text-gray-500 mt-1">Secret is stored hashed and cannot be fully displayed.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Redirect URI</label>
                <code class="mt-1 block text-xs bg-gray-100 px-3 py-2 rounded break-all">{{ $app['redirect_uri'] }}</code>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Scopes</label>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($app['scopes'] as $scope)
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">{{ $scope }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Owner</label>
                <p class="mt-1 text-sm text-gray-900">{{ $app['owner'] }} ({{ $app['owner_email'] }})</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <p class="mt-1">
                    @if($app['is_active'])
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                    @else
                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Auth Requests</label>
                <p class="mt-1 text-sm text-gray-900">{{ number_format($app['total_auth_requests']) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Total API Calls</label>
                <p class="mt-1 text-sm text-gray-900">{{ number_format($app['total_api_calls']) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Created At</label>
                <p class="mt-1 text-sm text-gray-900">{{ $app['created_at'] }}</p>
            </div>
        </div>
    </div>

    <!-- Auth Logs Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Recent Authentication Logs</h3>
            <span class="text-sm text-gray-500">{{ count($authLogs) }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($authLogs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded {{ $log['event_type'] === 'sso_validated' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $log['event_type'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            {{ $log['user_email'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">
                            {{ $log['ip_address'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $log['created_at'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            No authentication logs yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('admin.third-party-apps.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">&larr; Back to List</a>
    </div>
</div>
@endsection
