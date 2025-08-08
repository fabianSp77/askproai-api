<div class="space-y-6">
    <!-- Performance KPI Dashboard -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                Performance KPI Übersicht
            </h3>
            
            <!-- KPI Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Performance Score -->
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-24 h-24">
                        <svg class="transform -rotate-90 w-24 h-24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="transparent" 
                                class="text-gray-200 dark:text-gray-700" transform="translate(36,36)" />
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="transparent" 
                                stroke-dasharray="{{ 2 * pi() * 10 }}" 
                                stroke-dashoffset="{{ 2 * pi() * 10 * (1 - ($kpis['performance_score'] ?? 0) / 100) }}"
                                class="text-blue-500" transform="translate(36,36)" />
                        </svg>
                        <div class="absolute text-xl font-bold text-gray-900 dark:text-white">
                            {{ round($kpis['performance_score'] ?? 0) }}
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Performance Score</div>
                        <div class="text-xs text-gray-500">{{ $kpis['performance_rating'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <!-- Answer Rate Gauge -->
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-green-600">
                        <div class="text-lg font-bold text-white">{{ round($kpis['answer_rate'] ?? 0) }}%</div>
                    </div>
                    <div class="mt-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Annahmequote</div>
                        <div class="text-xs text-gray-500">
                            @php
                                $rate = $kpis['answer_rate'] ?? 0;
                                echo $rate >= 90 ? 'Exzellent' : ($rate >= 80 ? 'Gut' : 'Verbesserung nötig');
                            @endphp
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate Gauge -->
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-purple-400 to-purple-600">
                        <div class="text-lg font-bold text-white">{{ round($kpis['conversion_rate'] ?? 0) }}%</div>
                    </div>
                    <div class="mt-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Konversionsrate</div>
                        <div class="text-xs text-gray-500">
                            @php
                                $conv = $kpis['conversion_rate'] ?? 0;
                                echo $conv >= 25 ? 'Überdurchschnittlich' : ($conv >= 15 ? 'Durchschnittlich' : 'Unterdurchschnittlich');
                            @endphp
                        </div>
                    </div>
                </div>

                <!-- Customer Satisfaction -->
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600">
                        <div class="text-lg font-bold text-white">{{ number_format($kpis['avg_sentiment_score'] ?? 0, 1) }}</div>
                    </div>
                    <div class="mt-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Kundenzufriedenheit</div>
                        <div class="text-xs text-gray-500">von 5.0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Benchmark Comparison -->
    @if(isset($data['benchmarks']))
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
                Benchmark Vergleich
            </h3>
            
            <div class="space-y-4">
                @foreach($data['benchmarks'] as $metric => $benchmark)
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $metric) }}
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $benchmark['value'] }}{{ in_array($metric, ['answer_rate', 'conversion_rate']) ? '%' : '' }}
                            </span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-2">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Schlecht</span>
                                <span>Durchschnitt</span>
                                <span>Exzellent</span>
                            </div>
                            <div class="mt-1 relative">
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                    <!-- Poor to Good range -->
                                    <div class="bg-red-400 w-1/3"></div>
                                    <div class="bg-yellow-400 w-1/3"></div>
                                    <div class="bg-green-400 w-1/3"></div>
                                </div>
                                
                                <!-- Current position indicator -->
                                @php
                                    $poor = $benchmark['benchmark']['poor'] ?? 0;
                                    $good = $benchmark['benchmark']['good'] ?? 0;
                                    $excellent = $benchmark['benchmark']['excellent'] ?? 0;
                                    $value = $benchmark['value'];
                                    
                                    if ($metric === 'cost_per_call') {
                                        // Reverse logic for cost
                                        $position = $value <= $excellent ? 100 : 
                                                   ($value <= $good ? 66 : 
                                                   ($value <= $poor ? 33 : 0));
                                    } else {
                                        $position = $value >= $excellent ? 100 : 
                                                   ($value >= $good ? 66 : 
                                                   ($value >= $poor ? 33 : 0));
                                    }
                                @endphp
                                <div class="absolute top-0 h-2 w-1 bg-gray-800 rounded" 
                                     style="left: {{ min(100, max(0, $position)) }}%"></div>
                            </div>
                        </div>
                        
                        <div class="mt-2 text-xs">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $benchmark['rating'] === 'excellent' ? 'bg-green-100 text-green-800' : 
                                   ($benchmark['rating'] === 'good' ? 'bg-blue-100 text-blue-800' : 
                                   ($benchmark['rating'] === 'average' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($benchmark['rating']) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Detailed KPIs Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Call Efficiency Metrics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    📞 Anruf-Effizienz
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Gesamtanrufe</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($kpis['total_calls'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Erfolgreiche Anrufe</span>
                        <span class="text-lg font-semibold text-green-600">{{ number_format($kpis['successful_calls'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Verpasste Anrufe</span>
                        <span class="text-lg font-semibold text-red-600">{{ number_format($kpis['missed_calls'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ø Gesprächsdauer</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($kpis['avg_call_duration'] ?? 0, 1) }}min</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ø Latenz</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($kpis['avg_response_latency_ms'] ?? 0) }}ms</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Impact Metrics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    💰 Business Impact
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Umsatz generiert</span>
                        <span class="text-lg font-semibold text-green-600">€{{ number_format($kpis['revenue_generated'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Gesamtkosten</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">€{{ number_format($kpis['total_cost'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Kosten/Anruf</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">€{{ number_format($kpis['cost_per_call'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Kosten/Konversion</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">€{{ number_format($kpis['cost_per_conversion'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ROI</span>
                        <span class="text-lg font-semibold {{ ($kpis['roi_percentage'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($kpis['roi_percentage'] ?? 0, 1) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Interaction Quality -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                👥 Kundeninteraktion Qualität
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($kpis['data_capture_rate'] ?? 0, 1) }}%</div>
                    <div class="text-sm text-gray-500">Datenerfassungsrate</div>
                    <div class="mt-2 text-xs text-gray-400">
                        Prozentsatz der Anrufe, bei denen Kundendaten erfasst wurden
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ number_format($kpis['positive_sentiment_rate'] ?? 0, 1) }}%</div>
                    <div class="text-sm text-gray-500">Positive Stimmung</div>
                    <div class="mt-2 text-xs text-gray-400">
                        Anteil der Anrufe mit positiver Kundenstimmung
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-red-600">{{ number_format($kpis['negative_sentiment_rate'] ?? 0, 1) }}%</div>
                    <div class="text-sm text-gray-500">Negative Stimmung</div>
                    <div class="mt-2 text-xs text-gray-400">
                        Anteil der Anrufe mit negativer Kundenstimmung
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Improvement Suggestions -->
    @if(isset($data['improvement_areas']) && !empty($data['improvement_areas']))
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-blue-900 dark:text-blue-100 mb-4">
                🎯 Verbesserungsvorschläge
            </h3>
            <div class="space-y-4">
                @foreach($data['improvement_areas'] as $area)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $area['area'] }}</h4>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $area['current'] }}{{ $area['area'] === 'Kundenzufriedenheit' ? '/5' : '%' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Ziel: {{ $area['target'] }}{{ $area['area'] === 'Kundenzufriedenheit' ? '/5' : '%' }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Empfohlene Maßnahmen:</h5>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            @foreach($area['actions'] as $action)
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                                {{ $action }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Performance Trends -->
    @if(isset($data['performance_trends']) && !empty($data['performance_trends']))
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                📈 Performance Trends (7 Tage)
            </h3>
            <div class="chart-container">
                <canvas id="performanceTrendChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Performance Trend Chart
        const performanceTrendCtx = document.getElementById('performanceTrendChart');
        if (performanceTrendCtx) {
            new Chart(performanceTrendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(collect($data['performance_trends'] ?? [])->pluck('date')) !!},
                    datasets: [{
                        label: 'Anrufe',
                        data: {!! json_encode(collect($data['performance_trends'] ?? [])->pluck('calls')) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y'
                    }, {
                        label: 'Konversionsrate (%)',
                        data: {!! json_encode(collect($data['performance_trends'] ?? [])->pluck('conversion_rate')) !!},
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }, {
                        label: 'Zufriedenheit (von 5)',
                        data: {!! json_encode(collect($data['performance_trends'] ?? [])->pluck('satisfaction')) !!},
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Anzahl Anrufe'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Prozent / Bewertung'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }
    </script>
    @endif
</div>