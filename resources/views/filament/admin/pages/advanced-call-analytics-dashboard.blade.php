<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Advanced Call Analytics Dashboard
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->getSubheading() }}
                    </p>
                </div>
                <div class="flex space-x-2">
                    <button 
                        wire:click="refreshData"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Aktualisieren
                    </button>
                    <button 
                        wire:click="exportData"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <!-- Filters Form -->
            {{ $this->form }}
        </div>

        <!-- Performance Alerts -->
        @if($hasAlerts)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    Performance Alerts ({{ $performanceAlerts['total_alerts'] ?? 0 }} aktiv, {{ $criticalAlerts }} kritisch)
                </h3>
            </div>
            <div class="space-y-2">
                @foreach(($performanceAlerts['alerts'] ?? []) as $alert)
                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded p-3 border-l-4 
                    {{ $alert['severity'] === 'high' ? 'border-red-500' : ($alert['severity'] === 'medium' ? 'border-yellow-500' : 'border-blue-500') }}">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $alert['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $alert['message'] }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $alert['severity'] === 'high' ? 'bg-red-100 text-red-800' : ($alert['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ ucfirst($alert['severity']) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Main Content Based on Analysis Type -->
        @switch($analysisType)
            @case('overview')
                @include('filament.admin.pages.analytics.overview', ['data' => $dashboardData])
                @break
            @case('performance')
                @include('filament.admin.pages.analytics.performance', ['data' => $dashboardData, 'kpis' => $kpis])
                @break
            @case('patterns')
                @include('filament.admin.pages.analytics.patterns', ['data' => $dashboardData, 'patterns' => $patternAnalysis])
                @break
            @case('satisfaction')
                @include('filament.admin.pages.analytics.satisfaction', ['data' => $dashboardData, 'satisfaction' => $satisfactionMetrics])
                @break
            @case('funnel')
                @include('filament.admin.pages.analytics.funnel', ['data' => $dashboardData, 'funnel' => $funnelData])
                @break
            @case('comparative')
                @include('filament.admin.pages.analytics.comparative', ['data' => $dashboardData, 'comparative' => $comparativeAnalysis])
                @break
            @case('realtime')
                @include('filament.admin.pages.analytics.realtime', ['data' => $dashboardData, 'realtime' => $realTimeKPIs])
                @break
        @endswitch
    </div>

    <!-- JavaScript for Charts and Real-time Updates -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts based on analysis type
            @if($analysisType === 'overview' || $analysisType === 'performance')
                initializeOverviewCharts();
            @endif
            
            @if($analysisType === 'patterns')
                initializePatternCharts();
            @endif
            
            @if($analysisType === 'satisfaction')
                initializeSatisfactionCharts();
            @endif
            
            @if($analysisType === 'funnel')
                initializeFunnelChart();
            @endif
            
            @if($analysisType === 'realtime')
                initializeRealTimeCharts();
                setInterval(refreshRealTimeData, 30000); // Refresh every 30 seconds
            @endif
        });

        function initializeOverviewCharts() {
            // KPI Trend Chart
            @if(isset($dashboardData['trends']))
            const trendCtx = document.getElementById('trendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(collect($dashboardData['performance_trends'] ?? [])->pluck('date')) !!},
                        datasets: [{
                            label: 'Anrufe',
                            data: {!! json_encode(collect($dashboardData['performance_trends'] ?? [])->pluck('calls')) !!},
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        }, {
                            label: 'Konversionsrate (%)',
                            data: {!! json_encode(collect($dashboardData['performance_trends'] ?? [])->pluck('conversion_rate')) !!},
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }
            @endif
        }

        function initializePatternCharts() {
            // Hourly Pattern Chart
            @if(isset($patternAnalysis['hourly_pattern']))
            const hourlyCtx = document.getElementById('hourlyPatternChart');
            if (hourlyCtx) {
                new Chart(hourlyCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys({!! json_encode($patternAnalysis['hourly_pattern'] ?? []) !!}).map(h => h + ':00'),
                        datasets: [{
                            label: 'Anrufe pro Stunde',
                            data: Object.values({!! json_encode($patternAnalysis['hourly_pattern'] ?? []) !!}).map(d => d.count),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Anrufverteilung über den Tag'
                            }
                        }
                    }
                });
            }
            @endif

            // Daily Pattern Chart
            @if(isset($patternAnalysis['daily_pattern']))
            const dailyCtx = document.getElementById('dailyPatternChart');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys({!! json_encode($patternAnalysis['daily_pattern'] ?? []) !!}),
                        datasets: [{
                            data: Object.values({!! json_encode($patternAnalysis['daily_pattern'] ?? []) !!}),
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                                '#8B5CF6', '#06B6D4', '#84CC16'
                            ],
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Anrufverteilung nach Wochentagen'
                            }
                        }
                    }
                });
            }
            @endif
        }

        function initializeSatisfactionCharts() {
            // Sentiment Distribution
            @if(isset($satisfactionMetrics['sentiment_distribution']))
            const sentimentCtx = document.getElementById('sentimentChart');
            if (sentimentCtx) {
                new Chart(sentimentCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Positiv', 'Neutral', 'Negativ'],
                        datasets: [{
                            data: [
                                {{ $satisfactionMetrics['sentiment_distribution']['positive'] ?? 0 }},
                                {{ $satisfactionMetrics['sentiment_distribution']['neutral'] ?? 0 }},
                                {{ $satisfactionMetrics['sentiment_distribution']['negative'] ?? 0 }}
                            ],
                            backgroundColor: ['#10B981', '#6B7280', '#EF4444'],
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Kundenstimmung Verteilung'
                            }
                        }
                    }
                });
            }
            @endif
        }

        function initializeFunnelChart() {
            // Conversion Funnel
            @if(isset($funnelData['funnel_stages']))
            const funnelCtx = document.getElementById('funnelChart');
            if (funnelCtx) {
                new Chart(funnelCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(array_keys($funnelData['funnel_stages'] ?? [])) !!},
                        datasets: [{
                            label: 'Anzahl',
                            data: {!! json_encode(array_values($funnelData['funnel_stages'] ?? [])) !!},
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', 
                                '#EF4444', '#8B5CF6', '#06B6D4'
                            ],
                        }]
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: {
                            title: {
                                display: true,
                                text: 'Conversion Funnel'
                            }
                        }
                    }
                });
            }
            @endif
        }

        function initializeRealTimeCharts() {
            // Real-time activity chart would go here
            console.log('Real-time charts initialized');
        }

        function refreshRealTimeData() {
            @if($analysisType === 'realtime')
            Livewire.dispatch('refreshData');
            @endif
        }

        // Listen for data refresh events
        document.addEventListener('livewire:init', () => {
            Livewire.on('data-refreshed', (event) => {
                // Reinitialize charts after data refresh
                setTimeout(() => {
                    location.reload(); // Simple approach - in production, update charts dynamically
                }, 100);
            });
        });
    </script>

    <!-- Custom Styles -->
    <style>
        .metric-card {
            @apply bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg;
        }
        
        .metric-card-header {
            @apply px-4 py-5 sm:p-6;
        }
        
        .metric-value {
            @apply text-2xl font-semibold text-gray-900 dark:text-white;
        }
        
        .metric-label {
            @apply text-sm font-medium text-gray-500 dark:text-gray-400 truncate;
        }
        
        .metric-change {
            @apply text-sm font-medium;
        }
        
        .metric-change.positive {
            @apply text-green-600 dark:text-green-400;
        }
        
        .metric-change.negative {
            @apply text-red-600 dark:text-red-400;
        }
        
        .metric-change.neutral {
            @apply text-gray-500 dark:text-gray-400;
        }
        
        .chart-container {
            @apply bg-white dark:bg-gray-800 p-6 rounded-lg shadow;
            position: relative;
            height: 400px;
        }
    </style>
</x-filament-panels::page>