@extends('admin.master-layout')

@section('title', 'Service Analytics - ' . $service->service_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('admin.services.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Services</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $service->service_name }} - Analytics</h1>
            <p class="text-sm text-gray-600 mt-1">Usage statistics for the last {{ $period }} days</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="period" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Active Users</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($totals['total_active_users']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Actions</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($totals['total_actions']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">API Calls</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($totals['total_api_calls']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Avg Response Time</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $totals['avg_response_time'] ? round($totals['avg_response_time'], 2) . 'ms' : 'N/A' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Errors</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($totals['total_errors']) }}</p>
        </div>
    </div>

    <!-- Usage Chart Data Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Daily Usage Statistics</h3>
        </div>
        @if($stats->isNotEmpty())
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Calls</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Errors</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats as $stat)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $stat->date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($stat->active_users) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($stat->total_actions) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($stat->api_calls) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($stat->error_count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-12 text-center">
                <p class="text-sm text-gray-500">No usage data available for this period</p>
            </div>
        @endif
    </div>
</div>
@endsection
