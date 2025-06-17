<x-filament-panels::page>
    <form wire:submit="testConnection">
        {{ $this->form }}
        
        <div class="mt-6 flex gap-4">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>