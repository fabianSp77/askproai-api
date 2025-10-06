<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Alerts Section --}}
        @if(count($alerts) > 0)
            <div class="space-y-2">
                @foreach($alerts as $alert)
                    <div class="p-4 rounded-lg flex items-center space-x-3
                        @if($alert['type'] === 'success') bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800
                        @elseif($alert['type'] === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800
                        @elseif($alert['type'] === 'danger') bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                        @else bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800
                        @endif">
                        <span class="text-2xl">{{ $alert['icon'] }}</span>
                        <span class="text-sm font-medium
                            @if($alert['type'] === 'success') text-green-800 dark:text-green-200
                            @elseif($alert['type'] === 'warning') text-yellow-800 dark:text-yellow-200
                            @elseif($alert['type'] === 'danger') text-red-800 dark:text-red-200
                            @else text-gray-800 dark:text-gray-200
                            @endif">
                            {{ $alert['message'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Main Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Today's Profit --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Profit Heute</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($stats['todayProfit'] / 100, 2, ',', '.') }} €
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $stats['todayCallCount'] }} Anrufe
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full">
                            <span class="text-2xl">💰</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Month's Profit --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Profit Diesen Monat</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($stats['monthProfit'] / 100, 2, ',', '.') }} €
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $stats['monthCallCount'] }} Anrufe
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <span class="text-2xl">📊</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Average Margin --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Durchschn. Marge</p>
                            <p class="mt-2 text-3xl font-bold
                                @if($stats['avgMargin'] > 50) text-green-600 dark:text-green-400
                                @elseif($stats['avgMargin'] > 20) text-yellow-600 dark:text-yellow-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                {{ $stats['avgMargin'] }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Heute
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <span class="text-2xl">📈</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trend --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">30-Tage-Trend</p>
                            <p class="mt-2 text-3xl font-bold
                                @if($profitTrends['trend'] > 0) text-green-600 dark:text-green-400
                                @elseif($profitTrends['trend'] < 0) text-red-600 dark:text-red-400
                                @else text-gray-600 dark:text-gray-400
                                @endif">
                                @if($profitTrends['trend'] > 0) ↑
                                @elseif($profitTrends['trend'] < 0) ↓
                                @else →
                                @endif
                                {{ abs($profitTrends['trend']) }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                vs. Vorperiode
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                            <span class="text-2xl">📉</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Platform/Mandant Profit Breakdown (Super Admin only) --}}
        @if($isSuperAdmin && ($stats['todayPlatformProfit'] > 0 || $stats['todayResellerProfit'] > 0))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform-Profit Heute</p>
                        <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($stats['todayPlatformProfit'] / 100, 2, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Unser direkter Gewinn</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mandanten-Profit Heute</p>
                        <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($stats['todayResellerProfit'] / 100, 2, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Gewinn der Mandanten</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Profit Chart --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Profit-Entwicklung (30 Tage)
                </h3>
                <canvas id="profitChart" class="w-full" style="height: 300px;"></canvas>
            </div>
        </div>

        {{-- Top Performers --}}
        @if(count($topPerformers) > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        @if($isSuperAdmin)
                            Top 10 Profitabelste Unternehmen
                        @else
                            Top 5 Profitabelste Kunden
                        @endif
                    </h3>
                    <div class="space-y-3">
                        @foreach($topPerformers as $index => $performer)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center space-x-3">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full
                                        @if($index === 0) bg-yellow-500 text-white
                                        @elseif($index === 1) bg-gray-400 text-white
                                        @elseif($index === 2) bg-orange-600 text-white
                                        @else bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300
                                        @endif text-sm font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $performer['name'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @if($performer['type'] === 'reseller')
                                                Mandant
                                            @elseif($performer['type'] === 'customer')
                                                Kunde
                                            @else
                                                {{ ucfirst($performer['type']) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ number_format($performer['profit'] / 100, 2, ',', '.') }} €
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Chart.js Script --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('profitChart').getContext('2d');
            const isDarkMode = document.documentElement.classList.contains('dark');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [
                        {
                            label: 'Gesamt-Profit',
                            data: @json($chartData['totalProfit']),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                        @if($isSuperAdmin)
                        ,{
                            label: 'Platform-Profit',
                            data: @json($chartData['platformProfit']),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Mandanten-Profit',
                            data: @json($chartData['resellerProfit']),
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                        @endif
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: isDarkMode ? '#fff' : '#000'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y.toFixed(2) + ' €';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: isDarkMode ? '#9ca3af' : '#4b5563',
                                callback: function(value) {
                                    return value + ' €';
                                }
                            },
                            grid: {
                                color: isDarkMode ? 'rgba(156, 163, 175, 0.2)' : 'rgba(156, 163, 175, 0.3)'
                            }
                        },
                        x: {
                            ticks: {
                                color: isDarkMode ? '#9ca3af' : '#4b5563'
                            },
                            grid: {
                                color: isDarkMode ? 'rgba(156, 163, 175, 0.2)' : 'rgba(156, 163, 175, 0.3)'
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
