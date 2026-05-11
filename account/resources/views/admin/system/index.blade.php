@extends('admin.master-layout')

@section('title', 'System Management')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">System Management</h2>
            <p class="text-sm text-gray-500 mt-1">Monitor runtime health and perform system operations.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-semibold
                {{ $info['app_env'] === 'production' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                ENV: {{ strtoupper($info['app_env']) }}
            </span>
            @if($info['app_debug'])
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">DEBUG ON</span>
            @endif
            @if($info['maintenance_mode'])
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 animate-pulse">MAINTENANCE</span>
            @endif
        </div>
    </div>

    {{-- Runtime Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-medium mb-1">PHP Version</p>
            <p class="text-xl font-bold text-gray-900">{{ $info['php_version'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-medium mb-1">Laravel</p>
            <p class="text-xl font-bold text-gray-900">{{ $info['laravel_version'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-medium mb-1">Database</p>
            <p class="text-xl font-bold text-gray-900">{{ $info['db_version'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-medium mb-1">Memory Limit</p>
            <p class="text-xl font-bold text-gray-900">{{ $info['memory_limit'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Max exec: {{ $info['max_exec_time'] }}</p>
        </div>
    </div>

    {{-- Disk Usage --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Disk Usage</h3>
            <span class="text-sm text-gray-500">{{ $info['disk_free_gb'] }} GB free of {{ $info['disk_total_gb'] }} GB</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div class="h-4 rounded-full {{ $info['disk_used_pct'] > 85 ? 'bg-red-500' : ($info['disk_used_pct'] > 65 ? 'bg-yellow-500' : 'bg-green-500') }}"
                style="width: {{ $info['disk_used_pct'] }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ $info['disk_used_pct'] }}% used</p>
    </div>

    {{-- Queue + Drivers --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Queue Status</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-3xl font-bold text-blue-700">{{ $info['pending_jobs'] }}</p>
                    <p class="text-xs text-blue-500 mt-1">Pending Jobs</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <p class="text-3xl font-bold text-red-700">{{ $info['failed_jobs'] }}</p>
                    <p class="text-xs text-red-500 mt-1">Failed Jobs</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Active Drivers</h3>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Cache</dt>
                    <dd class="font-semibold text-gray-900 uppercase">{{ $info['cache_driver'] }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Queue</dt>
                    <dd class="font-semibold text-gray-900 uppercase">{{ $info['queue_driver'] }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Session</dt>
                    <dd class="font-semibold text-gray-900 uppercase">{{ $info['session_driver'] }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Operations --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Maintenance --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Maintenance Mode</h3>
            <p class="text-xs text-gray-400 mb-4">Take the site offline for visitors. A bypass secret URL is generated for admin access.</p>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full {{ $info['maintenance_mode'] ? 'bg-red-500 animate-pulse' : 'bg-green-500' }}"></span>
                <span class="text-sm font-medium {{ $info['maintenance_mode'] ? 'text-red-700' : 'text-green-700' }}">
                    {{ $info['maintenance_mode'] ? 'Currently DOWN' : 'Currently UP' }}
                </span>
            </div>
            @if($info['maintenance_mode'])
                <button onclick="document.getElementById('modal-up').classList.remove('hidden')"
                    class="w-full px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    Bring Site Back Up
                </button>
            @else
                <button onclick="document.getElementById('modal-down').classList.remove('hidden')"
                    class="w-full px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    Enable Maintenance
                </button>
            @endif
        </div>

        {{-- Cache --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Cache Management</h3>
            <p class="text-xs text-gray-400 mb-4">Clear or rebuild application, config, view, and route caches.</p>
            <div class="space-y-2">
                <form method="POST" action="{{ route('admin.system.cache.clear') }}"
                    onsubmit="return confirm('Clear all caches? This may briefly slow the site.')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 text-sm bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition">
                        Clear All Caches
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.system.cache.warm') }}"
                    onsubmit="return confirm('Rebuild all caches for production?')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                        Warm Caches
                    </button>
                </form>
            </div>
        </div>

        {{-- Queue --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Queue Workers</h3>
            <p class="text-xs text-gray-400 mb-4">Signal all workers to gracefully restart after their current job completes.</p>
            <form method="POST" action="{{ route('admin.system.queue.restart') }}"
                onsubmit="return confirm('Restart all queue workers?')">
                @csrf
                <button type="submit" class="w-full mt-4 px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                    Restart Queue Workers
                </button>
            </form>
        </div>

        {{-- Cron Setup --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 md:col-span-3">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Cron Setup (cPanel)</h3>
            <p class="text-xs text-gray-400 mb-4">Copy and paste this exact string into your cPanel Cron Jobs interface. Set it to run <b>Once Per Minute (* * * * *)</b>.</p>
            <div class="relative group">
                <code class="block w-full p-4 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-800 font-mono break-all select-all">/usr/local/bin/php {{ base_path('artisan') }} schedule:run >> /dev/null 2>&1</code>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="navigator.clipboard.writeText('/usr/local/bin/php {{ str_replace('\\', '/', base_path('artisan')) }} schedule:run >> /dev/null 2>&1'); alert('Copied to clipboard!')" class="p-2 bg-white rounded-md shadow-sm border border-gray-200 text-gray-500 hover:text-blue-600">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i> This single cron job handles cross-node sync, subscription renewals, and webhook deliveries across the entire 16-node ecosystem.</p>
        </div>
    </div>
</div>

{{-- Modal: Enable Maintenance --}}
<div id="modal-down" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-red-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Enable Maintenance Mode</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-700 text-sm">The site will go offline for all visitors. A bypass URL will be generated for super-admin access only.</p>
            <p class="text-xs text-red-600 font-medium mt-2">Only super-admin (ID 1) can perform this.</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button onclick="document.getElementById('modal-down').classList.add('hidden')"
                class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
            <form method="POST" action="{{ route('admin.system.maintenance.enable') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    Confirm
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Disable Maintenance --}}
<div id="modal-up" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-green-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Restore Site</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-700 text-sm">This will run <code class="bg-gray-100 px-1 rounded">php artisan up</code> and bring the site back online.</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button onclick="document.getElementById('modal-up').classList.add('hidden')"
                class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
            <form method="POST" action="{{ route('admin.system.maintenance.disable') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    Confirm
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
