<x-filament-panels::page>

    @if($showOutput)
    <x-filament::section>
        <div class="flex items-center justify-between mb-3">
            <span class="font-semibold text-sm">Backup Output</span>
            <x-filament::button size="sm" color="gray" wire:click="closeOutput">✕ Close</x-filament::button>
        </div>
        <pre class="text-xs overflow-auto max-h-64 p-4 rounded-lg bg-gray-950 text-green-400">{{ $commandOutput }}</pre>
    </x-filament::section>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($backups as $id => $info)
        <x-filament::section>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">{{ $info['icon'] }}</span>
                    <div>
                        <div class="font-semibold">{{ $info['name'] }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ $info['backupDir'] }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($info['hasScript'])
                    <x-filament::button
                        size="xs" color="primary" icon="heroicon-o-archive-box-arrow-down"
                        wire:click="runBackup('{{ $id }}')"
                        wire:loading.attr="disabled">
                        Backup Now
                    </x-filament::button>
                    @else
                    <x-filament::badge color="gray">No script</x-filament::badge>
                    @endif
                </div>
            </div>

            @if(count($info['files']) > 0)
            <div class="space-y-1.5">
                @foreach($info['files'] as $file)
                <div class="flex items-center justify-between text-xs py-1.5 px-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center gap-2 min-w-0">
                        <span>💾</span>
                        <span class="font-mono truncate">{{ $file['name'] }}</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0 ml-2">
                        <span class="text-gray-400">{{ $file['size'] }}</span>
                        <span class="text-gray-400">{{ $file['mtime'] }}</span>
                        <button wire:click="deleteBackup('{{ addslashes($file['path']) }}')"
                                wire:confirm="Delete {{ $file['name'] }}?"
                                class="text-danger-500 hover:text-danger-700 transition">✕</button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-xs text-gray-400 py-4 text-center">No backup files found.</div>
            @endif
        </x-filament::section>
        @endforeach
    </div>

</x-filament-panels::page>
