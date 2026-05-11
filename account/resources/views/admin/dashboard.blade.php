@extends('admin.master-layout')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalUsers }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Active Subscriptions</p>
                <p class="text-3xl font-bold text-gray-900">{{ $activeSubscriptions }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Wallet Balance -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Wallet Balance</p>
                <p class="text-3xl font-bold text-gray-900">${{ number_format($totalWalletBalance, 2) }}</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="space-y-2">
            <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-center">
                Manage Users
            </a>
            <a href="{{ route('admin.platform.index') }}" class="block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-center">
                Platform Stats
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Users</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($recentUsers as $user)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                            <a href="{{ route('admin.users.show', $user->id) }}" class="text-xs text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">View all users →</a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($recentActivity as $activity)
                    <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
                        <span class="text-xl">{{ $activity->icon }}</span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->action }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $activity->user->name ?? 'Unknown' }} • {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
