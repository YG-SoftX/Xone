@extends('layouts.console')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-sm text-gray-600">Welcome back! Here's what's happening with your projects.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Projects -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Projects</dt>
                                <dd class="text-3xl font-semibold text-gray-900">{{ $totalProjects }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Keys -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">API Keys</dt>
                                <dd class="text-3xl font-semibold text-gray-900">{{ $totalApiKeys }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Spend -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Spend</dt>
                                <dd class="text-3xl font-semibold text-gray-900">${{ number_format($totalSpend, 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Subs</dt>
                                <dd class="text-3xl font-semibold text-gray-900">{{ auth()->user()->projects->sum(fn($p) => $p->subscriptions->where('status', 'active')->count()) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- API Usage Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">API Usage (Last 30 Days)</h3>
                <canvas id="apiUsageChart" height="250"></canvas>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue (Last 12 Months)</h3>
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recent Activity & Projects -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Activity -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse($recentActivity as $activity)
                    <li class="px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                @if($activity['type'] === 'api_key')
                                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $activity['description'] }}</p>
                                <p class="text-sm text-gray-500">{{ $activity['created_at']->diffForHumans() }}</p>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-6 py-8 text-center text-gray-500">
                        No recent activity
                    </li>
                    @endforelse
                </ul>
            </div>

            <!-- Your Projects -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Your Projects</h3>
                    <a href="{{ route('console.projects.create') }}" class="text-sm text-blue-600 hover:text-blue-800">+ New Project</a>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse($projects->take(5) as $project)
                    <li class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                        {{ substr($project->name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $project->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $project->api_keys_count }} API keys • {{ $project->oauth_applications_count }} OAuth apps</p>
                                </div>
                            </div>
                            <a href="{{ route('console.projects.show', $project) }}" class="text-sm text-blue-600 hover:text-blue-800">View →</a>
                        </div>
                    </li>
                    @empty
                    <li class="px-6 py-8 text-center">
                        <p class="text-gray-500 mb-4">No projects yet</p>
                        <a href="{{ route('console.projects.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                            Create Your First Project
                        </a>
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// API Usage Chart
const apiCtx = document.getElementById('apiUsageChart').getContext('2d');
new Chart(apiCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($apiUsageData['labels']) !!},
        datasets: [{
            label: 'API Calls',
            data: {!! json_encode($apiUsageData['data']) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
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
                    precision: 0
                }
            }
        }
    }
});

// Revenue Chart
const revCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenueData['labels']) !!},
        datasets: [{
            label: 'Revenue ($)',
            data: {!! json_encode($revenueData['data']) !!},
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 2
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
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection
