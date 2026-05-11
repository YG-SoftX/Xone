@extends('developer.layouts.app')

@section('title', 'Quotas')
@section('page-title', 'API Quotas & Usage')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Quota Management</h1>
            <p class="text-gray-600 mt-1">Monitor and manage API usage limits</p>
        </div>
        <button onclick="loadQuotas()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <i class="fas fa-sync mr-2"></i>Refresh
        </button>
    </div>

    <!-- Project Selector -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Project</label>
        <select id="projectSelector" onchange="loadQuotas()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Choose a project...</option>
            @foreach($projects as $project)
                <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['project_id'] }})</option>
            @endforeach
        </select>
    </div>

    <!-- Overview Stats -->
    <div id="quotaStats" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-blue-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3>
            <p class="text-sm text-gray-600">Daily Usage</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3>
            <p class="text-sm text-gray-600">Monthly Usage</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-green-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3>
            <p class="text-sm text-gray-600">Rate Limit</p>
        </div>
    </div>

    <!-- Product Quotas -->
    <div id="quotasContainer" class="space-y-4">
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tachometer-alt text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view quotas</p>
        </div>
    </div>

    <!-- Alerts Section -->
    <div id="alertsContainer" class="hidden space-y-4">
        <h3 class="font-semibold text-gray-800">Active Alerts</h3>
        <div id="alertsList"></div>
    </div>
</div>

@push('scripts')
<script>
async function loadQuotas() {
    const projectId = document.getElementById('projectSelector').value;
    
    if (!projectId) {
        resetDisplay();
        return;
    }
    
    try {
        const response = await fetch(`/api/developer-console/projects/${projectId}/quotas`);
        const data = await response.json();
        
        if (data.success) {
            updateStats(data.overview);
            displayQuotas(data.quotas);
            displayAlerts(data.alerts);
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
                    <i class="fas fa-tachometer-alt text-blue-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.daily_used.toLocaleString()} / ${overview.daily_limit.toLocaleString()}</h3>
            <p class="text-sm text-gray-600">Daily Usage (${overview.daily_percentage}%)</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.monthly_used.toLocaleString()} / ${overview.monthly_limit.toLocaleString()}</h3>
            <p class="text-sm text-gray-600">Monthly Usage (${overview.monthly_percentage}%)</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border card-hover transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-green-600 text-xl"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-1">${overview.rate_limit_per_minute}/min</h3>
            <p class="text-sm text-gray-600">Rate Limit</p>
        </div>
    `;
    
    document.getElementById('quotaStats').innerHTML = statsHtml;
}

function displayQuotas(quotas) {
    const container = document.getElementById('quotasContainer');
    
    if (quotas.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-white rounded-xl border">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tachometer-alt text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600">No quota data available</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = quotas.map(quota => {
        const dailyColor = getProgressColor(quota.daily.percentage);
        const monthlyColor = getProgressColor(quota.monthly.percentage);
        
        return `
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cube text-white"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">${quota.display_name}</h4>
                            <p class="text-sm text-gray-500">${quota.product_name}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs ${quota.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                        ${quota.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                
                <div class="space-y-4">
                    <!-- Daily Quota -->
                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">Daily Quota</span>
                            <span class="font-medium">${quota.daily.used.toLocaleString()} / ${quota.daily.limit.toLocaleString()}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="${dailyColor} h-3 rounded-full transition-all" style="width: ${Math.min(100, quota.daily.percentage)}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${quota.daily.remaining.toLocaleString()} remaining (${quota.daily.percentage}% used)</p>
                    </div>
                    
                    <!-- Monthly Quota -->
                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">Monthly Quota</span>
                            <span class="font-medium">${quota.monthly.used.toLocaleString()} / ${quota.monthly.limit.toLocaleString()}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="${monthlyColor} h-3 rounded-full transition-all" style="width: ${Math.min(100, quota.monthly.percentage)}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${quota.monthly.remaining.toLocaleString()} remaining</p>
                    </div>
                    
                    <!-- Rate Limit -->
                    <div class="pt-3 border-t">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Rate Limit</span>
                            <span class="font-medium">${quota.rate_limit.per_minute} requests/minute</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function displayAlerts(alerts) {
    const container = document.getElementById('alertsContainer');
    const list = document.getElementById('alertsList');
    
    if (alerts.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    list.innerHTML = alerts.map(alert => `
        <div class="border-l-4 ${alert.severity === 'critical' ? 'border-red-500 bg-red-50' : (alert.severity === 'warning' ? 'border-yellow-500 bg-yellow-50' : 'border-blue-500 bg-blue-50')} p-4 rounded-r-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-${alert.severity === 'critical' ? 'exclamation-circle text-red-600' : (alert.severity === 'warning' ? 'exclamation-triangle text-yellow-600' : 'info-circle text-blue-600')} text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-800">${alert.title}</h4>
                    <p class="text-sm text-gray-600 mt-1">${alert.message}</p>
                    <p class="text-xs text-gray-500 mt-2">${new Date(alert.created_at).toLocaleString()}</p>
                </div>
            </div>
        </div>
    `).join('');
}

function getProgressColor(percentage) {
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 75) return 'bg-yellow-500';
    return 'bg-green-500';
}

function resetDisplay() {
    document.getElementById('quotaStats').innerHTML = `
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3><p class="text-sm text-gray-600">Daily Usage</p></div>
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3><p class="text-sm text-gray-600">Monthly Usage</p></div>
        <div class="bg-white rounded-xl p-6 shadow-sm border"><h3 class="text-3xl font-bold text-gray-800 mb-1">-</h3><p class="text-sm text-gray-600">Rate Limit</p></div>
    `;
    
    document.getElementById('quotasContainer').innerHTML = `
        <div class="text-center py-12 bg-white rounded-xl border">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tachometer-alt text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600">Select a project to view quotas</p>
        </div>
    `;
    
    document.getElementById('alertsContainer').classList.add('hidden');
}
</script>
@endpush
@endsection
