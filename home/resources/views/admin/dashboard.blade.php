@php
    $ecoService = app(\App\Services\EcosystemService::class);
    $themeService = app(\App\Services\HomeThemeService::class);
    $activeApps = $ecoService->getActiveApps();
    $ecosystemStats = $ecoService->getEcosystemStats();
@endphp

@extends('layouts.app')

@section('title', 'Admin Dashboard — YGXONE Home')

@push('styles')
<style>
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
    .module-toggle { transition: all 0.2s; }
    .module-toggle:checked + label { border-color: var(--yg-primary); }
    .chart-container { position: relative; height: 300px; }
    .glass-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(12px); border: 1px solid var(--yg-border); }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[var(--yg-surface)]" x-data="adminDashboard()" x-init="init()">
    
    {{-- ── Header ── --}}
    <div class="bg-white border-b border-[var(--yg-border)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-heading font-black text-[var(--yg-text)]">Home Dashboard</h1>
                    <p class="text-sm text-[var(--yg-text-dim)] mt-1">Monitor & manage the YGXONE ecosystem portal</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="refreshAll()" :disabled="refreshing" 
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-[var(--yg-surface)] text-[var(--yg-text)] hover:bg-[var(--yg-border)] transition-all border border-[var(--yg-border)]">
                        <i class="fas fa-sync text-xs" :class="{'fa-spin': refreshing}"></i>
                        {{-- Refresh --}}
                    </button>
                    @if(isset($ecosystemEngine))
                    <a href="{{ $ecosystemEngine }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all shadow-lg"
                       style="background: linear-gradient(135deg, var(--yg-primary), var(--yg-secondary))">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        Master Panel
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(isset($error))
    <div class="max-w-7xl mx-auto px-4 sm:px-8 mt-4">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[var(--yg-danger)]/10 border border-[var(--yg-danger)]/20 text-sm font-medium" style="color: var(--yg-danger)">
            <i class="fas fa-exclamation-circle"></i> {{ $error }}
        </div>
    </div>
    @endif
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-8 mt-4">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[var(--yg-success)]/10 border border-[var(--yg-success)]/20 text-sm font-medium" style="color: var(--yg-success)">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-8 py-6 space-y-6">

        {{-- ── Stats Grid ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">Searches Today</p>
                        <p class="text-2xl font-heading font-black text-[var(--yg-text)] mt-1">{{ number_format($stats['total_searches_today'] ?? 0) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: var(--yg-primary)15">
                        <i class="fas fa-search text-sm" style="color: var(--yg-primary)"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">Total Users</p>
                        <p class="text-2xl font-heading font-black text-[var(--yg-text)] mt-1">{{ number_format($ecosystemStats['total_users'] ?? 0) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: var(--yg-secondary)15">
                        <i class="fas fa-users text-sm" style="color: var(--yg-secondary)"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">Ecosystem Apps</p>
                        <p class="text-2xl font-heading font-black text-[var(--yg-text)] mt-1">{{ $ecosystemStats['active_apps'] ?? 0 }}<span class="text-sm font-medium text-[var(--yg-text-dim)]">/{{ $ecosystemStats['total_apps'] ?? 0 }}</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #22c55e15">
                        <i class="fas fa-cube text-sm" style="color: #22c55e"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">Zero Result Rate</p>
                        <p class="text-2xl font-heading font-black mt-1 {{ ($stats['zero_result_rate'] ?? 0) > 10 ? 'text-[var(--yg-danger)]' : 'text-[var(--yg-success)]' }}">
                            {{ $stats['zero_result_rate'] ?? 0 }}%
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: {{ ($stats['zero_result_rate'] ?? 0) > 10 ? 'var(--yg-danger)15' : 'var(--yg-success)15' }}">
                        <i class="fas fa-exclamation-triangle text-sm" style="color: {{ ($stats['zero_result_rate'] ?? 0) > 10 ? 'var(--yg-danger)' : 'var(--yg-success)' }}"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">CTR (7d)</p>
                        <p class="text-2xl font-heading font-black mt-1 {{ ($stats['click_through_rate'] ?? 0) < 30 ? 'text-[var(--yg-accent)]' : 'text-[var(--yg-success)]' }}">
                            {{ $stats['click_through_rate'] ?? 0 }}%
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: {{ ($stats['click_through_rate'] ?? 0) < 30 ? '#f59e0b15' : 'var(--yg-success)15' }}">
                        <i class="fas fa-mouse-pointer text-sm" style="color: {{ ($stats['click_through_rate'] ?? 0) < 30 ? '#f59e0b' : 'var(--yg-success)' }}"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card rounded-xl bg-white border border-[var(--yg-border)] p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[var(--yg-text-dim)] uppercase tracking-wider">Indexed Items</p>
                        <p class="text-2xl font-heading font-black text-[var(--yg-text)] mt-1">{{ number_format($stats['total_indexed_items'] ?? 0) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #8b5cf615">
                        <i class="fas fa-database text-sm" style="color: #8b5cf6"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Ecosystem Modules Management ── --}}
        <div class="rounded-xl bg-white border border-[var(--yg-border)] overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--yg-border)] flex items-center justify-between">
                <h3 class="text-base font-heading font-bold text-[var(--yg-text)] flex items-center gap-2">
                    <i class="fas fa-cubes" style="color: var(--yg-primary)"></i>
                    Ecosystem Modules
                </h3>
                <a href="https://master.ygxone.com/admin" target="_blank"
                   class="text-xs font-semibold hover:underline flex items-center gap-1" style="color: var(--yg-primary)">
                    <i class="fas fa-external-link-alt text-[10px]"></i>
                    Manage in Master Panel
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[var(--yg-surface)]">
                            <th class="px-4 py-3 text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">App</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">Slug</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">URL</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">Core</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--yg-border)]">
                        @foreach($allApps ?? $activeApps as $app)
                        <tr class="hover:bg-[var(--yg-surface)]/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs text-white"
                                         style="background: {{ $app['icon_color'] ?? '#6b7280' }}">
                                        <i class="{{ $app['icon'] ?? 'fas fa-cube' }}"></i>
                                    </div>
                                    <span class="font-semibold text-[var(--yg-text)]">{{ $app['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[var(--yg-text-dim)] font-mono text-xs">{{ $app['slug'] }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ $app['url'] }}" target="_blank" class="text-xs hover:underline truncate block max-w-[200px]" style="color: var(--yg-primary)">
                                    {{ $app['url'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($app['is_active'] ?? true)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: var(--yg-success)15; color: var(--yg-success)">
                                    <span class="live-dot"></span> Active
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: var(--yg-danger)15; color: var(--yg-danger)">
                                    <i class="fas fa-pause"></i> Inactive
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($app['is_core'] ?? false)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded" style="background: var(--yg-primary)10; color: var(--yg-primary)">
                                    <i class="fas fa-star"></i> Core
                                </span>
                                @else
                                <span class="text-[10px] text-[var(--yg-text-dim)]">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Charts & Tables Grid ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- Search Volume Chart --}}
            <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
                <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-line text-sm" style="color: var(--yg-primary)"></i>
                    Search Volume (30 Days)
                </h3>
                <div class="chart-container">
                    <canvas id="searchVolumeChart"></canvas>
                </div>
            </div>

            {{-- Index Distribution --}}
            <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
                <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-sm" style="color: var(--yg-secondary)"></i>
                    Index Distribution by Service
                </h3>
                <div class="space-y-4">
                    @foreach($indexedByService ?? [] as $service)
                    @php
                        $percentage = ($stats['total_indexed_items'] ?? 0) > 0 
                            ? round(($service->count / $stats['total_indexed_items']) * 100, 1) 
                            : 0;
                        $serviceColors = [
                            'mail' => ['bg' => '#ef4444', 'bar' => 'bg-red-500'],
                            'drive' => ['bg' => '#22c55e', 'bar' => 'bg-green-500'],
                            'docs' => ['bg' => '#3b82f6', 'bar' => 'bg-blue-500'],
                            'contacts' => ['bg' => '#eab308', 'bar' => 'bg-yellow-500'],
                            'calendar' => ['bg' => '#8b5cf6', 'bar' => 'bg-purple-500'],
                        ];
                        $sc = $serviceColors[$service->service] ?? ['bg' => '#6b7280', 'bar' => 'bg-gray-500'];
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-semibold text-[var(--yg-text)]">{{ ucfirst($service->service) }}</span>
                            <span class="text-[var(--yg-text-dim)]">{{ number_format($service->count) }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-[var(--yg-border)] overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" style="width: {{ $percentage }}%; background: {{ $sc['bg'] }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Trending Queries --}}
            <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
                <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-fire text-sm" style="color: #f59e0b"></i>
                    Top Trending (7d)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">
                                <th class="pb-3 pr-4">#</th>
                                <th class="pb-3 pr-4">Query</th>
                                <th class="pb-3 text-right">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--yg-border)]">
                            @forelse($trendingQueries ?? [] as $index => $query)
                            <tr class="hover:bg-[var(--yg-surface)]/50 transition-colors">
                                <td class="py-3 pr-4 text-xs font-bold text-[var(--yg-text-dim)]">#{{ $index + 1 }}</td>
                                <td class="py-3 pr-4 text-sm text-[var(--yg-text)]">{{ Str::limit($query->query, 50) }}</td>
                                <td class="py-3 text-sm text-right font-bold" style="color: var(--yg-primary)">{{ $query->count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-[var(--yg-text-dim)]">No trending data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Content Gaps --}}
            <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
                <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-sm" style="color: var(--yg-accent)"></i>
                    Content Gaps (Zero Results)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">
                                <th class="pb-3 pr-4">Query</th>
                                <th class="pb-3 pr-4 text-right">Occurrences</th>
                                <th class="pb-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--yg-border)]">
                            @forelse($zeroResultQueries ?? [] as $query)
                            <tr class="hover:bg-[var(--yg-surface)]/50 transition-colors">
                                <td class="py-3 pr-4 text-sm text-[var(--yg-text)]">{{ Str::limit($query->query, 50) }}</td>
                                <td class="py-3 pr-4 text-sm text-right font-bold" style="color: var(--yg-danger)">{{ $query->occurrences }}</td>
                                <td class="py-3 text-center">
                                    <a href="/search?q={{ urlencode($query->query) }}" target="_blank"
                                       class="text-xs font-semibold hover:underline" style="color: var(--yg-primary)">
                                        Search →
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-[var(--yg-text-dim)]">No zero-result queries! 🎉</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Control Actions ── --}}
        <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
            <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                <i class="fas fa-sliders-h text-sm" style="color: var(--yg-primary)"></i>
                Administrative Controls
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <form action="{{ route('admin.cache.clear') }}" method="POST" onsubmit="return confirm('Clear all search cache?')">
                    @csrf
                    <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #f59e0b15">
                            <i class="fas fa-trash text-sm" style="color: #f59e0b"></i>
                        </div>
                        <span class="text-xs font-semibold text-[var(--yg-text)]">Clear Cache</span>
                    </button>
                </form>
                <form action="{{ route('admin.sync') }}" method="POST" onsubmit="return confirm('Sync all ecosystem modules?')">
                    @csrf
                    <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: var(--yg-primary)15">
                            <i class="fas fa-sync text-sm" style="color: var(--yg-primary)"></i>
                        </div>
                        <span class="text-xs font-semibold text-[var(--yg-text)]">Sync Modules</span>
                    </button>
                </form>
                <form action="{{ route('admin.rebuild') }}" method="POST" onsubmit="return confirm('Rebuild entire search index? This may take a while.')">
                    @csrf
                    <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #8b5cf615">
                            <i class="fas fa-database text-sm" style="color: #8b5cf6"></i>
                        </div>
                        <span class="text-xs font-semibold text-[var(--yg-text)]">Rebuild Index</span>
                    </button>
                </form>
                <form action="{{ route('admin.ecosystem.clear') }}" method="POST" onsubmit="return confirm('Clear ecosystem cache?')">
                    @csrf
                    <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #06b6d415">
                            <i class="fas fa-cloud text-sm" style="color: #06b6d4"></i>
                        </div>
                        <span class="text-xs font-semibold text-[var(--yg-text)]">Clear Eco Cache</span>
                    </button>
                </form>
                <a href="https://master.ygxone.com/admin" target="_blank"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-[var(--yg-border)] hover:bg-[var(--yg-surface)] transition-all">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, var(--yg-primary)15, var(--yg-secondary)15)">
                        <i class="fas fa-external-link-alt text-sm" style="color: var(--yg-secondary)"></i>
                    </div>
                    <span class="text-xs font-semibold text-[var(--yg-text)]">Master Panel</span>
                </a>
            </div>
        </div>

        {{-- ── Most Clicked Results ── --}}
        @if(!empty($topClickedResults ?? []))
        <div class="rounded-xl bg-white border border-[var(--yg-border)] p-6">
            <h3 class="text-base font-heading font-bold text-[var(--yg-text)] mb-4 flex items-center gap-2">
                <i class="fas fa-bullseye text-sm" style="color: var(--yg-success)"></i>
                Most Clicked Results
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-[var(--yg-text-dim)] uppercase tracking-wider">
                            <th class="pb-3 pr-4">URL</th>
                            <th class="pb-3 pr-4">Type</th>
                            <th class="pb-3 pr-4 text-right">Clicks</th>
                            <th class="pb-3 pr-4 text-right">Impressions</th>
                            <th class="pb-3 text-right">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--yg-border)]">
                        @foreach($topClickedResults as $result)
                        @php $ctr = ($result->impression_count ?? 0) > 0 ? round(($result->click_count / $result->impression_count) * 100, 1) : 0; @endphp
                        <tr class="hover:bg-[var(--yg-surface)]/50 transition-colors">
                            <td class="py-3 pr-4 text-[var(--yg-text)] max-w-xs truncate">
                                <a href="{{ $result->url ?? '#' }}" target="_blank" class="hover:underline" style="color: var(--yg-primary)">
                                    {{ Str::limit($result->url ?? '', 50) }}
                                </a>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded"
                                      style="background: {{ match($result->result_type ?? 'web') { 'mail' => '#ef444415', 'drive' => '#22c55e15', 'docs' => '#3b82f615', default => '#64748b15' } }};
                                             color: {{ match($result->result_type ?? 'web') { 'mail' => '#ef4444', 'drive' => '#22c55e', 'docs' => '#3b82f6', default => '#64748b' } }}">
                                    {{ ucfirst($result->result_type ?? 'web') }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-right font-bold text-[var(--yg-text)]">{{ number_format($result->click_count ?? 0) }}</td>
                            <td class="py-3 pr-4 text-right text-[var(--yg-text-dim)]">{{ number_format($result->impression_count ?? 0) }}</td>
                            <td class="py-3 text-right font-bold" style="color: {{ $ctr > 50 ? 'var(--yg-success)' : ($ctr > 20 ? 'var(--yg-accent)' : 'var(--yg-danger)') }}">
                                {{ $ctr }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function adminDashboard() {
        return {
            refreshing: false,
            async refreshAll() {
                this.refreshing = true;
                try {
                    await new Promise(r => setTimeout(r, 800));
                    window.location.reload();
                } finally {
                    this.refreshing = false;
                }
            }
        }
    }

    // Search Volume Chart
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('searchVolumeChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const searchVolumeData = @json($searchVolumeData ?? []);
        
        if (searchVolumeData.length > 0) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: searchVolumeData.map(item => item.date),
                    datasets: [{
                        label: 'Searches',
                        data: searchVolumeData.map(item => item.count),
                        borderColor: getComputedStyle(document.documentElement).getPropertyValue('--yg-primary').trim() || '#2563eb',
                        backgroundColor: function(context) {
                            const gradient = ctx.createLinearGradient(0, 0, 0, context.chart.height);
                            gradient.addColorStop(0, (getComputedStyle(document.documentElement).getPropertyValue('--yg-primary').trim() || '#2563eb') + '20');
                            gradient.addColorStop(1, (getComputedStyle(document.documentElement).getPropertyValue('--yg-primary').trim() || '#2563eb') + '02');
                            return gradient;
                        },
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        } else {
            canvas.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-[var(--yg-text-dim)]">No chart data available yet.</div>';
        }
    });
</script>
@endpush
