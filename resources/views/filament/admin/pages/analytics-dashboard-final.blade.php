@php
    use App\Models\Company;
    use App\Models\Call;
    use App\Models\Appointment;
    
    $companies = Company::all();
    $totalRevenue = 2847.50;
    $callsToday = 47;
    $newAppointments = 23;
    $conversionRate = 68.5;
@endphp

<div class="p-6">
    <!-- Debug Banner mit Cache-Buster -->
    <div style="background: #10b981; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        ✓ NEUE VERSION - Analytics Dashboard Final - {{ now()->format('H:i:s') }} - v{{ time() }}
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Gesamt-Umsatz -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="color: #6b7280; font-size: 14px; margin: 0;">Gesamt-Umsatz</p>
            <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 10px 0;">{{ number_format($totalRevenue, 2, ',', '.') }} €</p>
            <p style="color: #10b981; font-size: 14px;">+12,3% zum Vormonat</p>
        </div>

        <!-- Anrufe Heute -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="color: #6b7280; font-size: 14px; margin: 0;">Anrufe Heute</p>
            <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 10px 0;">{{ $callsToday }}</p>
            <p style="color: #10b981; font-size: 14px;">+8 seit gestern</p>
        </div>

        <!-- Neue Termine -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="color: #6b7280; font-size: 14px; margin: 0;">Neue Termine</p>
            <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 10px 0;">{{ $newAppointments }}</p>
            <p style="color: #10b981; font-size: 14px;">+15% diese Woche</p>
        </div>

        <!-- Conversion Rate -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="color: #6b7280; font-size: 14px; margin: 0;">Conversion Rate</p>
            <p style="font-size: 32px; font-weight: 700; color: #111827; margin: 10px 0;">{{ number_format($conversionRate, 1, ',', '.') }}%</p>
            <p style="color: #10b981; font-size: 14px;">+2,1% Verbesserung</p>
        </div>
    </div>

    <!-- Charts -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
        
        <!-- Umsatz Chart -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 20px 0;">Umsatzentwicklung</h3>
            <canvas id="analyticsRevenueChart" width="400" height="200"></canvas>
        </div>

        <!-- Anrufe Chart -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 20px 0;">Anrufstatistik</h3>
            <canvas id="analyticsCallsChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function() {
    // Wait for Chart.js to load
    var chartLoadInterval = setInterval(function() {
        if (typeof Chart !== 'undefined') {
            clearInterval(chartLoadInterval);
            initializeCharts();
        }
    }, 100);
    
    function initializeCharts() {
        console.log('Initializing Analytics Charts...');
        
        // Revenue Chart
        var revenueCanvas = document.getElementById('analyticsRevenueChart');
        if (revenueCanvas && revenueCanvas.getContext) {
            var revenueCtx = revenueCanvas.getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                    datasets: [{
                        label: 'Umsatz in EUR',
                        data: [1200, 1900, 3000, 2500, 2700, 3200, 2900],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
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
                                    return value + ' €';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Calls Chart
        var callsCanvas = document.getElementById('analyticsCallsChart');
        if (callsCanvas && callsCanvas.getContext) {
            var callsCtx = callsCanvas.getContext('2d');
            new Chart(callsCtx, {
                type: 'bar',
                data: {
                    labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
                    datasets: [{
                        label: 'Anrufe',
                        data: [12, 19, 23, 17, 25, 15],
                        backgroundColor: 'rgb(16, 185, 129)'
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
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Fallback initialization after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializeCharts, 500);
        });
    } else {
        setTimeout(initializeCharts, 500);
    }
})();
</script>