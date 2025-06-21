<x-filament-widgets::widget>
    <x-filament::card>
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Umsatz nach Filiale
                    </h2>
                    <p class="text-sm text-gray-500">{{ $period }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-primary-600">
                        {{ number_format($totalRevenue, 2, ',', '.') }} €
                    </p>
                    <p class="text-xs text-gray-500">Gesamt</p>
                </div>
            </div>

            {{-- Branch List --}}
            <div class="space-y-3">
                @forelse($branches as $branch)
                    <div class="relative">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $branch['name'] }}
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ number_format($branch['revenue'], 2, ',', '.') }} €
                            </span>
                        </div>
                        
                        {{-- Progress Bar --}}
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div 
                                class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                                style="width: {{ $branch['percentage'] }}%"
                            ></div>
                        </div>
                        
                        {{-- Additional Info --}}
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-500">
                                {{ $branch['appointments_count'] }} Termine
                            </span>
                            <span class="text-xs text-gray-500">
                                Ø {{ number_format($branch['avg_per_appointment'], 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-heroicon-o-building-office class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500">Keine Umsätze in diesem Zeitraum</p>
                    </div>
                @endforelse
            </div>

            {{-- Trend Chart --}}
            @if(!empty($chartData['data']) && array_sum($chartData['data']) > 0)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        7-Tage Trend
                    </h3>
                    <div class="relative h-32">
                        <canvas id="revenue-trend-chart-{{ $this->getId() }}"></canvas>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('revenue-trend-chart-{{ $this->getId() }}').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: @json($chartData['labels']),
                                datasets: [{
                                    label: 'Umsatz',
                                    data: @json($chartData['data']),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.3,
                                    fill: true,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.parsed.y.toFixed(2).replace('.', ',') + ' €';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return value.toFixed(0) + ' €';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    });
                </script>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget>