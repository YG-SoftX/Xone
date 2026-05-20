<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button wire:click="save" color="primary" icon="heroicon-o-check-circle">
                Save all browser settings
            </x-filament::button>
            <span class="text-xs text-gray-400">
                Changes take effect immediately across all user browser sessions.
            </span>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
