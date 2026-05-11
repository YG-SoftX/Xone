<x-filament-panels::page>

    @if($showOutput)
    <x-filament::section>
        <div class="flex items-center justify-between mb-3">
            <span class="font-semibold text-sm">Migration Output</span>
            <x-filament::button size="sm" color="gray" wire:click="closeOutput">✕ Close</x-filament::button>
        </div>
        <pre class="text-xs overflow-auto max-h-96 p-4 rounded-lg bg-gray-950 text-green-400">{{ $commandOutput }}</pre>
    </x-filament::section>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($migrationStatuses as $id => $info)
        <x-filament::section>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">{{ $info['icon'] }}</span>
                    <span class="font-semibold">{{ $info['name'] }}</span>
                </div>
                @if(count($info['pending']) > 0)
                    <x-filament::badge color="warning">{{ count($info['pending']) }} pending</x-filament::badge>
                @else
                    <x-filament::badge color="success">Up to date</x-filament::badge>
                @endif
            </div>

            @if(count($info['pending']) > 0)
            <div class="mb-4 space-y-1">
                @foreach($info['pending'] as $migration)
                <div class="text-xs font-mono text-gray-500 truncate">{{ $migration }}</div>
                @endforeach
            </div>

            <x-filament::button
                size="sm" color="warning" icon="heroicon-o-play"
                wire:click="runMigrations('{{ $id }}')"
                wire:loading.attr="disabled"
                wire:confirm="Run {{ count($info['pending']) }} pending migration(s) on {{ $info['name'] }}?">
                Run Migrations
            </x-filament::button>
            @else
            <div class="text-xs text-gray-400">No pending migrations.</div>
            @endif

            {{-- Quick artisan shortcuts --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-wrap gap-1.5">
                <x-filament::button size="xs" color="gray"
                    wire:click="runArtisan('{{ $id }}', 'migrate:status')">
                    Status
                </x-filament::button>
                <x-filament::button size="xs" color="gray"
                    wire:click="runArtisan('{{ $id }}', 'migrate:rollback')"
                    wire:confirm="Rollback the last migration batch on {{ $info['name'] }}?">
                    Rollback Batch
                </x-filament::button>
                <x-filament::button size="xs" color="gray"
                    wire:click="runArtisan('{{ $id }}', 'db:show')">
                    DB Info
                </x-filament::button>
            </div>
        </x-filament::section>
        @endforeach
    </div>

</x-filament-panels::page>
