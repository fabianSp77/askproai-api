<div class="fi-wi-widget">
    <div class="fi-wi-widget-content bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <div class="absolute inset-0 w-3 h-3 bg-green-400 rounded-full animate-ping"></div>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Real-Time Metrics</h2>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <x-heroicon-m-clock class="w-4 h-4" />
                    <span>Last update: {{ $metrics['last_update'] ?? '--:--:--' }}</span>
                </div>
            </div>
        </div>

        {{-- Metrics Grid --}}
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                {{-- Active Calls --}}
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 mb-3">
                        <x-heroicon-o-phone class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $metrics['active_calls'] ?? 0 }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Active Calls</p>
                </div>

                {{-- Calls/Hour --}}
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 mb-3">
                        <x-heroicon-o-arrow-trending-up class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $metrics['calls_per_hour'] ?? 0 }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Calls/Hour</p>
                </div>

                {{-- Conversion Rate --}}
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 mb-3">
                        <x-heroicon-o-chart-pie class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <p class="text-3xl font-bold {{ ($metrics['conversion_rate'] ?? 0) >= 50 ? 'text-green-600 dark:text-green-400' : (($metrics['conversion_rate'] ?? 0) >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $metrics['conversion_rate'] ?? 0 }}%
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Conversion</p>
                </div>

                {{-- Queue Depth --}}
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full {{ ($metrics['queue_depth'] ?? 0) > 5 ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-gray-100 dark:bg-gray-900/30' }} mb-3">
                        <x-heroicon-o-users class="w-6 h-6 {{ ($metrics['queue_depth'] ?? 0) > 5 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}" />
                    </div>
                    <p class="text-3xl font-bold {{ ($metrics['queue_depth'] ?? 0) > 5 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $metrics['queue_depth'] ?? 0 }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">In Queue</p>
                </div>
            </div>

            {{-- Activity Sparkline --}}
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Activity (Last 60 Minutes)</h3>
                <div class="h-32">
                    <canvas id="activitySparkline"></canvas>
                </div>
            </div>

            {{-- API Performance --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">API Response Times</h3>
                <div class="space-y-3">
                    @foreach(($metrics['api_response_times'] ?? []) as $service => $responseTime)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $service }}</span>
                                <span class="font-medium {{ $responseTime < 100 ? 'text-green-600 dark:text-green-400' : ($responseTime < 200 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $responseTime }}ms
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="{{ $responseTime < 100 ? 'bg-green-600' : ($responseTime < 200 ? 'bg-amber-600' : 'bg-red-600') }} h-1.5 rounded-full transition-all duration-300" 
                                     style="width: {{ min(($responseTime / 300) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Additional Metrics --}}
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Avg Call Duration</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ gmdate('i:s', $metrics['avg_call_duration'] ?? 0) }}
                        </span>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Avg Wait Time</span>
                        <span class="font-medium {{ ($metrics['avg_wait_time'] ?? 0) < 30 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $metrics['avg_wait_time'] ?? 0 }}s
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                let sparklineChart;
                
                function initSparkline() {
                    const ctx = document.getElementById('activitySparkline');
                    if (!ctx) return;
                    
                    if (sparklineChart) {
                        sparklineChart.destroy();
                    }
                    
                    const sparklineData = @json($sparklineData);
                    
                    sparklineChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: sparklineData.labels || [],
                            datasets: [
                                {
                                    label: 'Calls',
                                    data: sparklineData.calls || [],
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    tension: 0.3
                                },
                                {
                                    label: 'Bookings',
                                    data: sparklineData.bookings || [],
                                    borderColor: 'rgb(16, 185, 129)',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                }
                            },
                            scales: {
                                x: {
                                    display: false,
                                },
                                y: {
                                    display: false,
                                    beginAtZero: true
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                }
                
                // Initialize on load
                initSparkline();
                
                // Reinitialize on Livewire update
                Livewire.on('metrics-updated', () => {
                    setTimeout(initSparkline, 100);
                });
            });
        </script>
    @endpush
</div>