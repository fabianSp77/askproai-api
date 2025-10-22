<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Customer Intelligence
        </x-slot>

        <x-slot name="description">
            KI-gestützte Analyse und Empfehlungen
        </x-slot>

        <div class="space-y-6">
            {{-- Key Metrics Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Health Score --}}
                <div class="rounded-lg border-2 border-{{ $healthScore['color'] }}-200 dark:border-{{ $healthScore['color'] }}-800 p-4 bg-{{ $healthScore['color'] }}-50 dark:bg-{{ $healthScore['color'] }}-900/20">
                    <div class="text-sm font-medium text-{{ $healthScore['color'] }}-700 dark:text-{{ $healthScore['color'] }}-300 mb-2">
                        Gesundheit
                    </div>
                    <div class="flex items-end gap-3">
                        <div class="text-4xl font-bold text-{{ $healthScore['color'] }}-900 dark:text-{{ $healthScore['color'] }}-100">
                            {{ $healthScore['value'] }}
                        </div>
                        <div class="text-lg text-{{ $healthScore['color'] }}-600 dark:text-{{ $healthScore['color'] }}-400 mb-1">
                            /100
                        </div>
                    </div>
                    <div class="text-xs text-{{ $healthScore['color'] }}-600 dark:text-{{ $healthScore['color'] }}-400 mt-2">
                        {{ $healthScore['label'] }}
                    </div>
                    {{-- Progress bar --}}
                    <div class="mt-3 h-2 bg-{{ $healthScore['color'] }}-200 dark:bg-{{ $healthScore['color'] }}-800 rounded-full overflow-hidden">
                        <div class="h-full bg-{{ $healthScore['color'] }}-600 dark:bg-{{ $healthScore['color'] }}-400 transition-all duration-500"
                             style="width: {{ $healthScore['value'] }}%"></div>
                    </div>
                </div>

                {{-- Churn Risk --}}
                <div class="rounded-lg border-2 border-{{ $churnRisk['color'] }}-200 dark:border-{{ $churnRisk['color'] }}-800 p-4 bg-{{ $churnRisk['color'] }}-50 dark:bg-{{ $churnRisk['color'] }}-900/20">
                    <div class="text-sm font-medium text-{{ $churnRisk['color'] }}-700 dark:text-{{ $churnRisk['color'] }}-300 mb-2">
                        Abwanderungsrisiko
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="text-2xl">
                            @if($churnRisk['level'] === 'critical') 🔴
                            @elseif($churnRisk['level'] === 'high') 🟠
                            @elseif($churnRisk['level'] === 'medium') 🟡
                            @else 🟢
                            @endif
                        </div>
                        <div class="text-2xl font-bold text-{{ $churnRisk['color'] }}-900 dark:text-{{ $churnRisk['color'] }}-100">
                            {{ $churnRisk['label'] }}
                        </div>
                    </div>
                    <div class="text-xs text-{{ $churnRisk['color'] }}-600 dark:text-{{ $churnRisk['color'] }}-400 mt-2">
                        Risiko-Score: {{ $churnRisk['score'] }}/100
                    </div>
                </div>

                {{-- Value Score --}}
                <div class="rounded-lg border-2 border-{{ $valueScore['color'] }}-200 dark:border-{{ $valueScore['color'] }}-800 p-4 bg-{{ $valueScore['color'] }}-50 dark:bg-{{ $valueScore['color'] }}-900/20">
                    <div class="text-sm font-medium text-{{ $valueScore['color'] }}-700 dark:text-{{ $valueScore['color'] }}-300 mb-2">
                        Kundenwert
                    </div>
                    <div class="flex items-end gap-3">
                        <div class="text-4xl font-bold text-{{ $valueScore['color'] }}-900 dark:text-{{ $valueScore['color'] }}-100">
                            {{ $valueScore['value'] }}
                        </div>
                        <div class="text-lg text-{{ $valueScore['color'] }}-600 dark:text-{{ $valueScore['color'] }}-400 mb-1">
                            /100
                        </div>
                    </div>
                    <div class="text-xs text-{{ $valueScore['color'] }}-600 dark:text-{{ $valueScore['color'] }}-400 mt-2">
                        {{ $valueScore['label'] }}
                    </div>
                </div>

                {{-- Engagement Level --}}
                <div class="rounded-lg border-2 border-{{ $engagementLevel['color'] }}-200 dark:border-{{ $engagementLevel['color'] }}-800 p-4 bg-{{ $engagementLevel['color'] }}-50 dark:bg-{{ $engagementLevel['color'] }}-900/20">
                    <div class="text-sm font-medium text-{{ $engagementLevel['color'] }}-700 dark:text-{{ $engagementLevel['color'] }}-300 mb-2">
                        Engagement
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="text-2xl">
                            @if($engagementLevel['level'] === 'high') 🔥
                            @elseif($engagementLevel['level'] === 'medium') 💬
                            @else 💤
                            @endif
                        </div>
                        <div class="text-2xl font-bold text-{{ $engagementLevel['color'] }}-900 dark:text-{{ $engagementLevel['color'] }}-100">
                            {{ $engagementLevel['label'] }}
                        </div>
                    </div>
                    <div class="text-xs text-{{ $engagementLevel['color'] }}-600 dark:text-{{ $engagementLevel['color'] }}-400 mt-2">
                        Score: {{ $engagementLevel['score'] }}/100
                    </div>
                </div>
            </div>

            {{-- Next Best Action (Prominent) --}}
            <div class="rounded-lg border-2 border-primary-300 dark:border-primary-700 bg-primary-50 dark:bg-primary-900/20 p-6">
                <div class="flex items-start gap-4">
                    <div class="text-5xl">{{ $nextBestAction['icon'] }}</div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-lg font-bold text-primary-900 dark:text-primary-100">
                                Empfohlene Aktion
                            </h3>
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                                @if($nextBestAction['priority'] === 'critical') bg-danger-500 text-white
                                @elseif($nextBestAction['priority'] === 'high') bg-warning-500 text-white
                                @elseif($nextBestAction['priority'] === 'medium') bg-info-500 text-white
                                @else bg-gray-500 text-white
                                @endif">
                                {{ strtoupper($nextBestAction['priority']) }}
                            </span>
                        </div>
                        <div class="text-xl font-semibold text-primary-800 dark:text-primary-200 mb-2">
                            {{ $nextBestAction['action'] }}
                        </div>
                        <div class="text-sm text-primary-600 dark:text-primary-400">
                            {{ $nextBestAction['reason'] }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Insights Cards --}}
            @if(count($insights) > 0)
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    Erkenntnisse
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($insights as $insight)
                        <div class="rounded-lg border-2
                            @if($insight['type'] === 'danger') border-danger-200 dark:border-danger-800 bg-danger-50 dark:bg-danger-900/20
                            @elseif($insight['type'] === 'warning') border-warning-200 dark:border-warning-800 bg-warning-50 dark:bg-warning-900/20
                            @elseif($insight['type'] === 'success') border-success-200 dark:border-success-800 bg-success-50 dark:bg-success-900/20
                            @else border-info-200 dark:border-info-800 bg-info-50 dark:bg-info-900/20
                            @endif p-4">
                            <div class="flex items-start gap-3">
                                <div class="text-2xl">{{ $insight['icon'] }}</div>
                                <div class="flex-1">
                                    <div class="font-semibold
                                        @if($insight['type'] === 'danger') text-danger-900 dark:text-danger-100
                                        @elseif($insight['type'] === 'warning') text-warning-900 dark:text-warning-100
                                        @elseif($insight['type'] === 'success') text-success-900 dark:text-success-100
                                        @else text-info-900 dark:text-info-100
                                        @endif mb-1">
                                        {{ $insight['title'] }}
                                    </div>
                                    <div class="text-sm
                                        @if($insight['type'] === 'danger') text-danger-700 dark:text-danger-300
                                        @elseif($insight['type'] === 'warning') text-warning-700 dark:text-warning-300
                                        @elseif($insight['type'] === 'success') text-success-700 dark:text-success-300
                                        @else text-info-700 dark:text-info-300
                                        @endif">
                                        {{ $insight['message'] }}
                                    </div>
                                    @if($insight['action'])
                                        <div class="mt-2">
                                            <button class="text-xs font-medium
                                                @if($insight['type'] === 'danger') text-danger-800 dark:text-danger-200 hover:underline
                                                @elseif($insight['type'] === 'warning') text-warning-800 dark:text-warning-200 hover:underline
                                                @elseif($insight['type'] === 'success') text-success-800 dark:text-success-200 hover:underline
                                                @else text-info-800 dark:text-info-200 hover:underline
                                                @endif">
                                                {{ $insight['action'] }} →
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Explanation Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <details class="text-sm text-gray-500 dark:text-gray-400">
                    <summary class="cursor-pointer font-medium hover:text-gray-700 dark:hover:text-gray-300">
                        Wie werden diese Metriken berechnet?
                    </summary>
                    <div class="mt-3 space-y-2 text-xs">
                        <div><strong>Gesundheit:</strong> Basierend auf Aktivität, Conversion-Rate, und Aktualität der Interaktionen.</div>
                        <div><strong>Abwanderungsrisiko:</strong> Analysiert Inaktivität, fehlgeschlagene Buchungen, und Stornierungen.</div>
                        <div><strong>Kundenwert:</strong> Bewertet Umsatz, Terminfrequenz, und Engagement-Potenzial.</div>
                        <div><strong>Engagement:</strong> Misst Aktivitätsfrequenz und Reaktionsgeschwindigkeit der letzten 30 Tage.</div>
                    </div>
                </details>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
