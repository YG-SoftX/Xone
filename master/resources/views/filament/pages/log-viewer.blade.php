<x-filament-panels::page>

    {{-- Controls ------------------------------------------------------------}}
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-4">

            {{-- App selector --}}
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">App:</span>
                @foreach(app(\App\Services\AppRegistryService::class)->all() as $id => $app)
                @if($app['type'] === 'laravel')
                <button wire:click="selectApp('{{ $id }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                               {{ $selectedApp===$id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $app['icon'] }} {{ $app['name'] }}
                </button>
                @endif
                @endforeach
            </div>

            {{-- Lines selector --}}
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">Lines:</span>
                @foreach([50, 100, 200, 500] as $n)
                <button wire:click="setLines({{ $n }})"
                        class="px-2.5 py-1 rounded text-xs {{ $lines===$n ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800' }}">
                    {{ $n }}
                </button>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    {{-- Log content ---------------------------------------------------------}}
    <x-filament::section>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-500 font-mono">
                @if($selectedApp)
                    {{ app(\App\Services\AppRegistryService::class)->path($selectedApp) }}/storage/logs/laravel.log
                    (last {{ $lines }} lines)
                @endif
            </span>
            <div class="flex gap-2">
                <x-filament::button size="xs" color="gray" wire:click="loadLog" icon="heroicon-o-arrow-path">
                    Refresh
                </x-filament::button>
                <x-filament::button size="xs" color="danger" wire:click="clearLog"
                    wire:confirm="Clear the log file for {{ $selectedApp }}? This cannot be undone."
                    icon="heroicon-o-trash">
                    Clear
                </x-filament::button>
            </div>
        </div>

        <pre class="text-xs overflow-auto max-h-[70vh] p-4 rounded-xl font-mono leading-relaxed"
             style="background:#0a0a0a;color:#a0e0a0">{{ $logContent ?: '(no log content)' }}</pre>
    </x-filament::section>

</x-filament-panels::page>
