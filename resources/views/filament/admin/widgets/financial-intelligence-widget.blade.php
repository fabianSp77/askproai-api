<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm">
        {{-- Header with gradient background --}}
        <div class="relative bg-gradient-to-r {{ $this->getRoiColorClass($roi['summary']['roi_percentage']) }} p-6 text-white">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-medium text-white/90">Return on Investment</h3>
                    <p class="text-sm text-white/70">{{ $periodLabel }}</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold">{{ number_format($roi['summary']['roi_percentage'], 1, ',', '.') }}%</div>
                    <div class="text-sm text-white/70">ROI</div>
                </div>
            </div>
            
            {{-- Sparkline Trend --}}
            @if(count($trend) > 1)
                <div class="mt-4 h-16">
                    <canvas id="roi-trend-{{ $this->getId() }}" class="w-full h-full"></canvas>
                </div>
            @endif
        </div>
        
        {{-- Key Metrics Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 p-6 border-b border-gray-100 dark:border-gray-700">
            {{-- Revenue --}}
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-currency-euro class="w-5 h-5 text-green-500" />
                    <span class="text-sm text-gray-500 dark:text-gray-400">Umsatz</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($roi['summary']['total_revenue'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Costs --}}
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5 text-red-500" />
                    <span class="text-sm text-gray-500 dark:text-gray-400">Kosten</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($roi['summary']['total_costs'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Profit --}}
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-blue-500" />
                    <span class="text-sm text-gray-500 dark:text-gray-400">Gewinn</span>
                </div>
                <div class="mt-1 text-xl font-semibold {{ $roi['summary']['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($roi['summary']['net_profit'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Cost per Booking --}}
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calculator class="w-5 h-5 text-purple-500" />
                    <span class="text-sm text-gray-500 dark:text-gray-400">€/Termin</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($costPerBooking, 2, ',', '.') }}€
                </div>
            </div>
        </div>
        
        {{-- Business Hours Analysis --}}
        <div class="p-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Geschäftszeiten-Analyse</h4>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Business Hours Card --}}
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-sun class="w-5 h-5 text-blue-600" />
                            <span class="font-medium text-blue-900 dark:text-blue-100">
                                Geschäftszeiten ({{ $roi['business_hours_analysis']['business_hours_range']['start'] }}-{{ $roi['business_hours_analysis']['business_hours_range']['end'] }} Uhr)
                            </span>
                        </div>
                        <span class="text-lg font-bold {{ $this->getRoiTextColorClass($roi['business_hours_analysis']['business_hours']['roi']) }}">
                            {{ number_format($roi['business_hours_analysis']['business_hours']['roi'], 1, ',', '.') }}% ROI
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Umsatz:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ number_format($roi['business_hours_analysis']['business_hours']['revenue'], 2, ',', '.') }}€
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Kosten:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ number_format($roi['business_hours_analysis']['business_hours']['costs'], 2, ',', '.') }}€
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Anrufe:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ $roi['business_hours_analysis']['business_hours']['calls'] }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Termine:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ $roi['business_hours_analysis']['business_hours']['bookings'] }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- Visual Revenue Bar --}}
                    @php
                        $businessHoursPercentage = $roi['summary']['total_revenue'] > 0 
                            ? round(($roi['business_hours_analysis']['business_hours']['revenue'] / $roi['summary']['total_revenue']) * 100)
                            : 0;
                    @endphp
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Umsatzanteil</span>
                            <span>{{ $businessHoursPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $businessHoursPercentage }}%"></div>
                        </div>
                    </div>
                </div>
                
                {{-- After Hours Card --}}
                <div class="p-4 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-moon class="w-5 h-5 text-indigo-600" />
                            <span class="font-medium text-indigo-900 dark:text-indigo-100">
                                Außerhalb Geschäftszeiten
                            </span>
                        </div>
                        <span class="text-lg font-bold {{ $this->getRoiTextColorClass($roi['business_hours_analysis']['after_hours']['roi']) }}">
                            {{ number_format($roi['business_hours_analysis']['after_hours']['roi'], 1, ',', '.') }}% ROI
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Umsatz:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ number_format($roi['business_hours_analysis']['after_hours']['revenue'], 2, ',', '.') }}€
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Kosten:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ number_format($roi['business_hours_analysis']['after_hours']['costs'], 2, ',', '.') }}€
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Anrufe:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ $roi['business_hours_analysis']['after_hours']['calls'] }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Termine:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">
                                {{ $roi['business_hours_analysis']['after_hours']['bookings'] }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- Visual Revenue Bar --}}
                    @php
                        $afterHoursPercentage = $roi['summary']['total_revenue'] > 0 
                            ? round(($roi['business_hours_analysis']['after_hours']['revenue'] / $roi['summary']['total_revenue']) * 100)
                            : 0;
                    @endphp
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Umsatzanteil</span>
                            <span>{{ $afterHoursPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $afterHoursPercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Additional Insights --}}
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Anrufe gesamt:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-2">
                            {{ $roi['call_metrics']['total_calls'] }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Ø Anrufdauer:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-2">
                            {{ gmdate('i:s', $roi['call_metrics']['avg_duration_seconds']) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Konversion:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-2">
                            {{ $roi['call_metrics']['total_calls'] > 0 
                                ? round(($roi['call_metrics']['calls_with_bookings'] / $roi['call_metrics']['total_calls']) * 100, 1) 
                                : 0 }}%
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">€ pro Anruf erwartet:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-2">
                            {{ number_format($expectedValuePerCall, 2, ',', '.') }}€
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer Actions --}}
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <button 
                    wire:click="$dispatch('showRoiDetails')"
                    class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                >
                    Details anzeigen →
                </button>
                <div class="text-xs text-gray-500">
                    Aktualisiert vor {{ now()->diffInSeconds($roi['updated_at'] ?? now()) }} Sekunden
                </div>
            </div>
        </div>
    </div>
    
    {{-- Sparkline Chart Script --}}
    @if(count($trend) > 1)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('roi-trend-{{ $this->getId() }}').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json(array_column($trend, 'date')),
                        datasets: [{
                            data: @json(array_column($trend, 'roi')),
                            borderColor: 'rgba(255, 255, 255, 0.8)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toFixed(1) + '% ROI';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        }
                    }
                });
            });
        </script>
    @endif
</x-filament-widgets::widget>