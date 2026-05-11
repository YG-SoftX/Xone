@extends('admin.master-layout')

@section('title', 'Service Access Logs - ' . $service->service_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('admin.services.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Services</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $service->service_name }} - Access Logs</h1>
            <p class="text-sm text-gray-600 mt-1">Audit trail of service enable/disable actions</p>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($logs->isNotEmpty())
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->action === 'enabled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✓ Enabled
                                    </span>
                                @elseif($log->action === 'disabled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        ✗ Disabled
                                    </span>
                                @elseif($log->action === 'maintenance_on')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        🔧 Maintenance On
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Maintenance Off
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $log->admin_name }}</div>
                                <div class="text-sm text-gray-500">{{ $log->admin_email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $log->reason ?? 'No reason provided' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->ip_address }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i:s') }}
                                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($logs->hasPages())
                <div class="px-6 py-4 border-t">
                    {{ $logs->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No access logs found for this service</p>
            </div>
        @endif
    </div>
</div>
@endsection
