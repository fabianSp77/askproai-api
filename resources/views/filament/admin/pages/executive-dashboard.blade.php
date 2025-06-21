<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        {{-- Loading state --}}
        <div wire:loading.flex wire:target="loadDashboardData" class="items-center justify-center h-64">
            <div class="flex items-center space-x-2">
                <x-filament::loading-indicator class="h-5 w-5" />
                <span class="text-gray-500 dark:text-gray-400">Loading dashboard data...</span>
            </div>
        </div>

        <div wire:loading.remove wire:target="loadDashboardData" class="space-y-6">
            {{-- Anomaly Alerts --}}
            @if($anomalies['count'] > 0)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5" />
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
                                {{ $anomalies['count'] }} {{ Str::plural('Anomaly', $anomalies['count']) }} Detected
                            </h3>
                            <div class="mt-2 space-y-2">
                                @foreach($anomalies['alerts'] as $alert)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-red-700 dark:text-red-300">{{ $alert['message'] }}</span>
                                        <span class="text-xs text-red-600 dark:text-red-400">
                                            {{ $alert['severity'] === 'critical' ? 'Critical' : 'Warning' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Real-time Operational Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                {{-- Active Calls --}}
                <div wire:click="handleMetricClick('active_calls', {{ $operationalMetrics['active_calls'] ?? 0 }})"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 cursor-pointer hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Calls</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $operationalMetrics['active_calls'] ?? 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <x-heroicon-o-phone class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    @if(($operationalMetrics['queue']['depth'] ?? 0) > 0)
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-amber-600 dark:text-amber-400 font-medium">
                                {{ $operationalMetrics['queue']['depth'] }} in queue
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Today's Appointments --}}
                <div wire:click="handleMetricClick('appointments_today', {{ $operationalMetrics['today']['appointments']['total'] ?? 0 }})"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 cursor-pointer hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Appointments</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $operationalMetrics['today']['appointments']['total'] ?? 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <x-heroicon-o-calendar class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $operationalMetrics['today']['appointments']['completed'] ?? 0 }} completed
                        </span>
                        <span class="ml-auto text-green-600 dark:text-green-400 font-medium">
                            {{ $operationalMetrics['today']['appointments']['completion_rate'] ?? 0 }}%
                        </span>
                    </div>
                </div>

                {{-- Conversion Rate --}}
                <div wire:click="handleMetricClick('conversion_rate', {{ $operationalMetrics['today']['calls']['conversion_rate'] ?? 0 }})"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 cursor-pointer hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                            <p class="text-3xl font-bold mt-2 {{ ($operationalMetrics['today']['calls']['conversion_rate'] ?? 0) >= 50 ? 'text-green-600 dark:text-green-400' : (($operationalMetrics['today']['calls']['conversion_rate'] ?? 0) >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $operationalMetrics['today']['calls']['conversion_rate'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $operationalMetrics['today']['calls']['booked'] ?? 0 }} of {{ $operationalMetrics['today']['calls']['total'] ?? 0 }} calls
                    </div>
                </div>

                {{-- System Health --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">System Health</p>
                            <p class="text-lg font-semibold mt-2 capitalize {{ ($operationalMetrics['system_health']['status'] ?? 'unknown') === 'operational' ? 'text-green-600 dark:text-green-400' : (($operationalMetrics['system_health']['status'] ?? 'unknown') === 'degraded' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $operationalMetrics['system_health']['status'] ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="p-3 {{ ($operationalMetrics['system_health']['status'] ?? 'unknown') === 'operational' ? 'bg-green-100 dark:bg-green-900/30' : (($operationalMetrics['system_health']['status'] ?? 'unknown') === 'degraded' ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30') }} rounded-lg">
                            <x-heroicon-o-server class="h-6 w-6 {{ ($operationalMetrics['system_health']['status'] ?? 'unknown') === 'operational' ? 'text-green-600 dark:text-green-400' : (($operationalMetrics['system_health']['status'] ?? 'unknown') === 'degraded' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}" />
                        </div>
                    </div>
                    <div class="mt-4 space-y-1">
                        @foreach(($operationalMetrics['system_health']['services'] ?? []) as $service => $status)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $service }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500 dark:text-gray-500">{{ $status['response_time'] }}ms</span>
                                    <div class="w-2 h-2 rounded-full {{ $status['status'] === 'operational' ? 'bg-green-500' : ($status['status'] === 'degraded' ? 'bg-amber-500' : 'bg-red-500') }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Conversion Funnel --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Conversion Funnel</h3>
                <div class="space-y-4">
                    @foreach(($operationalMetrics['conversion_funnel']['stages'] ?? []) as $index => $stage)
                        <div class="relative">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $index + 1 }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stage['name'] }}</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($stage['count']) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $stage['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="mt-2 ml-11 mr-24">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full" style="width: {{ $stage['percentage'] }}%"></div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="absolute left-4 top-8 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Financial Metrics --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Unit Economics --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Unit Economics</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">LTV</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    €{{ number_format($financialMetrics['unit_economics']['ltv'] ?? 0, 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">CAC</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    €{{ number_format($financialMetrics['unit_economics']['cac'] ?? 0, 0) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">LTV:CAC Ratio</span>
                                <span class="text-lg font-semibold {{ ($financialMetrics['unit_economics']['ltv_cac_ratio'] ?? 0) >= 3 ? 'text-green-600 dark:text-green-400' : (($financialMetrics['unit_economics']['ltv_cac_ratio'] ?? 0) >= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $financialMetrics['unit_economics']['ltv_cac_ratio'] ?? 0 }}:1
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Payback Period</span>
                                <span class="text-lg font-semibold {{ ($financialMetrics['unit_economics']['payback_months'] ?? 0) <= 12 ? 'text-green-600 dark:text-green-400' : (($financialMetrics['unit_economics']['payback_months'] ?? 0) <= 18 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $financialMetrics['unit_economics']['payback_months'] ?? 0 }} months
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 rounded-lg {{ ($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'excellent' ? 'bg-green-50 dark:bg-green-900/20' : (($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'good' ? 'bg-blue-50 dark:bg-blue-900/20' : (($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'fair' ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-red-50 dark:bg-red-900/20')) }}">
                            <p class="text-sm font-medium {{ ($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'excellent' ? 'text-green-700 dark:text-green-300' : (($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'good' ? 'text-blue-700 dark:text-blue-300' : (($financialMetrics['unit_economics']['health_score'] ?? 'unknown') === 'fair' ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300')) }}">
                                Health Score: {{ ucfirst($financialMetrics['unit_economics']['health_score'] ?? 'Unknown') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Revenue Trends Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Revenue Trends</h3>
                    <div class="h-64">
                        <canvas id="revenueTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Branch Comparison --}}
            @if(count($branchComparison['branches'] ?? []) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Branch Performance</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Calls</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bookings</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conv. Rate</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Δ Week</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($branchComparison['branches'] as $branch)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $branch['rank'] <= 3 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-800' }}">
                                                <span class="text-sm font-medium {{ $branch['rank'] <= 3 ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $branch['rank'] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $branch['branch']['name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                            {{ number_format($branch['metrics']['calls']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                            {{ number_format($branch['metrics']['bookings']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span class="font-medium {{ $branch['metrics']['conversion_rate'] >= 50 ? 'text-green-600 dark:text-green-400' : ($branch['metrics']['conversion_rate'] >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                                {{ $branch['metrics']['conversion_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-medium">
                                            €{{ number_format($branch['metrics']['revenue'], 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span class="inline-flex items-center {{ $branch['metrics']['revenue_change'] > 0 ? 'text-green-600 dark:text-green-400' : ($branch['metrics']['revenue_change'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}">
                                                @if($branch['metrics']['revenue_change'] > 0)
                                                    <x-heroicon-m-arrow-up class="w-4 h-4 mr-1" />
                                                @elseif($branch['metrics']['revenue_change'] < 0)
                                                    <x-heroicon-m-arrow-down class="w-4 h-4 mr-1" />
                                                @else
                                                    <x-heroicon-m-minus class="w-4 h-4 mr-1" />
                                                @endif
                                                {{ abs($branch['metrics']['revenue_change']) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                @this.on('refresh-dashboard', () => {
                    // Refresh charts when data updates
                    setTimeout(() => {
                        initializeCharts();
                    }, 100);
                });
                
                // Initialize charts on first load
                initializeCharts();
            });
            
            function initializeCharts() {
                // Revenue Trends Chart
                const revenueTrendsCtx = document.getElementById('revenueTrendsChart');
                if (revenueTrendsCtx) {
                    const trends = @json($financialMetrics['trends'] ?? []);
                    
                    new Chart(revenueTrendsCtx, {
                        type: 'line',
                        data: {
                            labels: trends.map(t => t.period),
                            datasets: [{
                                label: 'Revenue',
                                data: trends.map(t => t.revenue),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.3
                            }]
                        },
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
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '€' + value.toLocaleString();
                                        }
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