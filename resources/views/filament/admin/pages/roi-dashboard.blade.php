<x-filament-panels::page>
    {{-- Live Update Indicator --}}
    @if($liveUpdate)
        <div wire:poll.{{ $updateInterval }}s="refreshData"></div>
    @endif
    
    {{-- Header with last update time --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Letzte Aktualisierung:</span>
            <span class="text-sm font-medium text-gray-700">{{ $lastUpdated }}</span>
            @if($liveUpdate)
                <span class="flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
            @endif
        </div>
    </div>
    
    {{-- Main ROI Card - Company-wide --}}
    <div class="mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br 
            @if($companyRoi['roi_status'] === 'excellent') from-green-500 to-green-600
            @elseif($companyRoi['roi_status'] === 'good') from-yellow-500 to-yellow-600
            @elseif($companyRoi['roi_status'] === 'break-even') from-orange-500 to-orange-600
            @else from-red-500 to-red-600
            @endif
            p-8 text-white shadow-2xl">
            
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="roi-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                            <circle cx="20" cy="20" r="1" fill="currentColor" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#roi-pattern)" />
                </svg>
            </div>
            
            <div class="relative">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-3xl font-bold">Unternehmens-ROI</h2>
                        <p class="mt-1 text-white/80">{{ $dateFrom }} bis {{ $dateTo }}</p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-baseline">
                            <span class="text-5xl font-bold">{{ number_format($companyRoi['roi'], 1) }}</span>
                            <span class="ml-1 text-2xl">%</span>
                        </div>
                        <p class="mt-1 text-sm text-white/80">
                            @if($companyRoi['roi_status'] === 'excellent') Exzellent
                            @elseif($companyRoi['roi_status'] === 'good') Gut
                            @elseif($companyRoi['roi_status'] === 'break-even') Break-Even
                            @else Negativ
                            @endif
                        </p>
                    </div>
                </div>
                
                {{-- Key Metrics Grid --}}
                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-white/10 p-4 backdrop-blur">
                        <p class="text-sm text-white/70">Umsatz</p>
                        <p class="text-2xl font-semibold">€{{ number_format($companyRoi['revenue'], 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4 backdrop-blur">
                        <p class="text-sm text-white/70">Kosten</p>
                        <p class="text-2xl font-semibold">€{{ number_format($companyRoi['cost'], 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4 backdrop-blur">
                        <p class="text-sm text-white/70">Gewinn</p>
                        <p class="text-2xl font-semibold">€{{ number_format($companyRoi['profit'], 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4 backdrop-blur">
                        <p class="text-sm text-white/70">Konversionsrate</p>
                        <p class="text-2xl font-semibold">{{ $companyRoi['conversion_rate'] }}%</p>
                    </div>
                </div>
                
                {{-- Benchmark Comparison --}}
                <div class="mt-6 rounded-lg bg-white/10 p-4 backdrop-blur">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-white/70">Branchendurchschnitt</span>
                        <span class="font-medium">{{ $benchmarks['industry_avg_roi'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 w-full rounded-full bg-white/20">
                        <div class="h-2 rounded-full bg-white" 
                             style="width: {{ min(($companyRoi['roi'] / $benchmarks['industry_avg_roi']) * 100, 100) }}%">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-white/70">
                        Sie sind im {{ $benchmarks['your_percentile'] }}. Perzentil Ihrer Branche
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Branch Comparison Section --}}
    <div class="mb-8">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Filialvergleich</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($branchComparison as $branch)
                <div class="relative rounded-xl border 
                    @if($branch['roi_status'] === 'excellent') border-green-200 bg-green-50
                    @elseif($branch['roi_status'] === 'good') border-yellow-200 bg-yellow-50
                    @elseif($branch['roi_status'] === 'break-even') border-orange-200 bg-orange-50
                    @else border-red-200 bg-red-50
                    @endif
                    p-6 shadow-sm transition-all hover:shadow-md">
                    
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $branch['name'] }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ $branch['appointments'] }} Termine</p>
                        </div>
                        <div class="text-right">
                            <div class="flex items-baseline">
                                <span class="text-2xl font-bold 
                                    @if($branch['roi_status'] === 'excellent') text-green-600
                                    @elseif($branch['roi_status'] === 'good') text-yellow-600
                                    @elseif($branch['roi_status'] === 'break-even') text-orange-600
                                    @else text-red-600
                                    @endif">
                                    {{ number_format($branch['roi'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Umsatz</span>
                            <span class="font-medium">€{{ number_format($branch['revenue'], 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Gewinn</span>
                            <span class="font-medium">€{{ number_format($branch['profit'], 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Ø Wert</span>
                            <span class="font-medium">€{{ number_format($branch['avg_value'], 0) }}</span>
                        </div>
                    </div>
                    
                    {{-- Mini Chart --}}
                    <div class="mt-4 h-12 w-full">
                        <canvas 
                            id="branch-chart-{{ $branch['id'] }}" 
                            class="h-full w-full"
                            wire:ignore
                        ></canvas>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    {{-- Hourly Heatmap --}}
    <div class="mb-8 rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Stündliche ROI-Heatmap</h3>
        <div class="overflow-x-auto">
            <div class="min-w-[600px]">
                {{-- Hour labels --}}
                <div class="mb-2 flex">
                    <div class="w-24"></div>
                    @for($hour = 0; $hour < 24; $hour++)
                        <div class="flex-1 text-center text-xs text-gray-500">{{ $hour }}</div>
                    @endfor
                </div>
                
                {{-- Heatmap grid --}}
                <div class="flex">
                    <div class="w-24 pr-4 text-right text-sm font-medium text-gray-700">ROI</div>
                    <div class="flex flex-1 gap-1">
                        @foreach($hourlyBreakdown as $hourData)
                            <div class="group relative flex-1">
                                <div class="aspect-square rounded 
                                    @if($hourData['roi'] >= 200) bg-green-600
                                    @elseif($hourData['roi'] >= 100) bg-green-500
                                    @elseif($hourData['roi'] >= 50) bg-yellow-500
                                    @elseif($hourData['roi'] >= 0) bg-orange-500
                                    @else bg-red-500
                                    @endif
                                    @if(!$hourData['is_business_hour']) opacity-60 @endif
                                    transition-all hover:ring-2 hover:ring-offset-2 hover:ring-gray-400">
                                </div>
                                
                                {{-- Tooltip --}}
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform opacity-0 transition-opacity group-hover:opacity-100">
                                    <div class="rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg">
                                        <p class="font-semibold">{{ $hourData['hour_label'] }}</p>
                                        <p>ROI: {{ $hourData['roi'] }}%</p>
                                        <p>Umsatz: €{{ number_format($hourData['revenue'], 0) }}</p>
                                        <p>Termine: {{ $hourData['appointments'] }}</p>
                                    </div>
                                    <div class="absolute left-1/2 top-full -mt-1 h-2 w-2 -translate-x-1/2 rotate-45 transform bg-gray-900"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- Legend --}}
                <div class="mt-4 flex items-center justify-center space-x-4 text-xs">
                    <div class="flex items-center">
                        <div class="h-3 w-3 rounded bg-green-600"></div>
                        <span class="ml-1 text-gray-600">> 200%</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-3 w-3 rounded bg-green-500"></div>
                        <span class="ml-1 text-gray-600">100-200%</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-3 w-3 rounded bg-yellow-500"></div>
                        <span class="ml-1 text-gray-600">50-100%</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-3 w-3 rounded bg-orange-500"></div>
                        <span class="ml-1 text-gray-600">0-50%</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-3 w-3 rounded bg-red-500"></div>
                        <span class="ml-1 text-gray-600">< 0%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Business Hours vs After Hours --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Business Hours Card --}}
        <div class="rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-blue-900">Geschäftszeiten</h4>
                    <p class="text-sm text-blue-700">8:00 - 18:00 Uhr</p>
                </div>
                <div class="rounded-full bg-blue-200 p-3">
                    <x-heroicon-o-sun class="h-6 w-6 text-blue-600" />
                </div>
            </div>
            
            <div class="mt-6 space-y-4">
                <div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm text-blue-700">Umsatz</span>
                        <span class="text-2xl font-bold text-blue-900">
                            €{{ number_format($businessHoursComparison['business_hours']['revenue'], 0) }}
                        </span>
                    </div>
                    <div class="mt-2 h-2 w-full rounded-full bg-blue-200">
                        <div class="h-2 rounded-full bg-blue-600" 
                             style="width: {{ $businessHoursComparison['business_hours']['percentage'] }}%">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-blue-700">Termine</span>
                    <span class="font-medium text-blue-900">
                        {{ $businessHoursComparison['business_hours']['appointments'] }}
                    </span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-blue-700">Anteil am Gesamtumsatz</span>
                    <span class="font-medium text-blue-900">
                        {{ $businessHoursComparison['business_hours']['percentage'] }}%
                    </span>
                </div>
            </div>
        </div>
        
        {{-- After Hours Card --}}
        <div class="rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-indigo-900">Außerhalb Geschäftszeiten</h4>
                    <p class="text-sm text-indigo-700">18:00 - 8:00 Uhr</p>
                </div>
                <div class="rounded-full bg-indigo-200 p-3">
                    <x-heroicon-o-moon class="h-6 w-6 text-indigo-600" />
                </div>
            </div>
            
            <div class="mt-6 space-y-4">
                <div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm text-indigo-700">Umsatz</span>
                        <span class="text-2xl font-bold text-indigo-900">
                            €{{ number_format($businessHoursComparison['after_hours']['revenue'], 0) }}
                        </span>
                    </div>
                    <div class="mt-2 h-2 w-full rounded-full bg-indigo-200">
                        <div class="h-2 rounded-full bg-indigo-600" 
                             style="width: {{ $businessHoursComparison['after_hours']['percentage'] }}%">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-700">Termine</span>
                    <span class="font-medium text-indigo-900">
                        {{ $businessHoursComparison['after_hours']['appointments'] }}
                    </span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-indigo-700">Anteil am Gesamtumsatz</span>
                    <span class="font-medium text-indigo-900">
                        {{ $businessHoursComparison['after_hours']['percentage'] }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    {{-- ROI Trend Chart --}}
    <div class="mt-8 rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">ROI-Trend</h3>
        <div class="h-64">
            <canvas id="roi-trend-chart" wire:ignore></canvas>
        </div>
    </div>
    
    {{-- Scripts for Charts --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ROI Trend Chart
            const trendCtx = document.getElementById('roi-trend-chart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json(array_column($roiTrends, 'label')),
                    datasets: [{
                        label: 'ROI %',
                        data: @json(array_column($roiTrends, 'roi')),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'ROI: ' + context.parsed.y.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
            
            // Branch mini charts
            @foreach($branchComparison as $branch)
                const branchCtx{{ $branch['id'] }} = document.getElementById('branch-chart-{{ $branch['id'] }}').getContext('2d');
                new Chart(branchCtx{{ $branch['id'] }}, {
                    type: 'line',
                    data: {
                        labels: ['', '', '', '', ''],
                        datasets: [{
                            data: [
                                {{ rand(50, 150) }}, 
                                {{ rand(50, 150) }}, 
                                {{ $branch['roi'] }}, 
                                {{ rand(50, 150) }}, 
                                {{ rand(50, 150) }}
                            ],
                            borderColor: @if($branch['roi_status'] === 'excellent') 'rgb(34, 197, 94)'
                                        @elseif($branch['roi_status'] === 'good') 'rgb(250, 204, 21)'
                                        @elseif($branch['roi_status'] === 'break-even') 'rgb(251, 146, 60)'
                                        @else 'rgb(239, 68, 68)'
                                        @endif,
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        }
                    }
                });
            @endforeach
        });
        
        // Live update visual feedback
        Livewire.on('dataRefreshed', () => {
            // Add a subtle pulse effect to updated elements
            document.querySelectorAll('[data-refresh]').forEach(el => {
                el.classList.add('animate-pulse');
                setTimeout(() => el.classList.remove('animate-pulse'), 1000);
            });
        });
    </script>
    @endpush
</x-filament-panels::page>