@extends('admin.master-layout')

@section('title', 'Third-Party Apps')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Registered Third-Party Applications</h3>
        <a href="{{ route('admin.third-party-apps.logs') }}" class="text-sm text-blue-600 hover:text-blue-800">View Auth Logs &rarr;</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redirect URI</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auth Count</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($apps as $app)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $app['name'] }}</div>
                        <div class="text-sm text-gray-500">{{ $app['slug'] }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $app['client_id'] }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $app['owner'] }}</div>
                        <div class="text-xs text-gray-500">{{ $app['owner_email'] }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
                        {{ $app['redirect_uri'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($app['is_active'])
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ number_format($app['total_auth_requests']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $app['created_at'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="{{ route('admin.third-party-apps.show', $app['id']) }}" class="text-blue-600 hover:text-blue-900">View</a>
                        <form method="POST" action="{{ route('admin.third-party-apps.toggle', $app['id']) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-{{ $app['is_active'] ? 'red' : 'green' }}-600 hover:text-{{ $app['is_active'] ? 'red' : 'green' }}-900">
                                {{ $app['is_active'] ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.third-party-apps.destroy', $app['id']) }}" class="inline"
                              onsubmit="return confirm('Are you sure? This will delete the app and all its auth logs.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        No third-party apps registered yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
