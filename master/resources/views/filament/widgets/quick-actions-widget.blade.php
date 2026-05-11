<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Actions
        </x-slot>

        <div class="grid grid-cols-2 gap-4">
            @foreach ($actions as $action)
                <x-filament::button
                    :color="$action['color']"
                    :icon="$action['icon']"
                    wire:click="{{ $action['action'] }}"
                    @if(isset($action['requiresConfirmation']))
                        x-on:click.prevent="$dispatch('open-modal', { id: 'confirm-{{ $action['action'] }}' })"
                    @endif
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Confirmation Modals --}}
    @foreach ($actions as $action)
        @if(isset($action['requiresConfirmation']))
            <x-filament::modal id="confirm-{{ $action['action'] }}">
                <x-slot name="heading">
                    Confirm Action
                </x-slot>

                <p>Are you sure you want to {{ strtolower($action['label']) }}?</p>

                <x-slot name="actions">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'confirm-{{ $action['action'] }}' })">
                        Cancel
                    </x-filament::button>
                    <x-filament::button :color="$action['color']" wire:click="{{ $action['action'] }}">
                        Confirm
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        @endif
    @endforeach
</x-filament-widgets::widget>
