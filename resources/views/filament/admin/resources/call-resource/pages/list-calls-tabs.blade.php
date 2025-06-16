<x-filament-panels::page>
    @php
        $activeTab = $this->activeTab ?? 'today';
        $tabs = [
            'all' => [
                'description' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum an.',
                'hint' => 'Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Kundeninteraktionen.'
            ],
            'today' => [
                'description' => 'Zeigt nur Anrufe, die heute eingegangen sind.',
                'hint' => 'Ideal für die tägliche Nachbearbeitung und um keinen aktuellen Anruf zu verpassen.'
            ],
            'high_conversion' => [
                'description' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin.',
                'hint' => 'Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!'
            ],
            'needs_followup' => [
                'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch.',
                'hint' => 'Diese Anrufer benötigen sofortige Aufmerksamkeit, um Unzufriedenheit zu vermeiden!'
            ],
            'with_appointment' => [
                'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin.',
                'hint' => 'Keine weitere Aktion erforderlich - diese Anrufer sind bereits versorgt.'
            ],
            'without_customer' => [
                'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung.',
                'hint' => 'Prüfen Sie, ob es sich um neue Interessenten oder Spam-Anrufe handelt.'
            ],
        ];
        
        $currentTab = $tabs[$activeTab] ?? $tabs['today'];
    @endphp

    <div class="fi-ta-tabs-wrapper">
        {{ $this->form }}
        
        @if(isset($currentTab))
            <div class="mt-4 mb-6">
                <div class="relative">
                    <!-- Verbindungslinie zum aktiven Tab -->
                    <div class="absolute -top-4 left-0 right-0 flex justify-center">
                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                    
                    <!-- Beschreibungsbox -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $currentTab['description'] }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $currentTab['hint'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>