<x-filament-panels::page>
    @php
        $activeTab = $this->activeTab ?? 'today';
        $tabDescriptions = [
            'all' => [
                'title' => 'Alle Anrufe',
                'description' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum an.',
                'hint' => 'Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Kundeninteraktionen.',
                'color' => 'gray'
            ],
            'today' => [
                'title' => 'Heutige Anrufe', 
                'description' => 'Zeigt nur Anrufe, die heute eingegangen sind.',
                'hint' => 'Ideal für die tägliche Nachbearbeitung und um keinen aktuellen Anruf zu verpassen.',
                'color' => 'primary'
            ],
            'high_conversion' => [
                'title' => 'Verkaufschancen',
                'description' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin.',
                'hint' => 'Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!',
                'color' => 'success'
            ],
            'needs_followup' => [
                'title' => 'Dringend',
                'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch.',
                'hint' => 'Diese Anrufer benötigen sofortige Aufmerksamkeit, um Unzufriedenheit zu vermeiden!',
                'color' => 'danger'
            ],
            'with_appointment' => [
                'title' => 'Erledigt',
                'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin.',
                'hint' => 'Keine weitere Aktion erforderlich - diese Anrufer sind bereits versorgt.',
                'color' => 'success'
            ],
            'without_customer' => [
                'title' => 'Unbekannt',
                'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung.',
                'hint' => 'Prüfen Sie, ob es sich um neue Interessenten oder Spam-Anrufe handelt.',
                'color' => 'warning'
            ]
        ];
        
        $currentTab = $tabDescriptions[$activeTab] ?? $tabDescriptions['today'];
    @endphp
    
    <!-- Tab Beschreibung mit Verbindungslinie -->
    <div class="relative" style="margin-top: -1rem; margin-bottom: 1.5rem;">
        <!-- Verbindungslinie -->
        <div class="absolute left-1/2 -translate-x-1/2" style="top: -0.5rem; width: 2px; height: 1.5rem; background-color: rgb(var(--primary-500));"></div>
        
        <!-- Punkt am Ende der Linie -->
        <div class="absolute left-1/2 -translate-x-1/2" style="top: -0.75rem; width: 8px; height: 8px; border-radius: 50%; background-color: rgb(var(--primary-500)); border: 2px solid white;"></div>
        
        <!-- Beschreibungsbox -->
        <div class="relative rounded-lg border p-4 {{ $currentTab['color'] === 'primary' ? 'bg-primary-50 dark:bg-primary-950/10 border-primary-200 dark:border-primary-800' : '' }}
                    {{ $currentTab['color'] === 'success' ? 'bg-success-50 dark:bg-success-950/10 border-success-200 dark:border-success-800' : '' }}
                    {{ $currentTab['color'] === 'danger' ? 'bg-danger-50 dark:bg-danger-950/10 border-danger-200 dark:border-danger-800' : '' }}
                    {{ $currentTab['color'] === 'warning' ? 'bg-warning-50 dark:bg-warning-950/10 border-warning-200 dark:border-warning-800' : '' }}
                    {{ $currentTab['color'] === 'gray' ? 'bg-gray-50 dark:bg-gray-950/10 border-gray-200 dark:border-gray-800' : '' }}">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="h-5 w-5 {{ $currentTab['color'] === 'primary' ? 'text-primary-600 dark:text-primary-400' : '' }}
                                        {{ $currentTab['color'] === 'success' ? 'text-success-600 dark:text-success-400' : '' }}
                                        {{ $currentTab['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : '' }}
                                        {{ $currentTab['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' : '' }}
                                        {{ $currentTab['color'] === 'gray' ? 'text-gray-600 dark:text-gray-400' : '' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold {{ $currentTab['color'] === 'primary' ? 'text-primary-900 dark:text-primary-100' : '' }}
                                                      {{ $currentTab['color'] === 'success' ? 'text-success-900 dark:text-success-100' : '' }}
                                                      {{ $currentTab['color'] === 'danger' ? 'text-danger-900 dark:text-danger-100' : '' }}
                                                      {{ $currentTab['color'] === 'warning' ? 'text-warning-900 dark:text-warning-100' : '' }}
                                                      {{ $currentTab['color'] === 'gray' ? 'text-gray-900 dark:text-gray-100' : '' }} mb-1">
                        {{ $currentTab['title'] }}
                    </h3>
                    <p class="text-sm {{ $currentTab['color'] === 'primary' ? 'text-primary-700 dark:text-primary-300' : '' }}
                                      {{ $currentTab['color'] === 'success' ? 'text-success-700 dark:text-success-300' : '' }}
                                      {{ $currentTab['color'] === 'danger' ? 'text-danger-700 dark:text-danger-300' : '' }}
                                      {{ $currentTab['color'] === 'warning' ? 'text-warning-700 dark:text-warning-300' : '' }}
                                      {{ $currentTab['color'] === 'gray' ? 'text-gray-700 dark:text-gray-300' : '' }}">
                        {{ $currentTab['description'] }}
                    </p>
                    <p class="mt-1 text-sm font-medium {{ $currentTab['color'] === 'primary' ? 'text-primary-800 dark:text-primary-200' : '' }}
                                                       {{ $currentTab['color'] === 'success' ? 'text-success-800 dark:text-success-200' : '' }}
                                                       {{ $currentTab['color'] === 'danger' ? 'text-danger-800 dark:text-danger-200' : '' }}
                                                       {{ $currentTab['color'] === 'warning' ? 'text-warning-800 dark:text-warning-200' : '' }}
                                                       {{ $currentTab['color'] === 'gray' ? 'text-gray-800 dark:text-gray-200' : '' }}">
                        {{ $currentTab['hint'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>