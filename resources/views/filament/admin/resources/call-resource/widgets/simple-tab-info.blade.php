<x-filament-widgets::widget>
    @php
        $activeTab = request()->query('activeTab', 'today');
        
        $descriptions = [
            'all' => [
                'title' => 'Alle Anrufe',
                'text' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum an.',
                'color' => 'gray'
            ],
            'today' => [
                'title' => 'Heutige Anrufe',
                'text' => 'Zeigt nur Anrufe, die heute eingegangen sind. Ideal für die tägliche Nachbearbeitung.',
                'color' => 'primary'
            ],
            'high_conversion' => [
                'title' => 'Verkaufschancen',
                'text' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin.',
                'color' => 'success'
            ],
            'needs_followup' => [
                'title' => 'Dringend',
                'text' => 'Anrufe mit negativer Stimmung oder hoher Dringlichkeit, die sofortige Aufmerksamkeit benötigen.',
                'color' => 'danger'
            ],
            'with_appointment' => [
                'title' => 'Erledigt',
                'text' => 'Anrufe mit bereits gebuchtem Termin. Keine weitere Aktion erforderlich.',
                'color' => 'success'
            ],
            'without_customer' => [
                'title' => 'Unbekannt',
                'text' => 'Anrufe ohne Kundenzuordnung. Prüfen Sie, ob es sich um neue Interessenten handelt.',
                'color' => 'warning'
            ]
        ];
        
        $current = $descriptions[$activeTab] ?? $descriptions['today'];
    @endphp
    
    <div class="relative rounded-lg border p-4 
        {{ $current['color'] === 'primary' ? 'bg-primary-50 dark:bg-primary-950/10 border-primary-200 dark:border-primary-800' : '' }}
        {{ $current['color'] === 'success' ? 'bg-success-50 dark:bg-success-950/10 border-success-200 dark:border-success-800' : '' }}
        {{ $current['color'] === 'danger' ? 'bg-danger-50 dark:bg-danger-950/10 border-danger-200 dark:border-danger-800' : '' }}
        {{ $current['color'] === 'warning' ? 'bg-warning-50 dark:bg-warning-950/10 border-warning-200 dark:border-warning-800' : '' }}
        {{ $current['color'] === 'gray' ? 'bg-gray-50 dark:bg-gray-950/10 border-gray-200 dark:border-gray-800' : '' }}">
        
        <!-- Verbindungslinie -->
        <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-{{ $current['color'] }}-500"></div>
        <div class="absolute -top-7 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-{{ $current['color'] }}-500"></div>
        
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 mt-0.5 text-{{ $current['color'] }}-600 dark:text-{{ $current['color'] }}-400" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-{{ $current['color'] }}-900 dark:text-{{ $current['color'] }}-100">
                    {{ $current['title'] }}
                </h3>
                <p class="text-sm text-{{ $current['color'] }}-700 dark:text-{{ $current['color'] }}-300">
                    {{ $current['text'] }}
                </p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>