<div class="fi-wi-stats-overview-card relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                Agent Performance Dashboard
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ML-basierte Leistungsanalyse und Trends
            </p>
        </div>
        
        {{-- Controls --}}
        <div class="flex items-center gap-4">
            {{-- Agent Selector --}}
            <select 
                wire:model.live="agentId" 
                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"
            >
                @foreach($availableAgents as $agent)
                    <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
                @endforeach
            </select>
            
            {{-- Date Range Selector --}}
            <select 
                wire:model.live="dateRange" 
                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="7days">Letzte 7 Tage</option>
                <option value="30days">Letzte 30 Tage</option>
                <option value="90days">Letzte 90 Tage</option>
            </select>
        </div>
    </div>
    
    {{-- Performance Score Card --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Overall Score --}}
        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary-600 dark:text-primary-400">Performance Score</p>
                    <p class="text-3xl font-bold text-primary-900 dark:text-primary-100">
                        {{ $performanceScore }}/100
                    </p>
                </div>
                <div class="text-4xl">
                    @if($performanceScore >= 80)
                        🌟
                    @elseif($performanceScore >= 60)
                        ⭐
                    @else
                        📊
                    @endif
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                @if($scoreTrend > 0)
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-green-600 dark:text-green-400">+{{ $scoreTrend }} Punkte</span>
                @elseif($scoreTrend < 0)
                    <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-red-600 dark:text-red-400">{{ $scoreTrend }} Punkte</span>
                @else
                    <span class="text-gray-500">Keine Änderung</span>
                @endif
            </div>
        </div>
        
        {{-- Latest Metrics Summary --}}
        @if($metrics->last())
            @php $latest = $metrics->last(); @endphp
            
            {{-- Conversion Rate --}}
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <p class="text-sm text-green-600 dark:text-green-400">Konversionsrate</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                    {{ number_format($latest->conversion_rate, 1) }}%
                </p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                    {{ $latest->converted_calls }} von {{ $latest->total_calls }} Anrufen
                </p>
            </div>
            
            {{-- Sentiment Distribution --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <p class="text-sm text-blue-600 dark:text-blue-400">Stimmungsverteilung</p>
                <div class="flex items-center gap-2 mt-2">
                    <div class="flex-1">
                        <div class="flex h-6 rounded-full overflow-hidden">
                            @php $dist = $latest->sentiment_distribution; @endphp
                            <div class="bg-green-500" style="width: {{ $dist['positive'] }}%" title="Positiv: {{ $dist['positive'] }}%"></div>
                            <div class="bg-gray-400" style="width: {{ $dist['neutral'] }}%" title="Neutral: {{ $dist['neutral'] }}%"></div>
                            <div class="bg-red-500" style="width: {{ $dist['negative'] }}%" title="Negativ: {{ $dist['negative'] }}%"></div>
                        </div>
                    </div>
                    <span class="text-2xl">
                        @if($dist['positive'] > 60)
                            😊
                        @elseif($dist['negative'] > 40)
                            😟
                        @else
                            😐
                        @endif
                    </span>
                </div>
            </div>
            
            {{-- Average Duration --}}
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <p class="text-sm text-purple-600 dark:text-purple-400">Ø Anrufdauer</p>
                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                    {{ $latest->avg_duration_minutes }} Min
                </p>
                <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                    {{ $latest->total_calls }} Anrufe gesamt
                </p>
            </div>
        @endif
    </div>
    
    {{-- Performance Chart --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
            Performance Trend
        </h3>
        <div class="h-64">
            <canvas 
                x-data="performanceChart(@js($chartData))" 
                x-init="initChart()"
                x-ref="canvas"
            ></canvas>
        </div>
    </div>
    
    {{-- Correlation Analysis --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 ring-1 ring-gray-200 dark:ring-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Korrelationsanalyse
            </h3>
            <div class="space-y-3">
                @foreach($correlations as $key => $correlation)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $correlation['description'] }}
                        </span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $percentage = abs($correlation['value']) * 100;
                                    $color = $correlation['value'] > 0 ? 'bg-green-500' : 'bg-red-500';
                                @endphp
                                <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400">
                                {{ number_format($correlation['value'], 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Top Performers --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 ring-1 ring-gray-200 dark:ring-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Top Performer
            </h3>
            <div class="space-y-2">
                @foreach($topAgents as $index => $agent)
                    <div class="flex items-center justify-between py-1">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">
                                @if($index === 0) 🥇
                                @elseif($index === 1) 🥈
                                @elseif($index === 2) 🥉
                                @else {{ $index + 1 }}.
                                @endif
                            </span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Agent {{ substr($agent['agent_id'], -8) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500">Score: {{ $agent['score'] }}</span>
                            <span class="text-gray-500">Conv: {{ $agent['conversion'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    {{-- Insights --}}
    @if($metrics->count() > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                Insights & Empfehlungen
            </h3>
            <ul class="text-sm text-blue-700 dark:text-blue-200 space-y-1">
                @php
                    $avgSentiment = $metrics->avg('avg_sentiment_score');
                    $avgConversion = $metrics->avg('conversion_rate');
                @endphp
                
                @if($avgSentiment > 0.3)
                    <li>✅ Überdurchschnittlich positive Kundenstimmung ({{ number_format(($avgSentiment + 1) * 50, 0) }}/100)</li>
                @elseif($avgSentiment < -0.3)
                    <li>⚠️ Negative Kundenstimmung erfordert Aufmerksamkeit</li>
                @endif
                
                @if($avgConversion > 30)
                    <li>🎯 Exzellente Konversionsrate von {{ number_format($avgConversion, 1) }}%</li>
                @elseif($avgConversion < 10)
                    <li>📈 Konversionsrate kann verbessert werden (aktuell {{ number_format($avgConversion, 1) }}%)</li>
                @endif
                
                @if($correlations['sentiment_conversion']['value'] > 0.5)
                    <li>🔗 Starke positive Korrelation zwischen Stimmung und Konversion</li>
                @endif
            </ul>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function performanceChart(chartData) {
    return {
        chart: null,
        
        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            
            this.chart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' Anrufe';
                                }
                            }
                        },
                    }
                }
            });
        }
    }
}
</script>
@endpush