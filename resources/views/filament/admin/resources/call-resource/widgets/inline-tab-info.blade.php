<x-filament-widgets::widget class="inline-tab-info-widget">
    <!-- DEBUG: Widget wird geladen -->
    <div style="background: red; color: white; padding: 10px; margin: 10px 0;">
        DEBUG: Tab Info Widget - Aktiver Tab: {{ request()->query('activeTab', 'today') }}
    </div>
    @php
        $activeTab = request()->query('activeTab', 'today');
        
        $tabInfos = [
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
                'title' => 'Dringende Anrufe',
                'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch.',
                'hint' => 'Diese Anrufer benötigen sofortige Aufmerksamkeit, um Unzufriedenheit zu vermeiden!',
                'color' => 'danger'
            ],
            'with_appointment' => [
                'title' => 'Erledigte Anrufe',
                'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin.',
                'hint' => 'Keine weitere Aktion erforderlich - diese Anrufer sind bereits versorgt.',
                'color' => 'success'
            ],
            'without_customer' => [
                'title' => 'Unbekannte Anrufer',
                'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung.',
                'hint' => 'Prüfen Sie, ob es sich um neue Interessenten oder Spam-Anrufe handelt.',
                'color' => 'warning'
            ]
        ];
        
        $currentInfo = $tabInfos[$activeTab] ?? $tabInfos['today'];
    @endphp
    
    <div style="position: relative; margin-top: -0.5rem; margin-bottom: 1rem;">
        <!-- Verbindungslinie -->
        <div style="position: absolute; top: -1rem; left: 50%; transform: translateX(-50%); width: 2px; height: 1.5rem; background-color: rgb(250, 204, 21);"></div>
        <div style="position: absolute; top: -1.25rem; left: 50%; transform: translateX(-50%); width: 8px; height: 8px; border-radius: 50%; background-color: rgb(250, 204, 21); border: 2px solid white;"></div>
        
        <!-- Info Box -->
        <div class="rounded-lg border p-4 
            @if($currentInfo['color'] == 'primary') bg-amber-50 dark:bg-amber-950/10 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-100
            @elseif($currentInfo['color'] == 'success') bg-green-50 dark:bg-green-950/10 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100  
            @elseif($currentInfo['color'] == 'danger') bg-red-50 dark:bg-red-950/10 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100
            @elseif($currentInfo['color'] == 'warning') bg-orange-50 dark:bg-orange-950/10 border-orange-200 dark:border-orange-800 text-orange-900 dark:text-orange-100
            @else bg-gray-50 dark:bg-gray-950/10 border-gray-200 dark:border-gray-800 text-gray-900 dark:text-gray-100
            @endif">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 mt-0.5
                        @if($currentInfo['color'] == 'primary') text-amber-600 dark:text-amber-400
                        @elseif($currentInfo['color'] == 'success') text-green-600 dark:text-green-400
                        @elseif($currentInfo['color'] == 'danger') text-red-600 dark:text-red-400
                        @elseif($currentInfo['color'] == 'warning') text-orange-600 dark:text-orange-400
                        @else text-gray-600 dark:text-gray-400
                        @endif" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold mb-1">
                        {{ $currentInfo['title'] }}
                    </h4>
                    <p class="text-sm opacity-90">
                        {{ $currentInfo['description'] }}
                    </p>
                    <p class="mt-1 text-sm font-medium opacity-80">
                        {{ $currentInfo['hint'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>

</x-filament-widgets::widget>