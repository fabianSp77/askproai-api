<div class="space-y-6">
    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Calls -->
        <div class="metric-card">
            <div class="metric-card-header">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="metric-label">Gesamtanrufe</dt>
                            <dd class="flex items-baseline">
                                <div class="metric-value">{{ number_format($data['summary']['total_calls'] ?? 0) }}</div>
                                @if(isset($data['trends']['calls_trend']))
                                <div class="ml-2 flex items-baseline text-sm font-semibold 
                                    {{ $data['trends']['calls_trend']['status'] === 'positive' ? 'text-green-600' : 
                                       ($data['trends']['calls_trend']['status'] === 'negative' ? 'text-red-600' : 'text-gray-500') }}">
                                    @if($data['trends']['calls_trend']['direction'] === 'up')
                                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                    @elseif($data['trends']['calls_trend']['direction'] === 'down')
                                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    @endif
                                    {{ $data['trends']['calls_trend']['percentage'] }}%
                                </div>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Answer Rate -->
        <div class="metric-card">
            <div class="metric-card-header">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="metric-label">Annahmequote</dt>
                            <dd class="flex items-baseline">
                                <div class="metric-value">{{ number_format($data['summary']['answer_rate'] ?? 0, 1) }}%</div>
                                <div class="ml-2 text-xs text-gray-500">
                                    @php
                                        $rate = $data['summary']['answer_rate'] ?? 0;
                                        $rating = $rate >= 90 ? 'Exzellent' : ($rate >= 80 ? 'Gut' : ($rate >= 70 ? 'Durchschnittlich' : 'Verbesserung nötig'));
                                        $color = $rate >= 90 ? 'text-green-600' : ($rate >= 80 ? 'text-blue-600' : ($rate >= 70 ? 'text-yellow-600' : 'text-red-600'));
                                    @endphp
                                    <span class="{{ $color }}">{{ $rating }}</span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="metric-card">
            <div class="metric-card-header">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="metric-label">Konversionsrate</dt>
                            <dd class="flex items-baseline">
                                <div class="metric-value">{{ number_format($data['summary']['conversion_rate'] ?? 0, 1) }}%</div>
                                @if(isset($data['trends']['conversion_trend']))
                                <div class="ml-2 flex items-baseline text-sm font-semibold 
                                    {{ $data['trends']['conversion_trend']['status'] === 'positive' ? 'text-green-600' : 
                                       ($data['trends']['conversion_trend']['status'] === 'negative' ? 'text-red-600' : 'text-gray-500') }}">
                                    @if($data['trends']['conversion_trend']['direction'] === 'up')
                                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                    @elseif($data['trends']['conversion_trend']['direction'] === 'down')
                                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    @endif
                                    {{ $data['trends']['conversion_trend']['percentage'] }}%
                                </div>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROI -->
        <div class="metric-card">
            <div class="metric-card-header">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="metric-label">ROI</dt>
                            <dd class="flex items-baseline">
                                <div class="metric-value 
                                    {{ ($data['summary']['roi_percentage'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($data['summary']['roi_percentage'] ?? 0, 1) }}%
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Rating and Quick Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance Rating -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Performance Bewertung</h3>
                <div class="mt-4">
                    <div class="text-center">
                        <div class="text-4xl font-bold 
                            {{ in_array($data['summary']['performance_rating'], ['Exzellent', 'Sehr gut']) ? 'text-green-600' : 
                               (in_array($data['summary']['performance_rating'], ['Gut', 'Durchschnittlich']) ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $data['summary']['performance_rating'] }}
                        </div>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Basierend auf Annahmequote, Konversion und Kundenzufriedenheit
                        </div>
                    </div>
                    
                    <!-- Performance Indicators -->
                    <div class="mt-6 grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['summary']['customer_satisfaction'] ?? 0, 1) }}/5
                            </div>
                            <div class="text-xs text-gray-500">Zufriedenheit</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['summary']['answer_rate'] ?? 0, 0) }}%
                            </div>
                            <div class="text-xs text-gray-500">Annahmequote</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['summary']['conversion_rate'] ?? 0, 0) }}%
                            </div>
                            <div class="text-xs text-gray-500">Konversion</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Insights -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Schnelle Erkenntnisse</h3>
                <div class="mt-4 space-y-3">
                    @forelse(($data['quick_insights'] ?? []) as $insight)
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($insight['type'] === 'success')
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @elseif($insight['type'] === 'warning')
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $insight['message'] }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                        Keine besonderen Erkenntnisse für den ausgewählten Zeitraum.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends Chart -->
    @if(isset($data['performance_trends']) && !empty($data['performance_trends']))
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Performance Trends (letzte 7 Tage)</h3>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    @endif

    <!-- Additional Metrics Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Data Capture Rate -->
        <div class="metric-card">
            <div class="metric-card-header">
                <dl>
                    <dt class="metric-label">Datenerfassung</dt>
                    <dd class="metric-value">{{ number_format($data['summary']['data_capture_rate'] ?? 0, 1) }}%</dd>
                </dl>
            </div>
        </div>

        <!-- Average Call Duration -->
        <div class="metric-card">
            <div class="metric-card-header">
                <dl>
                    <dt class="metric-label">Ø Gesprächsdauer</dt>
                    <dd class="metric-value">{{ number_format($data['summary']['avg_call_duration'] ?? 0, 1) }}min</dd>
                </dl>
            </div>
        </div>

        <!-- Cost per Call -->
        <div class="metric-card">
            <div class="metric-card-header">
                <dl>
                    <dt class="metric-label">Kosten/Anruf</dt>
                    <dd class="metric-value">€{{ number_format($data['summary']['cost_per_call'] ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>

        <!-- Revenue Generated -->
        <div class="metric-card">
            <div class="metric-card-header">
                <dl>
                    <dt class="metric-label">Umsatz generiert</dt>
                    <dd class="metric-value">€{{ number_format($data['summary']['revenue_generated'] ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Action Items -->
    @if(isset($data['improvement_areas']) && !empty($data['improvement_areas']))
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-4">
            📋 Empfohlene Maßnahmen
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($data['improvement_areas'] as $area)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">{{ $area['area'] }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Aktuell: {{ $area['current'] }}{{ $area['area'] === 'Kundenzufriedenheit' ? '/5' : '%' }} | 
                    Ziel: {{ $area['target'] }}{{ $area['area'] === 'Kundenzufriedenheit' ? '/5' : '%' }}
                </p>
                <ul class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    @foreach($area['actions'] as $action)
                    <li class="flex items-start">
                        <span class="mr-1">•</span>
                        <span>{{ $action }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>