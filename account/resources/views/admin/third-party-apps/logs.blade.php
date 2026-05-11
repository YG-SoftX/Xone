@extends('admin.master-layout')

@section('title', 'Third-Party Auth Logs')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Global Authentication Logs</h3>
        <a href="{{ route('admin.third-party-apps.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Apps</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">App</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User Agent</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $log['app_name'] }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded {{ $log['event_type'] === 'sso_validated' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $log['event_type'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $log['user_email'] ?? 'Anonymous' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">
                        {{ $log['ip_address'] ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $log['user_agent'] }}">
                        {{ $log['user_agent'] ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $log['created_at'] }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        No authentication logs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
