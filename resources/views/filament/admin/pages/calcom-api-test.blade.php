<x-filament-panels::page>
    <form wire:submit.prevent="testConnection">
        {{ $this->form }}
        
        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="testConnection" />
                <span wire:loading.remove wire:target="testConnection">
                    Test API Connection
                </span>
                <span wire:loading wire:target="testConnection">
                    Testing...
                </span>
            </x-filament::button>
            
            <x-filament::button type="button" color="gray" wire:click="clearResults">
                Clear
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>