@extends('layouts.app')

@section('title', 'YG Home - Admin Dashboard')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Monitor and manage YG Home search system</p>
                </div>
                <div class="flex gap-3">
                    <form action="{{ route('admin.cache.clear') }}" method="POST" onsubmit="return confirm('Clear all search cache?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">
                            <i class="fas fa-trash mr-2"></i>Clear Cache
                        </button>
                    </form>
                    <form action="{{ route('admin.sync') }}" method="POST" onsubmit="return confirm('Sync all ecosystem modules?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-sync mr-2"></i>Sync Modules
                        </button>
                    </form>
                    <form action="{{ route('admin.rebuild') }}" method="POST" onsubmit="return confirm('Rebuild entire search index? This may take a while.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                            <i class="fas fa-database mr-2"></i>Rebuild Index
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(isset($error))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-red-700">{{ $error }}</span>
            </div>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-green-700">{{ session('success') }}</span>
            </div>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
            <!-- Total Searches Today -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Searches Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_searches_today']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Zero Result Rate -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Zero Result Rate</p>
                        <p class="text-3xl font-bold {{ $stats['zero_result_rate'] > 10 ? 'text-red-600' : 'text-green-600' }} mt-2">
                            {{ $stats['zero_result_rate'] }}%
                        </p>
                    </div>
                    <div class="p-3 {{ $stats['zero_result_rate'] > 10 ? 'bg-red-100' : 'bg-green-100' }} rounded-full">
                        <svg class="h-6 w-6 {{ $stats['zero_result_rate'] > 10 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Click Through Rate -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">CTR (7d)</p>
                        <p class="text-3xl font-bold {{ $stats['click_through_rate'] < 30 ? 'text-yellow-600' : 'text-green-600' }} mt-2">
                            {{ $stats['click_through_rate'] }}%
                        </p>
                    </div>
                    <div class="p-3 {{ $stats['click_through_rate'] < 30 ? 'bg-yellow-100' : 'bg-green-100' }} rounded-full">
                        <svg class="h-6 w-6 {{ $stats['click_through_rate'] < 30 ? 'text-yellow-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Indexed Items -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Indexed Items</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_indexed_items']) }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Users (7d)</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['active_users_7d']) }}</p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-full">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Avg Response Time -->
            <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg Response</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['avg_response_time'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Search Volume Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Volume (Last 30 Days)</h3>
                <div class="chart-container">
                    <canvas id="searchVolumeChart"></canvas>
                </div>
            </div>

            <!-- Index Distribution -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Index Distribution by Service</h3>
                <div class="space-y-4">
                    @foreach($indexedByService as $service)
                    @php
                        $percentage = $stats['total_indexed_items'] > 0 
                            ? round(($service->count / $stats['total_indexed_items']) * 100, 2) 
                            : 0;
                        $colors = [
                            'mail' => 'bg-red-500',
                            'drive' => 'bg-green-500',
                            'docs' => 'bg-blue-500',
                            'contacts' => 'bg-yellow-500',
                            'calendar' => 'bg-purple-500',
                        ];
                        $color = $colors[$service->service] ?? 'bg-gray-500';
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ ucfirst($service->service) }}</span>
                            <span class="text-gray-600">{{ number_format($service->count) }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Trending Queries -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🔥 Top Trending Queries (7d)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($trendingQueries as $index => $query)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ Str::limit($query->query, 50) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-blue-600">{{ $query->count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Zero Result Queries -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Content Gaps (Zero Results)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Occurrences</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($zeroResultQueries as $query)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">{{ Str::limit($query->query, 50) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-red-600">{{ $query->occurrences }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <a href="/search?q={{ urlencode($query->query) }}" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        Search →
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                    No zero-result queries found! 🎉
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Clicked Results -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 Most Clicked Results</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($topClickedResults as $result)
                        @php
                            $ctr = $result->impression_count > 0 
                                ? round(($result->click_count / $result->impression_count) * 100, 2) 
                                : 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-md truncate">{{ Str::limit($result->url, 60) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $result->result_type === 'mail' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $result->result_type === 'drive' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $result->result_type === 'docs' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $result->result_type === 'web' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($result->result_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-gray-900">{{ number_format($result->click_count) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">{{ number_format($result->impression_count) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold {{ $ctr > 50 ? 'text-green-600' : ($ctr > 20 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $ctr }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Search Volume Chart
    const ctx = document.getElementById('searchVolumeChart').getContext('2d');
    const searchVolumeData = @json($searchVolumeData);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: searchVolumeData.map(item => item.date),
            datasets: [{
                label: 'Searches',
                data: searchVolumeData.map(item => item.count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 10
                    }
                }
            }
        }
    });
</script>
@endpush
