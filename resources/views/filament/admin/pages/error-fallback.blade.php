<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600" />
                    Ein Fehler ist aufgetreten
                </div>
            </x-slot>
            
            <div class="space-y-4">
                <p class="text-gray-600 dark:text-gray-400">
                    Es tut uns leid, aber beim Laden der Seite ist ein Fehler aufgetreten. 
                    Dies kann verschiedene Ursachen haben:
                </p>
                
                <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Ein Filter oder eine Aktion konnte nicht ausgeführt werden</li>
                    <li>Die angeforderten Daten sind nicht verfügbar</li>
                    <li>Es fehlen erforderliche Berechtigungen</li>
                    <li>Ein technisches Problem ist aufgetreten</li>
                </ul>
                
                @if($error)
                    <x-filament::section collapsible collapsed>
                        <x-slot name="heading">
                            Technische Details
                        </x-slot>
                        
                        <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-4 rounded overflow-x-auto">{{ json_encode($error, JSON_PRETTY_PRINT) }}</pre>
                    </x-filament::section>
                @endif
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">
                Was können Sie tun?
            </x-slot>
            
            <div class="flex gap-4">
                <x-filament::button wire:click="goBack" color="gray">
                    <x-heroicon-m-arrow-left class="w-4 h-4 mr-1" />
                    Zurück zur vorherigen Seite
                </x-filament::button>
                
                <x-filament::button wire:click="goToDashboard">
                    <x-heroicon-m-home class="w-4 h-4 mr-1" />
                    Zum Dashboard
                </x-filament::button>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">
                Hilfe benötigt?
            </x-slot>
            
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Wenn dieser Fehler wiederholt auftritt, wenden Sie sich bitte an den Support 
                und teilen Sie die folgenden Informationen mit:
            </p>
            
            <dl class="mt-4 grid grid-cols-1 gap-2 text-sm">
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Zeitpunkt:</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ now()->format('d.m.Y H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Benutzer:</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ auth()->user()->name }} (ID: {{ auth()->id() }})</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Vorherige Seite:</dt>
                    <dd class="text-gray-900 dark:text-gray-100 text-xs break-all">{{ $referer }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>