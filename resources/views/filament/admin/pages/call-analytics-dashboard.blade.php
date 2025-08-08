<x-filament-panels::page>
    {{-- Header Controls --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        {{-- Date Range Selector --}}
        <div class="flex items-center gap-2">
            <select wire:model.live="dateRange" 
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                <option value="today">Heute</option>
                <option value="yesterday">Gestern</option>
                <option value="7d">Letzte 7 Tage</option>
                <option value="30d">Letzte 30 Tage</option>
                <option value="90d">Letzte 90 Tage</option>
                <option value="this_month">Dieser Monat</option>
                <option value="last_month">Letzter Monat</option>
                <option value="this_year">Dieses Jahr</option>
                <option value="custom">Benutzerdefiniert</option>
            </select>
            
            @if($dateRange === 'custom')
                <input type="date" wire:model.live="customFrom" 
                       class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                <span class="text-gray-500">bis</span>
                <input type="date" wire:model.live="customTo"
                       class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
            @endif
        </div>
        
        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            {{-- Compare Mode Toggle --}}
            <button wire:click="$toggle('compareMode')"
                    class="px-3 py-1.5 text-sm rounded-lg {{ $compareMode ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                <x-heroicon-m-arrows-right-left class="w-4 h-4 inline mr-1"/>
                Vergleichen
            </button>
            
            {{-- Auto Refresh Toggle --}}
            <button wire:click="$toggle('autoRefresh')"
                    class="px-3 py-1.5 text-sm rounded-lg {{ $autoRefresh ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                <x-heroicon-m-arrow-path class="w-4 h-4 inline mr-1 {{ $autoRefresh ? 'animate-spin' : '' }}"/>
                Auto-Refresh
            </button>
            
            {{-- Export Button --}}
            <button wire:click="exportAnalytics"
                    class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <x-heroicon-m-arrow-down-tray class="w-4 h-4 inline mr-1"/>
                Export
            </button>
        </div>
    </div>
    
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        @foreach($this->kpiMetrics as $key => $metric)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="p-2 bg-{{ $metric['color'] }}-100 dark:bg-{{ $metric['color'] }}-900/30 rounded-lg">
                        <x-dynamic-component :component="'heroicon-o-' . $metric['icon']" 
                                           class="w-5 h-5 text-{{ $metric['color'] }}-600 dark:text-{{ $metric['color'] }}-400"/>
                    </div>
                    @if(isset($metric['change']) && $metric['change'] !== null)
                        <span class="text-xs px-2 py-1 rounded-full {{ $metric['change'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $metric['change'] > 0 ? '+' : '' }}{{ $metric['change'] }}%
                        </span>
                    @endif
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $metric['prefix'] ?? '' }}{{ number_format($metric['value'], 0, ',', '.') }}{{ $metric['suffix'] ?? '' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ str_replace('_', ' ', ucfirst($key)) }}
                    @if(isset($metric['previous']))
                        <span class="text-gray-400">
                            (Vorher: {{ $metric['prefix'] ?? '' }}{{ number_format($metric['previous'], 0, ',', '.') }}{{ $metric['suffix'] ?? '' }})
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Main Charts Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Call Volume Chart --}}
        @if($showCallVolume)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufvolumen</h3>
                    <button wire:click="$toggle('showCallVolume')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="callVolumeChart"></canvas>
                </div>
            </div>
        @endif
        
        {{-- Conversion Funnel --}}
        @if($showConversionRate)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Conversion Funnel</h3>
                    <button wire:click="$toggle('showConversionRate')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="space-y-3">
                    @foreach($this->conversionFunnelData['stages'] as $stage)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $stage['name'] }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stage['value'], 0, ',', '.') }} ({{ $stage['percentage'] }}%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                                     style="width: {{ $stage['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        {{-- Duration Distribution --}}
        @if($showDurationAnalysis)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufdauer-Verteilung</h3>
                    <button wire:click="$toggle('showDurationAnalysis')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="durationChart"></canvas>
                </div>
            </div>
        @endif
        
        {{-- Sentiment Analysis --}}
        @if($showSentimentAnalysis)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stimmungsanalyse</h3>
                    <button wire:click="$toggle('showSentimentAnalysis')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="sentimentChart"></canvas>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Full Width Charts --}}
    <div class="space-y-6 mb-6">
        {{-- Cost Analysis --}}
        @if($showCostAnalysis)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kostenanalyse</h3>
                    <button wire:click="$toggle('showCostAnalysis')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="h-64">
                    <canvas id="costChart"></canvas>
                </div>
            </div>
        @endif
        
        {{-- Heatmap --}}
        @if($showHeatmap)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitäts-Heatmap (Wochentag/Stunde)</h3>
                    <button wire:click="$toggle('showHeatmap')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="w-5 h-5"/>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <div class="min-w-[800px]">
                        <div class="flex">
                            <div class="w-12"></div>
                            @foreach($this->heatmapData['hours'] as $hour)
                                <div class="w-8 text-center text-xs text-gray-500">{{ $hour }}</div>
                            @endforeach
                        </div>
                        @foreach($this->heatmapData['days'] as $dayIndex => $day)
                            <div class="flex items-center">
                                <div class="w-12 text-xs text-gray-500">{{ $day }}</div>
                                @foreach($this->heatmapData['hours'] as $hour)
                                    @php
                                        $cellData = collect($this->heatmapData['data'])->firstWhere(function($item) use ($dayIndex, $hour) {
                                            return $item['y'] == $dayIndex && $item['x'] == $hour;
                                        });
                                        $value = $cellData['value'] ?? 0;
                                        $conversion = $cellData['conversion'] ?? 0;
                                        $intensity = $value > 0 ? min(100, ($value / max(array_column($this->heatmapData['data'], 'value'))) * 100) : 0;
                                    @endphp
                                    <div class="w-8 h-8 border border-gray-200 dark:border-gray-700 relative group cursor-pointer"
                                         style="background-color: rgba(99, 102, 241, {{ $intensity / 100 }})">
                                        @if($value > 0)
                                            <div class="absolute inset-0 flex items-center justify-center text-xs font-medium
                                                {{ $intensity > 50 ? 'text-white' : 'text-gray-700' }}">
                                                {{ $value }}
                                            </div>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block
                                                    bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                                                {{ $value }} Anrufe<br>
                                                {{ $conversion }}% Conversion
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-center gap-4 text-xs text-gray-500">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-200"></div>
                        <span>Keine Anrufe</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4" style="background-color: rgba(99, 102, 241, 0.3)"></div>
                        <span>Wenige Anrufe</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4" style="background-color: rgba(99, 102, 241, 0.6)"></div>
                        <span>Moderate Aktivität</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4" style="background-color: rgba(99, 102, 241, 1)"></div>
                        <span>Hohe Aktivität</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Top Performers --}}
    @if($showTopPerformers)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Customers --}}
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Anrufer</h3>
                <div class="space-y-3">
                    @foreach($this->topPerformersData['customers'] as $index => $customer)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $customer['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $customer['phone'] }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $customer['calls'] }}</div>
                                <div class="text-xs text-gray-500">Anrufe</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Best Conversion Days --}}
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Beste Conversion-Tage</h3>
                <div class="space-y-3">
                    @foreach($this->topPerformersData['best_days'] as $index => $day)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-sm font-medium text-green-600 dark:text-green-400">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $day['date'] }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $day['calls'] }} Anrufe, {{ $day['appointments'] }} Termine
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-green-600 dark:text-green-400">{{ $day['conversion'] }}%</div>
                                <div class="text-xs text-gray-500">Conversion</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    
    {{-- Push Scripts for Chart.js --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize charts
                initializeCharts();
                
                // Listen for refresh event
                Livewire.on('refreshCharts', () => {
                    initializeCharts();
                });
                
                // Auto refresh if enabled
                @if($autoRefresh)
                    setInterval(() => {
                        @this.call('$refresh');
                        initializeCharts();
                    }, {{ $refreshInterval * 1000 }});
                @endif
            });
            
            function initializeCharts() {
                // Call Volume Chart
                if (document.getElementById('callVolumeChart')) {
                    const callVolumeCtx = document.getElementById('callVolumeChart').getContext('2d');
                    new Chart(callVolumeCtx, {
                        type: 'line',
                        data: @json($this->callVolumeData),
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                
                // Duration Chart
                if (document.getElementById('durationChart')) {
                    const durationCtx = document.getElementById('durationChart').getContext('2d');
                    new Chart(durationCtx, {
                        type: 'bar',
                        data: @json($this->durationDistributionData),
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                
                // Sentiment Chart
                if (document.getElementById('sentimentChart')) {
                    const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
                    new Chart(sentimentCtx, {
                        type: 'doughnut',
                        data: @json($this->sentimentData),
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
                
                // Cost Chart
                if (document.getElementById('costChart')) {
                    const costCtx = document.getElementById('costChart').getContext('2d');
                    new Chart(costCtx, {
                        type: 'line',
                        data: @json($this->costAnalysisData),
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: 'Gesamtkosten (€)'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Kosten pro Einheit (€)'
                                    },
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            }
                        }
                    });
                }
            }
        </script>
    @endpush
</x-filament-panels::page>