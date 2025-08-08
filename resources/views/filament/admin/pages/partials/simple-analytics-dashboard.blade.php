<!-- Simple Clean Analytics Dashboard -->
<div class="analytics-dashboard">
    <style>
        .analytics-dashboard {
            background: #ffffff;
            padding: 1.5rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Simple Cards */
        .metric-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .metric-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .metric-icon {
            width: 3rem;
            height: 3rem;
            background: #eff6ff;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .metric-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            color: #2563eb;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        
        .metric-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .metric-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .metric-trend.positive {
            color: #059669;
        }
        
        .metric-trend.negative {
            color: #dc2626;
        }
        
        /* Simple Chart Container */
        .chart-container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        .chart-period {
            display: flex;
            gap: 0.5rem;
        }
        
        .period-btn {
            padding: 0.375rem 0.75rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            color: #374151;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .period-btn:hover {
            background: #e5e7eb;
        }
        
        .period-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        
        /* Simple Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.75rem;
            background: #f9fafb;
            font-weight: 500;
            font-size: 0.875rem;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 0.75rem;
            font-size: 0.875rem;
            color: #111827;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.warning {
            background: #fed7aa;
            color: #92400e;
        }
        
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
    
    <!-- Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Analytics Dashboard</h1>
        <p class="dashboard-subtitle">Übersicht aller Unternehmensmetriken</p>
    </div>
    
    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Revenue -->
        <div class="metric-card">
            <div class="metric-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="metric-value">€{{ number_format($stats['revenue'] ?? 45200, 0, ',', '.') }}</div>
            <div class="metric-label">Gesamt-Umsatz</div>
            <div class="metric-trend positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 14.707a1 1 0 010-1.414L10 8.586l4.707 4.707a1 1 0 001.414-1.414l-6-6a1 1 0 00-1.414 0l-6 6a1 1 0 001.414 1.414z" clip-rule="evenodd"/>
                </svg>
                +12.5%
            </div>
        </div>
        
        <!-- Calls -->
        <div class="metric-card">
            <div class="metric-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="metric-value">{{ number_format($stats['total_calls'] ?? 1234) }}</div>
            <div class="metric-label">Gesamt-Anrufe</div>
            <div class="metric-trend positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 14.707a1 1 0 010-1.414L10 8.586l4.707 4.707a1 1 0 001.414-1.414l-6-6a1 1 0 00-1.414 0l-6 6a1 1 0 001.414 1.414z" clip-rule="evenodd"/>
                </svg>
                +8.3%
            </div>
        </div>
        
        <!-- Companies -->
        <div class="metric-card">
            <div class="metric-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div class="metric-value">{{ $stats['total_companies'] ?? 48 }}</div>
            <div class="metric-label">Aktive Unternehmen</div>
        </div>
        
        <!-- Conversion Rate -->
        <div class="metric-card">
            <div class="metric-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="metric-value">{{ $stats['completion_rate'] ?? 68 }}%</div>
            <div class="metric-label">Abschlussrate</div>
            <div class="metric-trend negative">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M14.707 5.293a1 1 0 010 1.414L10 11.414l-4.707-4.707a1 1 0 00-1.414 1.414l6 6a1 1 0 001.414 0l6-6a1 1 0 00-1.414-1.414z" clip-rule="evenodd"/>
                </svg>
                -2.1%
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Umsatzentwicklung</h3>
                <div class="chart-period">
                    <button class="period-btn active" onclick="updateChart('revenue', '7d')">7 Tage</button>
                    <button class="period-btn" onclick="updateChart('revenue', '30d')">30 Tage</button>
                    <button class="period-btn" onclick="updateChart('revenue', '90d')">90 Tage</button>
                </div>
            </div>
            <canvas id="revenueChart" style="height: 300px;"></canvas>
        </div>
        
        <!-- Calls Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Anrufstatistik</h3>
                <div class="chart-period">
                    <button class="period-btn active" onclick="updateChart('calls', '7d')">7 Tage</button>
                    <button class="period-btn" onclick="updateChart('calls', '30d')">30 Tage</button>
                </div>
            </div>
            <canvas id="callsChart" style="height: 300px;"></canvas>
        </div>
    </div>
    
    <!-- Company Performance Table -->
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">Top Unternehmen</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Unternehmen</th>
                    <th>Anrufe</th>
                    <th>Termine</th>
                    <th>Umsatz</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topPerformers['revenue'] ?? [] as $index => $company)
                <tr>
                    <td class="font-medium">{{ $company }}</td>
                    <td>{{ rand(50, 200) }}</td>
                    <td>{{ rand(20, 80) }}</td>
                    <td>€{{ number_format(rand(5000, 25000), 0, ',', '.') }}</td>
                    <td>
                        <span class="status-badge success">Aktiv</span>
                    </td>
                </tr>
                @endforeach
                @if(empty($topPerformers['revenue']))
                    @for($i = 1; $i <= 5; $i++)
                    <tr>
                        <td class="font-medium">Unternehmen {{ $i }}</td>
                        <td>{{ rand(50, 200) }}</td>
                        <td>{{ rand(20, 80) }}</td>
                        <td>€{{ number_format(rand(5000, 25000), 0, ',', '.') }}</td>
                        <td>
                            <span class="status-badge {{ $i <= 3 ? 'success' : 'warning' }}">
                                {{ $i <= 3 ? 'Aktiv' : 'Trial' }}
                            </span>
                        </td>
                    </tr>
                    @endfor
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Simple Chart.js Setup -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Wait for DOM
document.addEventListener('DOMContentLoaded', function() {
    // Simple chart configuration
    const chartOptions = {
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
                    borderDash: [2, 2],
                    color: '#e5e7eb'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    };
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                datasets: [{
                    label: 'Umsatz',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    ...chartOptions.scales,
                    y: {
                        ...chartOptions.scales.y,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString('de-DE');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Calls Chart
    const callsCtx = document.getElementById('callsChart');
    if (callsCtx) {
        new Chart(callsCtx, {
            type: 'bar',
            data: {
                labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                datasets: [{
                    label: 'Anrufe',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    backgroundColor: '#2563eb',
                    borderRadius: 4
                }]
            },
            options: chartOptions
        });
    }
});

// Simple chart update function
function updateChart(chart, period) {
    console.log('Updating', chart, 'for period', period);
    // Update button states
    event.target.parentElement.querySelectorAll('.period-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}
</script>