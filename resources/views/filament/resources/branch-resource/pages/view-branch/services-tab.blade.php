<div class="space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Service Konfiguration</h3>
        <x-filament::button wire:click="configureServices">
            Services konfigurieren
        </x-filament::button>
    </div>

    @if($getRecord()->serviceConfigurations->count() > 0)
        <div class="grid gap-4">
            @foreach($getRecord()->serviceConfigurations as $config)
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium">{{ $config->masterService->name }}</h4>
                            <p class="text-sm text-gray-600">
                                Dauer: {{ $config->duration_override ?? $config->masterService->default_duration }} Min
                                | Preis: {{ $config->price_override ?? $config->masterService->default_price }}€
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-2 py-1 text-xs rounded-full {{ $config->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $config->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500">Noch keine Services konfiguriert.</p>
    @endif
</div>
