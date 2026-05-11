@extends('admin.master-layout')

@section('title', 'User Details - ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- User Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Users</a>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Name</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->status ?? 'active' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Joined</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- Wallet -->
    @if($user->wallet)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Wallet</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Balance</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($user->wallet->balance, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Currency</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->wallet->currency }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Subscriptions -->
    @if($user->subscriptions->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscriptions</h3>
        <div class="space-y-3">
            @foreach($user->subscriptions as $sub)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $sub->plan_name }}</p>
                        <p class="text-xs text-gray-500">${{ $sub->price_per_month }}/month</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                        {{ $sub->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $sub->status }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Activity -->
    @if($user->activityLogs->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <div class="space-y-3">
            @foreach($user->activityLogs->take(10) as $activity)
                <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
                    <span class="text-xl">{{ $activity->icon }}</span>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $activity->action }}</p>
                        <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Device Management -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Device Management</h3>
            <a href="{{ route('admin.devices.index', $user->id) }}"
               class="px-4 py-2 text-sm bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition font-medium">
                Manage Devices →
            </a>
        </div>

        @php
            $devices = $user->devices ?? collect();
        @endphp

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-900">{{ $devices->count() }}</p>
                <p class="text-xs text-gray-500 mt-1">Total</p>
            </div>
            <div class="text-center p-3 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-700">{{ $devices->where('is_blocked', false)->count() }}</p>
                <p class="text-xs text-green-600 mt-1">Active</p>
            </div>
            <div class="text-center p-3 bg-red-50 rounded-lg">
                <p class="text-2xl font-bold text-red-700">{{ $devices->where('is_blocked', true)->count() }}</p>
                <p class="text-xs text-red-600 mt-1">Blocked</p>
            </div>
        </div>

        @if($devices->count() > 0)
            <div class="space-y-2">
                @foreach($devices->take(3) as $device)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">
                            @if(str_contains(strtolower($device->device_name ?? ''), 'iphone') || str_contains(strtolower($device->device_name ?? ''), 'android'))📱
                            @else💻@endif
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $device->device_name ?? 'Unknown Device' }}</p>
                            <p class="text-xs text-gray-500">{{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'Never' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($device->is_blocked)
                            <span class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">Blocked</span>
                            <form method="POST" action="{{ route('admin.devices.unblock', $device->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-green-600 hover:underline">Unblock</button>
                            </form>
                        @else
                            <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">Active</span>
                            <form method="POST" action="{{ route('admin.devices.block', $device->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-red-600 hover:underline">Block</button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
                @if($devices->count() > 3)
                    <p class="text-xs text-center text-gray-400 pt-1">
                        + {{ $devices->count() - 3 }} more device(s) —
                        <a href="{{ route('admin.devices.index', $user->id) }}" class="text-blue-600 hover:underline">View all</a>
                    </p>
                @endif
            </div>
        @else
            <p class="text-sm text-gray-400 text-center py-4">No devices registered for this user.</p>
        @endif
    </div>
</div>
@endsection
