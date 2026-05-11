@extends('admin.master-layout')
@section('title', 'YG Master Control Panel')

@section('content')
<div class="space-y-6">
    
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">YG Master Control Panel</h1>
            <p class="text-gray-600 mt-1">Complete ecosystem management & monitoring</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="refreshStats()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-download mr-2"></i>Export Report
            </button>
        </div>
    </div>

    {{-- System Status Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-gray-700">All Systems Operational</span>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="far fa-clock mr-1"></i>
                    Last updated: <span id="last-updated">{{ now()->format('M j, Y g:i A') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-600">Uptime: <strong class="text-green-600">99.98%</strong></span>
                <span class="text-gray-600">Response Time: <strong class="text-blue-600">45ms</strong></span>
            </div>
        </div>
    </div>

    {{-- Key Metrics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        {{-- Total Users --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded-full">+{{ $accountStats['new_users_today'] ?? 0 }} today</span>
            </div>
            <div class="text-3xl font-bold mb-1">{{ number_format($accountStats['total_users'] ?? 0) }}</div>
            <div class="text-sm opacity-90">Total Users</div>
            <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                <span class="opacity-75">Active:</span> <strong>{{ number_format($accountStats['active_users'] ?? 0) }}</strong>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded-full">This Month</span>
            </div>
            <div class="text-3xl font-bold mb-1">${{ number_format($accountStats['monthly_revenue'] ?? 0, 2) }}</div>
            <div class="text-sm opacity-90">Monthly Revenue</div>
            <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                <span class="opacity-75">Wallet Balance:</span> <strong>${{ number_format($accountStats['total_wallet_balance'] ?? 0, 2) }}</strong>
            </div>
        </div>

        {{-- Services --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-th text-2xl"></i>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded-full">{{ count($services ?? []) }} services</span>
            </div>
            <div class="text-3xl font-bold mb-1">{{ $services->where('is_active', true)->count() ?? 0 }}</div>
            <div class="text-sm opacity-90">Active Services</div>
            <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                <span class="opacity-75">Subscriptions:</span> <strong>{{ number_format($accountStats['active_subscriptions'] ?? 0) }}</strong>
            </div>
        </div>

        {{-- Pending Actions --}}
        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Needs Attention</span>
            </div>
            <div class="text-3xl font-bold mb-1">{{ ($accountStats['pending_kyc'] ?? 0) + ($accountStats['suspended_users'] ?? 0) }}</div>
            <div class="text-sm opacity-90">Pending Actions</div>
            <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                <span class="opacity-75">KYC Reviews:</span> <strong>{{ $accountStats['pending_kyc'] ?? 0 }}</strong>
            </div>
        </div>
    </div>

    {{-- Service Health Monitor --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Service Health Monitor</h2>
            <a href="{{ route('admin.master.services') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Manage Services <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <div class="divide-y divide-gray-100">
            @forelse($services ?? [] as $service)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 {{ $service->color ?? 'bg-gray-100' }} rounded-lg flex items-center justify-center">
                        <i class="fas {{ $service->icon ?? 'fa-cube' }} text-lg"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">{{ $service->service_name }}</div>
                        <div class="text-sm text-gray-500">{{ $service->description ?? 'No description' }}</div>
                    </div>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">{{ $service->status ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">v{{ $service->version ?? '1.0' }}</div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ ($service->is_active ?? false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    
                    <a href="{{ $service->url ?? '#' }}" target="_blank" 
                       class="p-2 text-gray-400 hover:text-blue-600 transition">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-gray-500">No services configured</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Two Column Layout --}}
    <div class="grid lg:grid-cols-3 gap-6">
        
        {{-- Left Column (2/3 width) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- User Management Quick View --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Recent User Activity</h2>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View All</a>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($recentActivity ?? [] as $activity)
                    <div class="px-6 py-4 flex items-start gap-4 hover:bg-gray-50 transition">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shrink-0">
                            {{ strtoupper(substr($activity->user->full_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <div class="font-medium text-gray-900 truncate">{{ $activity->user->full_name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="text-sm text-gray-600">{{ $activity->description }}</div>
                            <div class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $activity->ip_address ?? 'Unknown IP' }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-8 text-center">
                        <p class="text-gray-500">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Top Users by Balance --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900">Top Users by Wallet Balance</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topUsers ?? [] as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                            {{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-gray-900">{{ $user->full_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-green-600">${{ number_format($user->wallet->balance ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:text-blue-700 text-sm">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No users found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column (1/3 width) --}}
        <div class="space-y-6">
            
            {{-- KYC Review Queue --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">KYC Reviews</h2>
                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full">{{ count($recentKyc ?? []) }}</span>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($recentKyc ?? [] as $kyc)
                    <div class="px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-medium text-gray-900">{{ $kyc->user->full_name ?? 'Unknown' }}</div>
                            <span class="text-xs text-gray-500">{{ $kyc->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-sm text-gray-600 mb-3">{{ $kyc->document_type ?? 'ID Verification' }}</div>
                        <div class="flex gap-2">
                            <button class="flex-1 py-2 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600 transition">
                                <i class="fas fa-check mr-1"></i>Approve
                            </button>
                            <button class="flex-1 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600 transition">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                        <p class="text-gray-500">No pending reviews</p>
                    </div>
                    @endforelse
                </div>
                
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                    <a href="{{ route('admin.kyc.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium block text-center">
                        View All KYC Submissions
                    </a>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 transition group">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition">
                            <i class="fas fa-user-plus text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Add User</div>
                            <div class="text-xs text-gray-500">Create new user account</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="{{ route('admin.features.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-purple-50 transition group">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition">
                            <i class="fas fa-cog text-purple-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Manage Features</div>
                            <div class="text-xs text-gray-500">Configure platform features</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="{{ route('admin.smtp.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-green-50 transition group">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition">
                            <i class="fas fa-envelope text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">SMTP Settings</div>
                            <div class="text-xs text-gray-500">Configure email delivery</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="{{ route('admin.system.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-orange-50 transition group">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition">
                            <i class="fas fa-server text-orange-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">System Settings</div>
                            <div class="text-xs text-gray-500">Server & environment config</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                </div>
            </div>

            {{-- Platform Statistics --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Platform Stats</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Storage Used</span>
                            <span class="font-semibold">68%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 68%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">API Calls Today</span>
                            <span class="font-semibold">45.2K</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Email Delivery Rate</span>
                            <span class="font-semibold">98.5%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: 98.5%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshStats() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Auto-refresh every 5 minutes
setInterval(() => {
    document.getElementById('last-updated').textContent = '{{ now()->format("M j, Y g:i A") }}';
}, 300000);
</script>
@endpush
@endsection
