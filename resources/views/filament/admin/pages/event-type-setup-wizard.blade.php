<x-filament-panels::page>
    
    @if($this->eventType)
        <div class="space-y-6">
            {{-- Progress Overview --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->eventType->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Setup-Fortschritt: {{ $this->eventType->getSetupProgress() }}%
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($this->eventType->setup_status === 'complete') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                            @elseif($this->eventType->setup_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                            @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                            @endif">
                            @if($this->eventType->setup_status === 'complete')
                                Vollständig konfiguriert
                            @elseif($this->eventType->setup_status === 'partial')
                                Teilweise konfiguriert
                            @else
                                Unvollständig
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                         style="width: {{ $this->eventType->getSetupProgress() }}%"></div>
                </div>

                {{-- Checklist Summary --}}
                @if($this->checklist)
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($this->checklist as $key => $item)
                            <div class="flex items-center space-x-2">
                                @if($item['completed'] ?? false)
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                @else
                                    <x-heroicon-o-x-circle class="w-5 h-5 text-gray-400" />
                                @endif
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $item['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cal.com Links Section --}}
            @if(!empty($this->calcomLinks))
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">
                        <x-heroicon-o-arrow-top-right-on-square class="inline-block w-5 h-5 mr-2" />
                        Direkte Cal.com Einstellungen
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">
                        Einige Einstellungen müssen direkt in Cal.com vorgenommen werden. 
                        Nutzen Sie diese Links um direkt zu den jeweiligen Abschnitten zu gelangen:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->calcomLinks as $section => $linkData)
                            @if($linkData['success'] ?? false)
                                <a href="{{ $linkData['url'] }}" 
                                   target="_blank"
                                   class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg hover:shadow-md transition-shadow">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                            @switch($section)
                                                @case('availability')
                                                    <x-heroicon-o-calendar-days class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                                                    @break
                                                @case('advanced')
                                                    <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                                                    @break
                                                @case('workflows')
                                                    <x-heroicon-o-bell-alert class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                                                    @break
                                                @case('webhooks')
                                                    <x-heroicon-o-link class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                                                    @break
                                            @endswitch
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $linkData['section_name'] ?? ucfirst($section) }}
                                            </span>
                                            @if($linkData['instructions'] ?? false)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ Str::limit($linkData['instructions'], 50) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400" />
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Main Form --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <form wire:submit.prevent="saveSettings">
                    {{ $this->form }}
                    
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" />
                            Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>

            {{-- Help Section --}}
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center mr-3">
                        <x-heroicon-o-question-mark-circle class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    Hilfe & Tipps
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                                    <x-heroicon-o-light-bulb class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                </div>
                            </div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                    Hybrid-Konfiguration
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Grundlegende Einstellungen werden automatisch synchronisiert. Erweiterte Funktionen direkt in Cal.com.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                    <x-heroicon-o-clock class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                    Verfügbarkeiten
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Arbeitszeiten in Cal.com definieren. Gelten dann für alle Event-Types.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <x-heroicon-o-users class="w-5 h-5 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                    Team-Events
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Mehrere Mitarbeiter? Team-Event-Types für automatische Verteilung nutzen.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center">
                        <x-heroicon-o-information-circle class="w-5 h-5 mr-2 flex-shrink-0" />
                        <span><strong>Tipp:</strong> Nutzen Sie die direkten Cal.com Links im letzten Schritt des Wizards für erweiterte Einstellungen.</span>
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Event Type Selection --}}
        <div class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full mb-4">
                            <x-heroicon-o-calendar-days class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                            Event-Type Konfiguration
                        </h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400 mt-3 max-w-2xl mx-auto">
                            Verwalten Sie Ihre Cal.com Event-Types zentral aus AskProAI heraus. 
                            Wählen Sie einen Event-Type aus, um die Konfiguration zu starten.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        {{ $this->form }}
                    </div>
                </div>
                
                {{-- Additional Help --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-question-mark-circle class="w-5 h-5 text-gray-400 mt-0.5" />
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <p class="font-medium mb-1">Wo finde ich meine Event-Types?</p>
                            <p>Event-Types müssen zuerst aus Cal.com importiert oder manuell angelegt werden. 
                            Nutzen Sie die <a href="/admin/event-type-import-wizard" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">Import-Funktion</a>, 
                            um Event-Types aus Cal.com zu übernehmen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading.flex 
         wire:target="saveSettings"
         class="fixed inset-0 bg-gray-900/50 z-50 items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Einstellungen werden gespeichert...</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>