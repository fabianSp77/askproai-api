<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Progress Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Schritt {{ $currentStep }} von {{ $totalSteps }}
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    @switch($currentStep)
                        @case(1) Unternehmen auswählen @break
                        @case(2) Agents entdecken @break
                        @case(3) Telefonnummern zuordnen @break
                        @case(4) Konfiguration anpassen @break
                        @case(5) Zusammenfassung @break
                    @endswitch
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                     style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
            </div>
        </div>

        {{-- Form --}}
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        {{-- Navigation Buttons --}}
        <div class="flex justify-between items-center pt-6 border-t dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                    <x-filament::button
                        wire:click="previousStep"
                        color="gray"
                        outlined
                    >
                        <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
                        Zurück
                    </x-filament::button>
                @endif
            </div>

            <div>
                @if($currentStep < $totalSteps)
                    <x-filament::button
                        wire:click="nextStep"
                        wire:loading.attr="disabled"
                    >
                        Weiter
                        <x-heroicon-o-arrow-right class="w-4 h-4 ml-2" />
                    </x-filament::button>
                @else
                    <x-filament::button
                        wire:click="import"
                        wire:loading.attr="disabled"
                        color="success"
                    >
                        <x-heroicon-o-check class="w-4 h-4 mr-2" />
                        Import starten
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.flex 
         wire:target="nextStep,previousStep,import,discoverAgents"
         class="fixed inset-0 z-50 items-center justify-center bg-gray-900/50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 flex items-center space-x-4">
            <x-filament::loading-indicator class="h-8 w-8" />
            <span class="text-gray-700 dark:text-gray-300">
                {{ $this->loadingMessage ?? 'Wird verarbeitet...' }}
            </span>
        </div>
    </div>
</x-filament-panels::page>