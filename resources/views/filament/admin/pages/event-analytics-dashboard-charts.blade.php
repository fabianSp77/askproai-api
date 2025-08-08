{{-- Charts JavaScript - wird am Ende der event-analytics-dashboard.blade.php eingebunden --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
(function() {
    'use strict';
    
    // Store chart instances globally
    window.analyticsCharts = window.analyticsCharts || {};
    
    function destroyExistingCharts() {
        Object.keys(window.analyticsCharts).forEach(key => {
            if (window.analyticsCharts[key]) {
                if (typeof window.analyticsCharts[key].destroy === 'function') {
                    window.analyticsCharts[key].destroy();
                } else if (typeof window.analyticsCharts[key].destroy === 'function') {
                    window.analyticsCharts[key].destroy();
                }
                delete window.analyticsCharts[key];
            }
        });
    }
    
    function initializeCharts() {
        console.log('🎨 Initializing Analytics Charts...');
        
        // Check if libraries are loaded
        if (typeof Chart === 'undefined' || typeof ApexCharts === 'undefined') {
            console.error('❌ Chart libraries not loaded! Retrying in 500ms...');
            setTimeout(initializeCharts, 500);
            return;
        }
        
        // Get data from PHP (check if variables exist)
        const companyId = {{ $companyId ?? 'null' }};
        const chartData = @json($chartData ?? []);
        const heatmapData = @json($heatmapData ?? []);
        const callMetrics = @json($callMetrics ?? []);
        
        console.log('📊 Data loaded:', {
            companyId: companyId,
            hasChartData: Object.keys(chartData).length > 0,
            hasHeatmapData: heatmapData.length > 0
        });
        
        // Only render charts if we have a company selected
        if (!companyId) {
            console.log('ℹ️ No company selected, skipping charts');
            return;
        }
        
        // Destroy existing charts before creating new ones
        destroyExistingCharts();
        
        // Small delay to ensure DOM is ready
        setTimeout(() => {
            // 1. Appointments Chart
            const appointmentsCanvas = document.getElementById('appointmentsChart');
            if (appointmentsCanvas && chartData.labels && chartData.appointments) {
                console.log('📊 Creating Appointments Chart');
                const ctx = appointmentsCanvas.getContext('2d');
                window.analyticsCharts.appointments = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Termine',
                            data: chartData.appointments,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            
            // 2. Revenue Chart
            const revenueCanvas = document.getElementById('revenueChart');
            if (revenueCanvas && chartData.labels && chartData.revenue) {
                console.log('💰 Creating Revenue Chart');
                const ctx = revenueCanvas.getContext('2d');
                window.analyticsCharts.revenue = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Umsatz',
                            data: chartData.revenue,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('de-DE') + ' €';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // 3. Call Distribution Chart
            const callDistCanvas = document.getElementById('callDistributionChart');
            if (callDistCanvas && callMetrics.inbound && callMetrics.outbound) {
                const inboundCalls = callMetrics.inbound.total_calls || 0;
                const outboundCalls = callMetrics.outbound.total_calls || 0;
                
                if (inboundCalls > 0 || outboundCalls > 0) {
                    console.log('📞 Creating Call Distribution Chart');
                    const ctx = callDistCanvas.getContext('2d');
                    window.analyticsCharts.callDist = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Eingehend', 'Ausgehend'],
                            datasets: [{
                                data: [inboundCalls, outboundCalls],
                                backgroundColor: [
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(59, 130, 246, 0.8)'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }
            }
            
            // 4. Calls Timeline Chart
            const callsTimelineCanvas = document.getElementById('callsTimelineChart');
            if (callsTimelineCanvas && chartData.labels && chartData.calls) {
                console.log('📈 Creating Calls Timeline Chart');
                const ctx = callsTimelineCanvas.getContext('2d');
                window.analyticsCharts.callsTimeline = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Anrufe',
                            data: chartData.calls,
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            
            // 5. Heatmap
            const heatmapElement = document.getElementById('heatmap');
            if (heatmapElement && heatmapData && heatmapData.length > 0) {
                console.log('🔥 Creating Heatmap');
                const heatmapOptions = {
                    series: heatmapData,
                    chart: {
                        height: 350,
                        type: 'heatmap',
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        heatmap: {
                            shadeIntensity: 0.5,
                            colorScale: {
                                ranges: [
                                    { from: 0, to: 0, name: 'Keine', color: '#E5E7EB' },
                                    { from: 1, to: 3, name: 'Wenig', color: '#DBEAFE' },
                                    { from: 4, to: 6, name: 'Mittel', color: '#93C5FD' },
                                    { from: 7, to: 10, name: 'Viel', color: '#3B82F6' }
                                ]
                            }
                        }
                    },
                    xaxis: {
                        type: 'category',
                        categories: ['8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00']
                    },
                    title: {
                        text: undefined,
                        style: { fontSize: '11px' }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' Termine';
                            }
                        }
                    }
                };
                
                window.analyticsCharts.heatmap = new ApexCharts(heatmapElement, heatmapOptions);
                window.analyticsCharts.heatmap.render();
            }
            
            console.log('✅ Charts initialization complete!');
        }, 100);
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCharts);
    } else {
        // DOM is already loaded
        initializeCharts();
    }
    
    // Re-initialize after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        setTimeout(initializeCharts, 200);
    });
    
    document.addEventListener('livewire:load', function() {
        Livewire.hook('message.processed', function() {
            setTimeout(initializeCharts, 200);
        });
    });
    
    // Also listen for custom events
    window.addEventListener('charts:refresh', initializeCharts);
})();
</script>
@endpush