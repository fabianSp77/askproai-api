<!-- Premium Analytics Dashboard for All Companies -->
<div class="premium-analytics-container">
    <!-- Include Premium Styles -->
    <link rel="stylesheet" href="{{ asset('css/premium-analytics-dashboard.css') }}">
    
    <!-- Additional inline styles for integration -->
    <style>
        .premium-analytics-container {
            background: linear-gradient(135deg, #1e3a8a 0%, #312e81 50%, #581c87 100%);
            margin: -2rem;
            padding: 2rem;
            border-radius: 1rem;
            min-height: 100vh;
        }
        
        .glass-card-premium {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .glass-card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            background: rgba(30, 41, 59, 0.9);
        }
        
        .metric-card-premium {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.8));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .metric-value-premium {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            line-height: 1.2;
        }
        
        .chart-container-premium {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            height: 400px;
            position: relative;
        }
    </style>

    <div class="dashboard-header text-center mb-8">
        <h1 class="text-4xl font-bold mb-2" style="background: linear-gradient(135deg, #60a5fa, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            📊 Premium Analytics Dashboard
        </h1>
        <p class="text-gray-300 text-lg">Gesamt-Übersicht aller Unternehmen</p>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="metric-card-premium">
            <div class="metric-value-premium">
                €{{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-gray-300 text-sm font-medium mt-2">Gesamt-Umsatz</div>
            @if(($stats['revenue'] ?? 0) > 0)
                <div class="mt-2 inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    +12.5%
                </div>
            @endif
        </div>

        <!-- Total Calls -->
        <div class="metric-card-premium">
            <div class="metric-value-premium">
                {{ number_format($stats['total_calls'] ?? 0) }}
            </div>
            <div class="text-gray-300 text-sm font-medium mt-2">Gesamt-Anrufe</div>
            <div class="text-gray-400 text-xs mt-1">
                {{ $stats['call_success_rate'] ?? 0 }}% erfolgreich
            </div>
        </div>

        <!-- Active Companies -->
        <div class="metric-card-premium">
            <div class="metric-value-premium">
                {{ $stats['total_companies'] ?? 0 }}
            </div>
            <div class="text-gray-300 text-sm font-medium mt-2">Aktive Unternehmen</div>
        </div>

        <!-- Conversion Rate -->
        <div class="metric-card-premium">
            <div class="metric-value-premium">
                {{ $stats['completion_rate'] ?? 0 }}%
            </div>
            <div class="text-gray-300 text-sm font-medium mt-2">Abschlussrate</div>
            <div class="text-gray-400 text-xs mt-1">
                Termine zu Abschlüssen
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Trend Chart -->
        <div class="glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Umsatz-Trend (7 Tage)</h3>
            <div class="chart-container-premium">
                <canvas id="premiumRevenueChart"></canvas>
            </div>
        </div>

        <!-- Companies Performance Chart -->
        <div class="glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Unternehmens-Performance</h3>
            <div class="chart-container-premium">
                <canvas id="premiumPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Appointments Distribution & Call Heatmap -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Appointments Distribution -->
        <div class="glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Termine nach Unternehmen</h3>
            <div class="chart-container-premium">
                <canvas id="premiumAppointmentsChart"></canvas>
            </div>
        </div>

        <!-- Call Volume Heatmap -->
        <div class="glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Anruf-Heatmap</h3>
            <div class="p-4">
                @if(isset($heatmapData['peak_hour']))
                    <div class="text-gray-300 text-sm mb-4">Hauptzeit: {{ sprintf('%02d:00', $heatmapData['peak_hour']) }} Uhr</div>
                @endif
                <div class="grid grid-cols-8 gap-1">
                    @if(isset($heatmapData['heatmap']))
                        @foreach($heatmapData['heatmap'] as $dayData)
                            <div class="text-gray-400 text-xs py-1">{{ substr($dayData['day'], 0, 2) }}</div>
                            @foreach(array_slice($dayData['data'], 8, 12) as $hourIndex => $calls)
                                <div class="w-8 h-8 rounded" 
                                     style="background-color: rgba(59, 130, 246, {{ min($calls / 10, 1) }})"
                                     title="{{ $dayData['day'] }} {{ sprintf('%02d:00', $hourIndex + 8) }} - {{ $calls }} Anrufe">
                                </div>
                            @endforeach
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers & Activity Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Performers -->
        <div class="glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Top Performer</h3>
            
            @if(isset($topPerformers))
                <div class="space-y-4">
                    <!-- Revenue Leaders -->
                    @if(isset($topPerformers['revenue']) && count($topPerformers['revenue']) > 0)
                        <div>
                            <h4 class="text-gray-200 font-medium mb-2">🏆 Umsatz-Spitzenreiter</h4>
                            @foreach($topPerformers['revenue'] as $index => $company)
                                <div class="flex items-center gap-2 p-2 bg-white/10 rounded-lg mb-1">
                                    <span class="text-yellow-400 font-bold">{{ $index + 1 }}</span>
                                    <span class="text-gray-200 text-sm">{{ $company }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Call Volume Leaders -->
                    @if(isset($topPerformers['calls']) && count($topPerformers['calls']) > 0)
                        <div>
                            <h4 class="text-gray-200 font-medium mb-2">📞 Anruf-Volumen</h4>
                            @foreach($topPerformers['calls'] as $index => $company)
                                <div class="flex items-center gap-2 p-2 bg-white/10 rounded-lg mb-1">
                                    <span class="text-blue-400 font-bold">{{ $index + 1 }}</span>
                                    <span class="text-gray-200 text-sm">{{ $company }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Best Conversion -->
                    @if(isset($topPerformers['conversion']) && count($topPerformers['conversion']) > 0)
                        <div>
                            <h4 class="text-gray-200 font-medium mb-2">🎯 Beste Konversion</h4>
                            @foreach($topPerformers['conversion'] as $index => $company)
                                <div class="flex items-center gap-2 p-2 bg-white/10 rounded-lg mb-1">
                                    <span class="text-green-400 font-bold">{{ $index + 1 }}</span>
                                    <span class="text-gray-200 text-sm">{{ $company }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Activity Timeline -->
        <div class="lg:col-span-2 glass-card-premium">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Echtzeit-Aktivität</h3>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                @if(isset($activityTimeline) && count($activityTimeline) > 0)
                    @foreach($activityTimeline as $activity)
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full {{ $activity['type'] === 'success' ? 'bg-green-400' : ($activity['type'] === 'warning' ? 'bg-yellow-400' : 'bg-blue-400') }}"></div>
                                <div>
                                    <div class="text-gray-200 font-medium text-sm">{{ $activity['event'] }}</div>
                                    <div class="text-gray-400 text-xs">{{ $activity['company'] }}</div>
                                </div>
                            </div>
                            <div class="text-gray-500 text-xs">{{ $activity['time'] }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-gray-400 text-center py-8">
                        Keine aktuellen Aktivitäten
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Company Comparison Table -->
    @if(isset($companyComparison) && count($companyComparison) > 0)
        <div class="glass-card-premium mt-8">
            <h3 class="text-gray-200 text-lg font-semibold mb-4">Unternehmens-Vergleich</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-gray-300 text-sm">
                            <th class="text-left p-2">Unternehmen</th>
                            <th class="text-center p-2">Termine</th>
                            <th class="text-center p-2">Anrufe</th>
                            <th class="text-center p-2">Umsatz</th>
                            <th class="text-center p-2">Abschlussrate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companyComparison as $company)
                            <tr class="text-gray-200 border-t border-gray-700">
                                <td class="p-2">{{ $company['company'] }}</td>
                                <td class="text-center p-2">{{ $company['appointments'] }}</td>
                                <td class="text-center p-2">{{ $company['calls'] }}</td>
                                <td class="text-center p-2">€{{ number_format($company['revenue'] ?? 0, 0, ',', '.') }}</td>
                                <td class="text-center p-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ ($company['completion_rate'] ?? 0) > 70 ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300' }}">
                                        {{ $company['completion_rate'] ?? 0 }}%
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

<!-- Premium Charts JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js defaults for glass theme
    Chart.defaults.color = 'rgba(255, 255, 255, 0.8)';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
    
    @if(isset($chartData) && !empty($chartData))
        // Revenue Trend Chart
        const revenueCtx = document.getElementById('premiumRevenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: 'Umsatz',
                        data: @json($chartData['revenue'] ?? []),
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return 'Umsatz: €' + context.parsed.y.toLocaleString('de-DE');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(229, 231, 235, 0.5)' },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toLocaleString('de-DE');
                                }
                            }
                        },
                        x: {
                            grid: { color: 'rgba(229, 231, 235, 0.5)' }
                        }
                    }
                }
            });
        }

        // Performance Chart
        const performanceCtx = document.getElementById('premiumPerformanceChart');
        if (performanceCtx) {
            new Chart(performanceCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: 'Anrufe',
                        data: @json($chartData['calls'] ?? []),
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                    }, {
                        label: 'Termine',
                        data: @json($chartData['appointments'] ?? []),
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { padding: 20 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(229, 231, 235, 0.5)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Appointments Distribution Chart
        const appointmentsCtx = document.getElementById('premiumAppointmentsChart');
        if (appointmentsCtx && window.companyComparison && window.companyComparison.length > 0) {
            const topCompanies = window.companyComparison.slice(0, 6);
            new Chart(appointmentsCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: topCompanies.map(c => c.company),
                    datasets: [{
                        data: topCompanies.map(c => c.appointments),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                        ],
                        borderWidth: 3,
                        borderColor: 'white',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 11 }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    @endif

    // Set company comparison data globally for chart access
    window.companyComparison = @json($companyComparison ?? []);
});
</script>

<!-- Load Premium Dashboard JavaScript -->
<script src="{{ asset('js/premium-analytics-dashboard.js') }}" defer></script>