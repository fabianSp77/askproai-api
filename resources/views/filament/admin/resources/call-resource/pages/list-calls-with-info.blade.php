<x-filament-panels::page>
    <style>
        /* Tab-Info Styles */
        .fi-resource-tabs + .tab-info-wrapper {
            margin-top: -0.5rem !important;
        }
        
        .tab-line-container {
            pointer-events: none;
        }
        
        .dark .tab-dot {
            border-color: rgb(31, 41, 55) !important;
        }
        
        /* Entferne margin von Stats Widget wenn vorhanden */
        .tab-info-wrapper + .fi-section {
            margin-top: 1rem !important;
        }
    </style>
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
                'color' => 'amber'
            ],
            'high_conversion' => [
                'title' => 'Verkaufschancen',
                'description' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin.',
                'hint' => 'Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!',
                'color' => 'green'
            ],
            'needs_followup' => [
                'title' => 'Dringende Anrufe',
                'description' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch.',
                'hint' => 'Diese Anrufer benötigen sofortige Aufmerksamkeit, um Unzufriedenheit zu vermeiden!',
                'color' => 'red'
            ],
            'with_appointment' => [
                'title' => 'Erledigte Anrufe',
                'description' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin.',
                'hint' => 'Keine weitere Aktion erforderlich - diese Anrufer sind bereits versorgt.',
                'color' => 'green'
            ],
            'without_customer' => [
                'title' => 'Unbekannte Anrufer',
                'description' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung.',
                'hint' => 'Prüfen Sie, ob es sich um neue Interessenten oder Spam-Anrufe handelt.',
                'color' => 'orange'
            ]
        ];
        
        $currentInfo = $tabInfos[$activeTab] ?? $tabInfos['today'];
    @endphp

    <!-- Tab Info direkt nach den Tabs -->
    <div class="tab-info-wrapper" style="margin-bottom: 1.5rem; position: relative;">
        <!-- Verbindungslinie Container -->
        <div class="tab-line-container" style="position: absolute; top: -1.5rem; left: 0; right: 0; height: 1.5rem;">
            <div class="tab-line" style="position: absolute; top: 0; width: 2px; height: 100%; background-color: rgb(250, 204, 21); transition: left 0.3s ease;"></div>
            <div class="tab-dot" style="position: absolute; top: -4px; width: 8px; height: 8px; margin-left: -3px; border-radius: 50%; background-color: rgb(250, 204, 21); border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: left 0.3s ease;"></div>
        </div>
            
            <!-- Info Box -->
            <div class="rounded-lg border p-4 bg-{{ $currentInfo['color'] }}-50 dark:bg-{{ $currentInfo['color'] }}-950/10 border-{{ $currentInfo['color'] }}-200 dark:border-{{ $currentInfo['color'] }}-800">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 mt-0.5 text-{{ $currentInfo['color'] }}-600 dark:text-{{ $currentInfo['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-{{ $currentInfo['color'] }}-900 dark:text-{{ $currentInfo['color'] }}-100 mb-1">
                            {{ $currentInfo['title'] }}
                        </h4>
                        <p class="text-sm text-{{ $currentInfo['color'] }}-700 dark:text-{{ $currentInfo['color'] }}-300">
                            {{ $currentInfo['description'] }}
                        </p>
                        <p class="mt-1 text-sm font-medium text-{{ $currentInfo['color'] }}-800 dark:text-{{ $currentInfo['color'] }}-200">
                            {{ $currentInfo['hint'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Original Table -->
    {{ $this->table }}
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateTabLine() {
            const activeTab = '{{ $activeTab }}';
            const tabButtons = document.querySelectorAll('.fi-tabs-item');
            const line = document.querySelector('.tab-line');
            const dot = document.querySelector('.tab-dot');
            
            if (!line || !dot) return;
            
            // Finde den aktiven Tab Button
            let activeButton = null;
            tabButtons.forEach(button => {
                // Prüfe verschiedene Möglichkeiten, wie der Tab-Wert gespeichert sein könnte
                const wireClick = button.getAttribute('wire:click');
                const isActive = button.classList.contains('fi-active') || 
                               button.getAttribute('aria-selected') === 'true' ||
                               (wireClick && wireClick.includes("'" + activeTab + "'"));
                
                if (isActive || (wireClick && wireClick.includes(activeTab))) {
                    activeButton = button;
                }
            });
            
            if (activeButton) {
                const buttonRect = activeButton.getBoundingClientRect();
                const containerRect = activeButton.closest('.fi-tabs')?.getBoundingClientRect();
                
                if (containerRect) {
                    const leftOffset = buttonRect.left - containerRect.left + (buttonRect.width / 2);
                    line.style.left = leftOffset + 'px';
                    dot.style.left = leftOffset + 'px';
                }
            }
        }
        
        // Initial positionieren
        setTimeout(updateTabLine, 100);
        
        // Bei Tab-Klick aktualisieren
        document.addEventListener('click', function(e) {
            if (e.target.closest('.fi-tabs-item')) {
                setTimeout(updateTabLine, 300);
            }
        });
        
        // Bei Resize aktualisieren
        window.addEventListener('resize', updateTabLine);
    });
    </script>
</x-filament-panels::page>