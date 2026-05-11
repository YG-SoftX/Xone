<x-filament-panels::page>

    {{-- App selector ---------------------------------------------------------}}
    <x-filament::section>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Select App:</span>
            <div class="flex flex-wrap gap-2">
                @foreach(app(\App\Services\AppRegistryService::class)->all() as $id => $app)
                <button wire:click="selectApp('{{ $id }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                               {{ $selectedApp===$id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $app['icon'] }} {{ $app['name'] }}
                </button>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    @if($selectedApp && count($envEntries) > 0)

    <x-filament::section :heading="'.env — ' . (app(\App\Services\AppRegistryService::class)->get($selectedApp)['name'] ?? $selectedApp)">
        <p class="text-xs text-gray-500 mb-5">
            Masked keys (APP_KEY, passwords, secrets) are shown as <code>••••••</code> and cannot be edited here.
            Edit them directly via SSH: <code>nano {{ app(\App\Services\AppRegistryService::class)->path($selectedApp) }}/.env</code>
        </p>

        <div class="space-y-2">
            @foreach($envEntries as $i => $entry)

            @if($entry['type'] === 'blank')
                <div class="h-3"></div>

            @elseif($entry['type'] === 'comment')
                <div class="text-xs text-gray-400 font-mono py-1">{{ $entry['value'] }}</div>

            @elseif($entry['type'] === 'key')
                <div class="flex items-center gap-3">
                    <div class="w-64 flex-shrink-0">
                        <code class="text-xs text-gray-700 dark:text-gray-300 font-mono">{{ $entry['key'] }}</code>
                    </div>
                    @if($entry['masked'])
                        <div class="flex-1">
                            <input type="password" value="••••••••••••"
                                   disabled
                                   class="w-full px-3 py-1.5 rounded-lg text-xs font-mono bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed border border-gray-200 dark:border-gray-700">
                        </div>
                        <x-filament::badge color="gray" class="flex-shrink-0">masked</x-filament::badge>
                    @else
                        <div class="flex-1">
                            <input type="text"
                                   wire:model.live.debounce="editData.{{ $entry['key'] }}"
                                   class="w-full px-3 py-1.5 rounded-lg text-xs font-mono border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    @endif
                </div>
            @endif

            @endforeach
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <x-filament::button
                wire:click="saveEnv"
                wire:loading.attr="disabled"
                wire:confirm="Save .env and rebuild config cache for {{ $selectedApp }}?"
                icon="heroicon-o-check"
                color="success">
                Save & Rebuild Cache
            </x-filament::button>

            <x-filament::button wire:click="loadEnv" color="gray" icon="heroicon-o-arrow-path">
                Discard Changes
            </x-filament::button>

            <span class="text-xs text-gray-400">Changes take effect immediately after saving.</span>
        </div>
    </x-filament::section>

    @elseif($selectedApp)
        <x-filament::section>
            <p class="text-sm text-gray-500">No .env file found at: {{ app(\App\Services\AppRegistryService::class)->path($selectedApp) }}/.env</p>
        </x-filament::section>
    @endif

</x-filament-panels::page>
