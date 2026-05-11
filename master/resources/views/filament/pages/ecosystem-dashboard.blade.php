<x-filament-panels::page>

    {{-- exec() warning -------------------------------------------------------}}
    @if(!$execAvailable)
    <x-filament::section>
        <div class="flex items-center gap-3 text-warning-600">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5"/>
            <span class="text-sm font-medium">
                PHP <code>exec()</code> is disabled on this server.
                Update/rollback buttons will report an error.
                Run <code>bash scripts/update.sh</code> via SSH instead.
            </span>
        </div>
    </x-filament::section>
    @endif

    {{-- Command output modal -------------------------------------------------}}
    @if($showOutput)
    <x-filament::section>
        <div class="flex items-center justify-between mb-3">
            <span class="font-semibold text-sm">{{ $commandTitle }}</span>
            <x-filament::button size="sm" color="gray" wire:click="closeOutput">✕ Close</x-filament::button>
        </div>
        <pre class="text-xs overflow-auto max-h-96 p-4 rounded-lg bg-gray-950 text-green-400">{{ $commandOutput }}</pre>
    </x-filament::section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- ECOSYSTEM OVERVIEW STATS                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(!empty($ecosystemStats) && !isset($ecosystemStats['error']))
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        
        {{-- Total Users --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-heroicon-o-users class="w-6 h-6 text-blue-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-blue-600">{{ number_format($ecosystemStats['total_users'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Total Users</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Active Users --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-green-600">{{ number_format($ecosystemStats['active_users'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Active Users</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Monthly Revenue --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <x-heroicon-o-currency-dollar class="w-6 h-6 text-emerald-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-emerald-600">${{ number_format($ecosystemStats['monthly_revenue'] ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-500">Monthly Revenue</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Active Subscriptions --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <x-heroicon-o-credit-card class="w-6 h-6 text-purple-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-purple-600">{{ number_format($ecosystemStats['active_subscriptions'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Subscriptions</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Active Services --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-heroicon-o-cube class="w-6 h-6 text-indigo-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-indigo-600">{{ $ecosystemStats['active_apps'] ?? 0 }}/{{ $ecosystemStats['total_apps'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Active Services</div>
                </div>
            </div>
        </x-filament::section>

        {{-- YG Pay Status --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <x-heroicon-o-credit-card class="w-6 h-6 text-orange-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-orange-600">{{ strtoupper($ecosystemStats['yg_pay_status'] ?? 'N/A') }}</div>
                    <div class="text-xs text-gray-500">YG Pay Status</div>
                </div>
            </div>
        </x-filament::section>
        
        {{-- Active Gateways --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                    <x-heroicon-o-building-library class="w-6 h-6 text-pink-600"/>
                </div>
                <div>
                    <div class="text-xl font-bold text-pink-600">{{ $ecosystemStats['active_gateways'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Active Gateways</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Critical Alerts --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <div class="p-2 {{ ($ecosystemStats['critical_alerts'] ?? 0) > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-800' }} rounded-lg">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 {{ ($ecosystemStats['critical_alerts'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-600' }}"/>
                </div>
                <div>
                    <div class="text-xl font-bold {{ ($ecosystemStats['critical_alerts'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-600' }}">{{ $ecosystemStats['critical_alerts'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Critical Alerts</div>
                </div>
            </div>
        </x-filament::section>
    </div>
    @endif

    {{-- Summary stats (legacy) -----------------------------------------------}}
    @php
        $total    = count($statuses);
        $up       = collect($statuses)->filter(fn($s)=>$s['health']==='up')->count();
        $pending  = collect($statuses)->sum('pending_migrations');
        $errors   = collect($statuses)->sum('log_errors');
        $debugOn  = collect($statuses)->filter(fn($s)=>$s['debug'])->count();
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['Services Online',   "$up / $total",  $up===$total?'success':'warning', 'heroicon-o-server'],
            ['Pending Migrations',$pending,         $pending>0?'warning':'success',   'heroicon-o-table-cells'],
            ['Log Errors',        $errors,          $errors>0?'danger':'success',     'heroicon-o-exclamation-circle'],
            ['Debug ON',          $debugOn,         $debugOn>0?'danger':'success',    'heroicon-o-bug-ant'],
            ['Apps Tracked',      $total,           'gray',                           'heroicon-o-cube'],
        ] as [$label,$value,$color,$icon])
        <x-filament::section>
            <div class="flex items-center gap-3">
                <x-dynamic-component :component="$icon" class="w-8 h-8 text-{{ $color }}-500"/>
                <div>
                    <div class="text-2xl font-bold text-{{ $color }}-600">{{ $value }}</div>
                    <div class="text-xs text-gray-500">{{ $label }}</div>
                </div>
            </div>
        </x-filament::section>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SERVICE HEALTH MONITOR                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(!empty($serviceHealth))
    <x-filament::section heading="Service Health Monitor" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($serviceHealth as $appId => $health)
            @php
                $appConfig = \App\Services\AppRegistryService::class;
                $registry = app($appConfig);
                $apps = $registry->all();
                $appInfo = $apps[$appId] ?? null;
            @endphp
            
            <div class="p-4 rounded-lg border-2 {{ 
                $health['status'] === 'healthy' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 
                ($health['status'] === 'unhealthy' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 
                'border-red-500 bg-red-50 dark:bg-red-900/20') 
            }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">{{ $appInfo['icon'] ?? '📦' }}</span>
                        <div class="font-semibold">{{ $appInfo['name'] ?? $appId }}</div>
                    </div>
                    <div class="px-2 py-1 rounded-full text-xs font-bold {{ 
                        $health['status'] === 'healthy' ? 'bg-green-500 text-white' : 
                        ($health['status'] === 'unhealthy' ? 'bg-yellow-500 text-white' : 
                        'bg-red-500 text-white') 
                    }}">
                        {{ strtoupper($health['status']) }}
                    </div>
                </div>
                
                @if($health['status'] === 'healthy')
                <div class="text-xs text-gray-600 space-y-1">
                    <div><strong>Version:</strong> {{ $health['version'] ?? 'N/A' }}</div>
                    <div><strong>Last Check:</strong> {{ \Carbon\Carbon::parse($health['last_check'])->diffForHumans() }}</div>
                </div>
                @else
                <div class="text-xs text-red-700 dark:text-red-400">
                    <strong>Error:</strong> {{ $health['error'] ?? 'Unknown' }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </x-filament::section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- RECENT ALERTS                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(!empty($recentAlerts))
    <x-filament::section heading="Recent System Alerts" class="mb-6">
        <div class="space-y-3">
            @foreach($recentAlerts as $alert)
            <div class="flex items-start gap-3 p-3 rounded-lg {{ 
                $alert['severity'] === 'critical' ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500' : 
                ($alert['severity'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500' : 
                'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500') 
            }}">
                <x-dynamic-component 
                    :component="$alert['severity'] === 'critical' ? 'heroicon-o-exclamation-circle' : 
                    ($alert['severity'] === 'warning' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-information-circle')" 
                    class="w-5 h-5 mt-0.5 {{ 
                        $alert['severity'] === 'critical' ? 'text-red-600' : 
                        ($alert['severity'] === 'warning' ? 'text-yellow-600' : 'text-blue-600') 
                    }}"
                />
                <div class="flex-1">
                    <div class="text-sm font-medium">{{ $alert['message'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $alert['timestamp'] }} • Source: {{ $alert['source'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- APP STATUS TABLE                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <x-filament::section heading="Service Management">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Service</th>
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Health</th>
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Commit</th>
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Environment</th>
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Migrations</th>
                        <th class="pb-3 pr-4 font-semibold text-gray-600">Errors</th>
                        <th class="pb-3 font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statuses as $id => $s)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">

                        {{-- Service name --}}
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ $s['icon'] }}</span>
                                <div>
                                    <div class="font-medium">{{ $s['name'] }}</div>
                                    @if(!$s['exists'])
                                    <div class="text-xs text-danger-500">Path not found</div>
                                    @else
                                    <div class="text-xs text-gray-400">{{ $s['branch'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Health --}}
                        <td class="py-3 pr-4">
                            @php $healthColors = ['up'=>'success','error'=>'danger','degraded'=>'warning','unreachable'=>'danger','unknown'=>'gray']; @endphp
                            <x-filament::badge :color="$healthColors[$s['health']] ?? 'gray'">
                                @if($s['http_code'] > 0) {{ $s['http_code'] }} @endif {{ $s['health'] }}
                            </x-filament::badge>
                        </td>

                        {{-- Commit --}}
                        <td class="py-3 pr-4">
                            <code class="text-xs">{{ $s['commit'] }}{{ $s['dirty'] ? '*' : '' }}</code>
                        </td>

                        {{-- Env + debug --}}
                        <td class="py-3 pr-4">
                            <x-filament::badge :color="$s['env']==='production'?'success':'warning'">
                                {{ $s['env'] }}
                            </x-filament::badge>
                            @if($s['debug'])
                            <x-filament::badge color="danger" class="ml-1">DEBUG</x-filament::badge>
                            @endif
                        </td>

                        {{-- Pending migrations --}}
                        <td class="py-3 pr-4">
                            @if($s['pending_migrations'] > 0)
                            <x-filament::badge color="warning">{{ $s['pending_migrations'] }} pending</x-filament::badge>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Log errors --}}
                        <td class="py-3 pr-4">
                            @if($s['log_errors'] > 0)
                            <x-filament::badge color="danger">{{ $s['log_errors'] }}</x-filament::badge>
                            @else
                            <span class="text-gray-400 text-xs">0</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="py-3">
                            @if($s['exists'])
                            <div class="flex flex-wrap gap-1.5">
                                <x-filament::button
                                    size="xs" color="primary" icon="heroicon-o-arrow-up-circle"
                                    wire:click="updateApp('{{ $id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Update {{ $s['name'] }}? It will go into maintenance mode briefly.">
                                    Update
                                </x-filament::button>

                                <x-filament::button
                                    size="xs" color="warning" icon="heroicon-o-arrow-uturn-left"
                                    wire:click="rollbackApp('{{ $id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Rollback {{ $s['name'] }} to the previous commit?">
                                    Rollback
                                </x-filament::button>

                                <x-filament::button
                                    size="xs" color="gray" icon="heroicon-o-arrow-down-tray"
                                    wire:click="pullApp('{{ $id }}')"
                                    wire:loading.attr="disabled">
                                    Pull
                                </x-filament::button>

                                @if($s['pending_migrations'] > 0)
                                <x-filament::button
                                    size="xs" color="success" icon="heroicon-o-table-cells"
                                    wire:click="migrateApp('{{ $id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Run {{ $s['pending_migrations'] }} pending migration(s) on {{ $s['name'] }}?">
                                    Migrate
                                </x-filament::button>
                                @endif

                                <x-filament::button
                                    size="xs" color="gray" icon="heroicon-o-arrow-path"
                                    wire:click="rebuildCaches('{{ $id }}')"
                                    wire:loading.attr="disabled">
                                    Caches
                                </x-filament::button>

                                <x-filament::button
                                    size="xs" color="gray" icon="heroicon-o-archive-box-arrow-down"
                                    wire:click="backupApp('{{ $id }}')"
                                    wire:loading.attr="disabled">
                                    Backup
                                </x-filament::button>

                                <x-filament::button
                                    tag="a"
                                    size="xs" color="gray" icon="heroicon-o-document-magnifying-glass"
                                    :href="route('filament.ygx-control-center.pages.log-viewer', [], false)">
                                    Logs
                                </x-filament::button>

                                <x-filament::button
                                    tag="a"
                                    size="xs" color="gray" icon="heroicon-o-document-text"
                                    :href="route('filament.ygx-control-center.pages.env-editor', [], false)">
                                    .env
                                </x-filament::button>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">Path not configured</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Last updated times ---------------------------------------------------}}
    <x-filament::section heading="Last Updated">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($statuses as $id => $s)
            @if($s['exists'])
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                <div class="text-xs font-medium">{{ $s['icon'] }} {{ $s['name'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $s['last_updated'] ?? 'Never (no update run yet)' }}</div>
            </div>
            @endif
            @endforeach
        </div>
    </x-filament::section>

    <div wire:loading class="fixed inset-0 bg-black/20 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 px-6 py-4 rounded-xl shadow-xl flex items-center gap-3">
            <x-filament::loading-indicator class="h-6 w-6"/>
            <span class="text-sm font-medium">Running command…</span>
        </div>
    </div>

</x-filament-panels::page>
