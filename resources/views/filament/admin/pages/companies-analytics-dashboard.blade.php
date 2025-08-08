<x-filament-panels::page class="analytics-dashboard">
    <!-- Include Premium Styles -->
    <link rel="stylesheet" href="{{ asset('css/premium-analytics-dashboard.css') }}">
    <!-- Premium Dashboard Styles -->
    <style>
        .analytics-dashboard-basic {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: -2rem -2rem -4rem -2rem;
            padding: 2rem;
        }
        
        .dashboard-container {
            max-width: 7xl;
            margin: 0 auto;
        }
        
        .glass-card-basic {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(31, 38, 135, 0.5);
        }
        
        .metric-card-basic {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .metric-card:hover::before {
            left: 100%;
        }
        
        .metric-value {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
            animation: countUp 2s ease-out;
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .metric-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 0.5rem;
        }
        
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .growth-positive {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .growth-negative {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .chart-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            height: 400px;
        }
        
        .activity-item {
            padding: 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.1) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 12px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .dashboard-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 3rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .section-title {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 1.5rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }
        
        .top-performer-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .top-performer-badge:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }
        
        .heatmap-cell {
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .heatmap-cell:hover {
            transform: scale(1.2);
            z-index: 10;
        }
    </style>

    <div class="dashboard-container space-y-8">
        <!-- Dashboard Header -->
        <div class="text-center mb-8">
            <h1 class="dashboard-title-premium">Analytics Dashboard</h1>
            <p class="text-white/80 text-lg">Complete overview of all companies performance</p>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Revenue -->
            <div class="metric-card-premium group" data-metric="total_revenue">
                <div class="metric-value-premium" data-count="{{ $overviewStats['total_revenue'] }}">
                    €{{ number_format($overviewStats['total_revenue'], 0) }}
                </div>
                <div class="metric-label">Total Revenue</div>
                @if($overviewStats['revenue_growth'] != 0)
                    <div class="growth-indicator-premium {{ $overviewStats['revenue_growth'] >= 0 ? 'growth-positive-premium' : 'growth-negative-premium' }}">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="{{ $overviewStats['revenue_growth'] >= 0 ? 'M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z' : 'M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 011.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z' }}" clip-rule="evenodd"/>
                        </svg>
                        {{ abs($overviewStats['revenue_growth']) }}%
                    </div>
                @endif
            </div>

            <!-- Total Calls -->
            <div class="metric-card-premium" data-metric="total_calls">
                <div class="metric-value-premium" data-count="{{ $overviewStats['total_calls'] }}">
                    {{ number_format($overviewStats['total_calls']) }}
                </div>
                <div class="metric-label">Total Calls</div>
                @if($overviewStats['calls_growth'] != 0)
                    <div class="growth-indicator-premium {{ $overviewStats['calls_growth'] >= 0 ? 'growth-positive-premium' : 'growth-negative-premium' }}">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="{{ $overviewStats['calls_growth'] >= 0 ? 'M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z' : 'M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 011.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z' }}" clip-rule="evenodd"/>
                        </svg>
                        {{ abs($overviewStats['calls_growth']) }}%
                    </div>
                @endif
            </div>

            <!-- Active Companies -->
            <div class="metric-card-premium" data-metric="active_companies">
                <div class="metric-value-premium" data-count="{{ $overviewStats['active_companies'] }}">
                    {{ $overviewStats['active_companies'] }}
                </div>
                <div class="metric-label">Active Companies</div>
                <div class="text-white/70 text-sm mt-1">
                    of {{ $overviewStats['total_companies'] }} total
                </div>
            </div>

            <!-- Conversion Rate -->
            <div class="metric-card-premium" data-metric="conversion_rate">
                <div class="metric-value-premium" data-count="{{ $overviewStats['conversion_rate'] }}">
                    {{ $overviewStats['conversion_rate'] }}%
                </div>
                <div class="metric-label">Conversion Rate</div>
                <div class="text-white/70 text-sm mt-1">
                    Calls to Appointments
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Trend Chart -->
            <div class="glass-card-premium p-6">
                <h3 class="section-title-premium">Revenue Trend</h3>
                <div class="chart-container-premium">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Companies Performance Chart -->
            <div class="glass-card-premium p-6">
                <h3 class="section-title-premium">Companies Performance</h3>
                <div class="chart-container-premium">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Appointments Distribution & Call Volume -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Appointments Distribution -->
            <div class="glass-card-premium p-6">
                <h3 class="section-title-premium">Appointments by Company</h3>
                <div class="chart-container-premium">
                    <canvas id="appointmentsChart"></canvas>
                </div>
            </div>

            <!-- Call Volume Heatmap -->
            <div class="glass-card-premium p-6">
                <h3 class="section-title-premium">Call Volume Heatmap</h3>
                <div class="p-4">
                    <div class="text-white/80 text-sm mb-4">Peak Hour: {{ sprintf('%02d:00', $callVolumeData['peak_hour']) }}</div>
                    <div class="grid grid-cols-24 gap-1">
                        @foreach($callVolumeData['heatmap'] as $dayData)
                            <div class="text-white/60 text-xs py-1">{{ $dayData['day'] }}</div>
                            @foreach($dayData['data'] as $hourIndex => $calls)
                                <div class="heatmap-cell-premium w-4 h-4" 
                                     style="background-color: rgba(59, 130, 246, {{ min($calls / 10, 1) }})"
                                     title="{{ $dayData['day'] }} {{ sprintf('%02d:00', $hourIndex) }} - {{ $calls }} calls"
                                     data-day="{{ $dayData['day'] }}"
                                     data-hour="{{ $hourIndex }}"
                                     data-calls="{{ $calls }}">
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers & Activity Timeline -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Top Performers -->
            <div class="glass-card-premium p-6">
                <h3 class="section-title-premium">Top Performers</h3>
                
                <div class="space-y-4">
                    <div>
                        <h4 class="text-white/90 font-semibold mb-2">🏆 Revenue Leaders</h4>
                        @foreach($topPerformers['revenue'] as $index => $company)
                            <div class="top-performer-badge-premium">
                                <span class="text-yellow-400">{{ $index + 1 }}</span>
                                {{ $company }}
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <h4 class="text-white/90 font-semibold mb-2">📞 Call Volume</h4>
                        @foreach($topPerformers['calls'] as $index => $company)
                            <div class="top-performer-badge-premium">
                                <span class="text-blue-400">{{ $index + 1 }}</span>
                                {{ $company }}
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <h4 class="text-white/90 font-semibold mb-2">🎯 Best Conversion</h4>
                        @foreach($topPerformers['conversion'] as $index => $company)
                            <div class="top-performer-badge-premium">
                                <span class="text-green-400">{{ $index + 1 }}</span>
                                {{ $company }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="lg:col-span-2 glass-card p-6">
                <h3 class="section-title-premium">Real-time Activity</h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($activityTimeline as $activity)
                        <div class="activity-item-premium flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full {{ $activity['type'] === 'success' ? 'bg-green-400' : ($activity['type'] === 'warning' ? 'bg-yellow-400' : 'bg-blue-400') }}"></div>
                                <div>
                                    <div class="text-white font-medium">{{ $activity['event'] }}</div>
                                    <div class="text-white/60 text-sm">{{ $activity['company'] }}</div>
                                </div>
                            </div>
                            <div class="text-white/50 text-xs">{{ $activity['time'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- KPI Comparison Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach(['revenue', 'calls', 'appointments'] as $metric)
                <div class="glass-card-premium p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-white font-semibold capitalize">{{ $metric }}</h3>
                        <div class="growth-indicator {{ $comparisonMetrics[$metric]['change'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                            {{ $comparisonMetrics[$metric]['change'] >= 0 ? '+' : '' }}{{ $comparisonMetrics[$metric]['change'] }}%
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-white/80">This Month</span>
                            <span class="text-white font-medium">
                                {{ $metric === 'revenue' ? '€' : '' }}{{ number_format($comparisonMetrics[$metric]['current']) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-white/60">Last Month</span>
                            <span class="text-white/80">
                                {{ $metric === 'revenue' ? '€' : '' }}{{ number_format($comparisonMetrics[$metric]['previous']) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Premium Dashboard JavaScript -->
    <script src="{{ asset('js/premium-analytics-dashboard.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js default configurations for glass theme
            Chart.defaults.color = 'rgba(255, 255, 255, 0.8)';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            Chart.defaults.backgroundColor = 'rgba(255, 255, 255, 0.05)';

            // Revenue Trend Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: @json($revenueData['labels']),
                    datasets: [{
                        label: 'Daily Revenue',
                        data: @json($revenueData['data']),
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
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                            },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: 'white',
                        }
                    }
                }
            });

            // Companies Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: @json(array_column($companiesPerformance, 'name')),
                    datasets: [{
                        label: 'Calls',
                        data: @json(array_column($companiesPerformance, 'calls')),
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }, {
                        label: 'Appointments',
                        data: @json(array_column($companiesPerformance, 'appointments')),
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Appointments Distribution Chart
            const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
            new Chart(appointmentsCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($appointmentsData['labels']),
                    datasets: [{
                        data: @json($appointmentsData['data']),
                        backgroundColor: @json($appointmentsData['colors']),
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
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });

            // Animated Counter Effect
            function animateCounters() {
                const counters = document.querySelectorAll('.metric-value[data-count]');
                
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-count'));
                    const duration = 2000; // 2 seconds
                    const increment = target / (duration / 16); // 60fps
                    let current = 0;
                    
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        
                        if (counter.textContent.includes('€')) {
                            counter.textContent = '€' + Math.floor(current).toLocaleString();
                        } else if (counter.textContent.includes('%')) {
                            counter.textContent = Math.floor(current) + '%';
                        } else {
                            counter.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 16);
                });
            }

            // Start counter animation
            setTimeout(animateCounters, 500);

            // Premium dashboard handles real-time updates
            // setInterval managed by PremiumAnalyticsDashboard class
        });

        // Listen for dashboard refresh events
        document.addEventListener('livewire:init', () => {
            Livewire.on('dashboard-refreshed', () => {
                // Refresh charts here if needed
                console.log('Dashboard refreshed');
            });
        });
    </script>
</x-filament-panels::page>