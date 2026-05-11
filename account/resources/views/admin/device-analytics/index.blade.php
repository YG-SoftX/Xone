@extends('admin.master-layout')

@section('title', 'Device Analytics & Fraud Detection')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">🔍 Device Intelligence Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Cross-account tracking, fraud detection, and device analytics</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.device-analytics.fraud-alerts') }}" 
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                🚨 View Alerts ({{ $stats['total_fraud_alerts'] }})
            </a>
            <a href="{{ route('admin.device-analytics.suspicious-links') }}" 
               class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                🔗 Suspicious Links ({{ $stats['suspicious_device_links'] }})
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Devices</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_devices']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-2xl">📱</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Avg Reputation</p>
                    <p class="text-3xl font-bold {{ $stats['avg_reputation_score'] >= 70 ? 'text-green-600' : ($stats['avg_reputation_score'] >= 40 ? 'text-yellow-600' : 'text-red-600') }} mt-1">
                        {{ $stats['avg_reputation_score'] }}/100
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-2xl">⭐</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Critical Alerts</p>
                    <p class="text-3xl font-bold text-red-600 mt-1">{{ $stats['critical_alerts'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-2xl">🚨</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Blocked Devices</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['blocked_devices']) }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-2xl">🚫</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        
        {{-- Recent Fraud Alerts --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-sm font-semibold text-red-900 uppercase tracking-wide">🚨 Recent Fraud Alerts</h3>
            </div>
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                @forelse($recentAlerts as $alert)
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                    {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-800' : 
                                       ($alert->severity === 'high' ? 'bg-orange-100 text-orange-800' : 
                                       ($alert->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                                    {{ strtoupper($alert->severity) }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $alert->alert_type }}</span>
                            </div>
                            <p class="text-sm text-gray-900 font-medium">{{ $alert->description }}</p>
                            @if($alert->user)
                            <p class="text-xs text-gray-500 mt-1">
                                User: {{ $alert->user->name }} ({{ $alert->user->email }})
                            </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                        </div>
                        <a href="{{ route('admin.device-analytics.alerts.review', $alert->id) }}" 
                           class="ml-4 px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md transition">
                            Review
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-400">
                    <p class="text-4xl mb-2">✅</p>
                    <p>No active fraud alerts</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Suspicious Device Links --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-orange-50">
                <h3 class="text-sm font-semibold text-orange-900 uppercase tracking-wide">🔗 Suspicious Account Links</h3>
            </div>
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                @forelse($suspiciousLinks as $link)
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                            {{ $link->account_count }} Accounts
                        </span>
                        <span class="text-xs text-gray-400">{{ $link->first_detected_at?->diffForHumans() ?? 'Unknown' }}</span>
                    </div>
                    <p class="text-sm text-gray-900 font-medium mb-1">
                        {{ $link->user->name ?? 'Unknown User' }}
                    </p>
                    <p class="text-xs text-gray-500 mb-2">{{ $link->suspicion_reason }}</p>
                    @if($link->account_ids)
                    <div class="flex flex-wrap gap-1">
                        @foreach(array_slice($link->account_ids, 0, 5) as $accountId)
                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">ID: {{ $accountId }}</span>
                        @endforeach
                        @if(count($link->account_ids) > 5)
                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">+{{ count($link->account_ids) - 5 }} more</span>
                        @endif
                    </div>
                    @endif
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-400">
                    <p class="text-4xl mb-2">✅</p>
                    <p>No suspicious links detected</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        
        {{-- Geographic Distribution --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <h3 class="text-sm font-semibold text-blue-900 uppercase tracking-wide">🌍 Geographic Distribution</h3>
            </div>
            <div class="p-4 space-y-2">
                @forelse($geoDistribution as $geo)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">{{ $geo->country_code }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $geo->count }}</span>
                </div>
                @empty
                <p class="text-center text-gray-400 py-4">No location data available</p>
                @endforelse
            </div>
        </div>

        {{-- Device Type Distribution --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <h3 class="text-sm font-semibold text-green-900 uppercase tracking-wide">📱 Device Types</h3>
            </div>
            <div class="p-4 space-y-2">
                @forelse($deviceTypeDist as $type)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 capitalize">{{ $type->device_type }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $type->count }}</span>
                </div>
                @empty
                <p class="text-center text-gray-400 py-4">No device type data</p>
                @endforelse
            </div>
        </div>

        {{-- OS Distribution --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <h3 class="text-sm font-semibold text-purple-900 uppercase tracking-wide">💻 Operating Systems</h3>
            </div>
            <div class="p-4 space-y-2">
                @forelse($osDist as $os)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">{{ $os->os }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $os->count }}</span>
                </div>
                @empty
                <p class="text-center text-gray-400 py-4">No OS data available</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Low Reputation Devices --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
            <h3 class="text-sm font-semibold text-yellow-900 uppercase tracking-wide">⚠️ Low Reputation Devices (Score < 30)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reputation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Logins</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failed Attempts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($lowReputationDevices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ Str::limit($device->device_name, 30) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $device->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $device->device_reputation_score >= 70 ? 'bg-green-100 text-green-800' : 
                                   ($device->device_reputation_score >= 40 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $device->device_reputation_score }}/100
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $device->login_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $device->failed_login_attempts }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($device->is_blocked)
                                <span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">Blocked</span>
                            @elseif($device->is_trusted)
                                <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">Trusted</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-600 rounded-full">Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.device-analytics.device-detail', $device->id) }}" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                View Details →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            No low reputation devices found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
