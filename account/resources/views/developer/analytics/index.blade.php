@extends('developer.layouts.app')

@section('title', 'Analytics')
@section('page-title', 'API Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Analytics Dashboard</h1>
            <p class="text-gray-600 mt-1">Monitor API usage, performance, and trends</p>
        </div>
        <div class="flex gap-3">
            <select id="daysSelector" onchange="loadAnalytics()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
            <button onclick="exportAnalytics()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
        </div>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Project</label>
        <select id="projectSelector" onchange="loadAnalytics()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Choose a project...</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['project_id'] }})</option>
            @endforeach
        </select>
    </div>

    <!-- Overview Stats -->
    <div id="statsContainer" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-blue-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3>
            <p class="text-sm text-gray-600">Total Requests</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-%</h3>
            <p class="text-sm text-gray-600">Success Rate</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">- ms</h3>
            <p class="text-sm text-gray-600">Avg Response Time</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-/day</h3>
            <p class="text-sm text-gray-600">Requests/Day</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Trend Chart -->
        <div class="bg-white rounded-xl shadow-sm border p-6 lg:col-span-2">
            <h3 class="font-semibold text-gray-800 mb-4">Daily Usage Trend</h3>
            <canvas id="dailyTrendChart" height="100"></canvas>
        </div>
        
        <!-- Status Distribution -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Status Code Distribution</h3>
            <canvas id="statusChart"></canvas>
        </div>
        
        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Top API Products</h3>
            <div id="productsList" class="space-y-3">
                <p class="text-gray-500 text-center py-8">No data available</p>
            </div>
        </div>
    </div>

    <!-- Top Endpoints Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Top Endpoints</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endpoint</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requests</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Response</th>
                </tr>
            </thead>
            <tbody id="endpointsTable" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No data available</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
let dailyTrendChart = null;
let statusChart = null;

async function loadAnalytics() {
    const projectId = document.getElementById('projectSelector').value;
    const days = document.getElementById('daysSelector').value;
    
    if (!projectId) {
        resetDisplay();
        return;
    }
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/analytics?days=${days}`);
        const data = await response.json();
        
        if (data.success) {
            updateStats(data.overview);
            updateCharts(data.daily_trend, data.status_distribution);
            updateProductsList(data.by_product);
            updateEndpointsTable(data.top_endpoints);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function updateStats(overview) {
    const statsHtml = `
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-blue-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.total_requests.toLocaleString()}</h3>
            <p class="text-sm text-gray-600">Total Requests</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.success_rate}%</h3>
            <p class="text-sm text-gray-600">Success Rate</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.avg_response_time_ms} ms</h3>
            <p class="text-sm text-gray-600">Avg Response Time</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.requests_per_day}/day</h3>
            <p class="text-sm text-gray-600">Requests/Day</p>
        </div>
    `;
    
    document.getElementById('statsContainer').innerHTML = statsHtml;
}

function updateCharts(dailyTrend, statusDistribution) {
    // Daily Trend Chart
    const trendCtx = document.getElementById('dailyTrendChart').getContext('2d');
    
    if (dailyTrendChart) {
        dailyTrendChart.destroy();
    }
    
    dailyTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: Object.keys(dailyTrend),
            datasets: [{
                label: 'Requests',
                data: Object.values(dailyTrend),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    
    if (statusChart) {
        statusChart.destroy();
    }
    
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'];
    
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusDistribution.map(s => s.category),
            datasets: [{
                data: statusDistribution.map(s => s.count),
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function updateProductsList(products) {
    const container = document.getElementById('productsList');
    
    if (products.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-8">No data available</p>';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="font-medium text-gray-800">${product.display_name}</p>
                <p class="text-xs text-gray-500">${product.name}</p>
            </div>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                ${product.count.toLocaleString()}
            </span>
        </div>
    `).join('');
}

function updateEndpointsTable(endpoints) {
    const tbody = document.getElementById('endpointsTable');
    
    if (endpoints.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = endpoints.map(endpoint => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">${endpoint.endpoint}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded text-xs ${getMethodColor(endpoint.method)}">
                    ${endpoint.method}
                </span>
            </td>
            <td class="px-6 py-4 font-medium">${endpoint.count.toLocaleString()}</td>
            <td class="px-6 py-4">${endpoint.avg_response_time} ms</td>
        </tr>
    `).join('');
}

function getMethodColor(method) {
    const colors = {
        'GET': 'bg-blue-100 text-blue-700',
        'POST': 'bg-green-100 text-green-700',
        'PUT': 'bg-yellow-100 text-yellow-700',
        'DELETE': 'bg-red-100 text-red-700',
        'PATCH': 'bg-purple-100 text-purple-700'
    };
    return colors[method] || 'bg-gray-100 text-gray-700';
}

function resetDisplay() {
    document.getElementById('statsContainer').innerHTML = `
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3><p class="text-sm text-gray-600">Total Requests</p></div>
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-%</h3><p class="text-sm text-gray-600">Success Rate</p></div>
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">- ms</h3><p class="text-sm text-gray-600">Avg Response Time</p></div>
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-/day</h3><p class="text-sm text-gray-600">Requests/Day</p></div>
    `;
    
    document.getElementById('endpointsTable').innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No data available</td></tr>';
    document.getElementById('productsList').innerHTML = '<p class="text-gray-500 text-center py-8">No data available</p>';
}

async function exportAnalytics() {
    const projectId = document.getElementById('projectSelector').value;
    const days = document.getElementById('daysSelector').value;
    
    if (!projectId) {
        alert('Please select a project first');
        return;
    }
    
    window.location.href = `/api/developer-console/projects/${projectId}/analytics/export?days=${days}`;
}

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    const projectId = document.getElementById('projectSelector').value;
    if (projectId) {
        loadAnalytics();
    }
});
</script>
@endpush
@endsection
