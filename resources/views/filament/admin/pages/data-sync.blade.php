<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Anrufe synchronisieren --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                <div class="flex-1">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Anrufe synchronisieren
                    </h3>
                    <p class="fi-section-description text-sm text-gray-600 dark:text-gray-400">
                        Laden Sie Anrufe von Retell.ai
                    </p>
                </div>
                <x-heroicon-o-phone class="h-5 w-5 text-gray-400" />
            </div>
            
            <div class="fi-section-content p-6 space-y-3">
                <x-filament::button
                    wire:click="syncTodayCalls"
                    icon="heroicon-o-arrow-path"
                    class="w-full"
                    size="lg"
                >
                    Heutige Anrufe abrufen
                </x-filament::button>
                
                <x-filament::button
                    wire:click="syncLastWeekCalls"
                    icon="heroicon-o-calendar-days"
                    color="gray"
                    class="w-full"
                >
                    Letzte 7 Tage
                </x-filament::button>
                
                <x-filament::button
                    wire:click="syncLastMonthCalls"
                    icon="heroicon-o-calendar"
                    color="gray"
                    class="w-full"
                >
                    Letzter Monat
                </x-filament::button>
            </div>
        </div>
        
        {{-- Termine synchronisieren --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                <div class="flex-1">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Termine synchronisieren
                    </h3>
                    <p class="fi-section-description text-sm text-gray-600 dark:text-gray-400">
                        Laden Sie Termine von Cal.com
                    </p>
                </div>
                <x-heroicon-o-calendar class="h-5 w-5 text-gray-400" />
            </div>
            
            <div class="fi-section-content p-6 space-y-3">
                <x-filament::button
                    wire:click="syncUpcomingAppointments"
                    icon="heroicon-o-arrow-right"
                    color="success"
                    class="w-full"
                    size="lg"
                >
                    Zukünftige Termine
                </x-filament::button>
                
                <x-filament::button
                    wire:click="syncPastAppointments"
                    icon="heroicon-o-arrow-left"
                    color="gray"
                    class="w-full"
                >
                    Vergangene Termine
                </x-filament::button>
            </div>
        </div>
    </div>
    
    {{-- Info Box --}}
    <div class="mt-6 rounded-lg bg-primary-50 p-4 dark:bg-primary-900/20">
        <div class="flex">
            <div class="flex-shrink-0">
                <x-heroicon-s-information-circle class="h-5 w-5 text-primary-400" />
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-primary-800 dark:text-primary-200">
                    So funktioniert die Synchronisation
                </h3>
                <div class="mt-2 text-sm text-primary-700 dark:text-primary-300">
                    <ul class="list-disc space-y-1 pl-5">
                        <li><strong>Heutige Anrufe</strong>: Alle Anrufe von heute</li>
                        <li><strong>Letzte 7 Tage</strong>: Anrufe der letzten Woche</li>
                        <li><strong>Letzter Monat</strong>: Anrufe der letzten 30 Tage</li>
                        <li><strong>Zukünftige Termine</strong>: Alle Termine für die nächsten 90 Tage</li>
                        <li><strong>Vergangene Termine</strong>: Termine der letzten 30 Tage</li>
                    </ul>
                    <p class="mt-2">Bereits importierte Daten werden automatisch übersprungen.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>