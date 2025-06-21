<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Anruf-Synchronisation --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                <div class="flex-1">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Anrufe abrufen
                    </h3>
                    <p class="fi-section-description text-sm text-gray-600 dark:text-gray-400">
                        Laden Sie Anrufe von Retell.ai
                    </p>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Von Datum</label>
                        <input type="date" 
                               wire:model="callDateFrom" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bis Datum</label>
                        <input type="date" 
                               wire:model="callDateTo" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Max. Anzahl</label>
                        <input type="number" 
                               wire:model="callLimit" 
                               min="1" 
                               max="500"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Min. Dauer (Sek.)</label>
                        <input type="number" 
                               wire:model="callMinDuration" 
                               min="0"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                
                <div class="mt-4">
                    <x-filament::button
                        wire:click="syncCalls"
                        icon="heroicon-o-arrow-path"
                        color="primary"
                    >
                        Anrufe abrufen
                    </x-filament::button>
                </div>
            </div>
        </div>
        
        {{-- Termin-Synchronisation --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                <div class="flex-1">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Termine abrufen
                    </h3>
                    <p class="fi-section-description text-sm text-gray-600 dark:text-gray-400">
                        Laden Sie Termine von Cal.com
                    </p>
                </div>
            </div>
            
            <div class="fi-section-content p-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Von Datum</label>
                        <input type="date" 
                               wire:model="appointmentDateFrom" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bis Datum</label>
                        <input type="date" 
                               wire:model="appointmentDateTo" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Max. Anzahl</label>
                        <input type="number" 
                               wire:model="appointmentLimit" 
                               min="1" 
                               max="1000"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                
                <div class="mt-4">
                    <x-filament::button
                        wire:click="syncAppointments"
                        icon="heroicon-o-calendar"
                        color="success"
                    >
                        Termine abrufen
                    </x-filament::button>
                </div>
            </div>
        </div>
        
        {{-- Hinweise --}}
        <div class="rounded-lg bg-primary-50 p-4 dark:bg-primary-900/20">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-information-circle class="h-5 w-5 text-primary-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-primary-800 dark:text-primary-200">
                        Hinweise
                    </h3>
                    <div class="mt-2 text-sm text-primary-700 dark:text-primary-300">
                        <ul class="list-disc space-y-1 pl-5">
                            <li>Anrufe: Es werden nur Anrufe mit der angegebenen Mindestdauer importiert</li>
                            <li>Bereits existierende Einträge werden automatisch übersprungen</li>
                            <li>Die Synchronisation läuft im Vordergrund - bitte warten Sie bis zum Abschluss</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>