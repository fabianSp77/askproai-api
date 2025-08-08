// Analytics Dashboard Charts
window.AnalyticsCharts = {
    charts: {},
    
    init() {
        console.log('📊 AnalyticsCharts.init() called');
        this.waitForLibraries();
    },
    
    waitForLibraries() {
        if (typeof Chart === 'undefined' || typeof ApexCharts === 'undefined') {
            console.log('⏳ Waiting for chart libraries...');
            setTimeout(() => this.waitForLibraries(), 100);
            return;
        }
        console.log('✅ Chart libraries loaded');
        this.waitForElements();
    },
    
    waitForElements() {
        // Check if at least one chart container exists
        const hasContainers = document.getElementById('appointmentsChart') || 
                             document.getElementById('revenueChart') ||
                             document.getElementById('callDistributionChart') ||
                             document.getElementById('callsTimelineChart') ||
                             document.getElementById('heatmap');
        
        if (!hasContainers) {
            console.log('⏳ Waiting for chart containers...');
            setTimeout(() => this.waitForElements(), 100);
            return;
        }
        
        console.log('✅ Chart containers found');
        this.createCharts();
    },
    
    destroyCharts() {
        Object.keys(this.charts).forEach(key => {
            if (this.charts[key] && typeof this.charts[key].destroy === 'function') {
                this.charts[key].destroy();
                console.log('🗑️ Destroyed chart:', key);
            }
            delete this.charts[key];
        });
    },
    
    createCharts() {
        console.log('🎨 Creating charts...');
        
        // Destroy existing charts first
        this.destroyCharts();
        
        // Get data from window object (will be set by PHP)
        const chartData = window.analyticsChartData || {};
        const heatmapData = window.analyticsHeatmapData || [];
        const callMetrics = window.analyticsCallMetrics || {};
        
        console.log('📊 Data check:', {
            hasChartData: Object.keys(chartData).length > 0,
            hasHeatmapData: heatmapData.length > 0,
            hasCallMetrics: Object.keys(callMetrics).length > 0
        });
        
        // 1. Appointments Chart
        const appointmentsEl = document.getElementById('appointmentsChart');
        if (appointmentsEl && chartData.labels && chartData.appointments) {
            try {
                const ctx = appointmentsEl.getContext('2d');
                this.charts.appointments = new Chart(ctx, {
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
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
                console.log('✅ Appointments chart created');
            } catch (e) {
                console.error('❌ Appointments chart failed:', e);
            }
        }
        
        // 2. Revenue Chart
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl && chartData.labels && chartData.revenue) {
            try {
                const ctx = revenueEl.getContext('2d');
                this.charts.revenue = new Chart(ctx, {
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
                        plugins: { legend: { display: false } },
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
                console.log('✅ Revenue chart created');
            } catch (e) {
                console.error('❌ Revenue chart failed:', e);
            }
        }
        
        // 3. Call Distribution
        const callDistEl = document.getElementById('callDistributionChart');
        if (callDistEl && callMetrics.inbound) {
            try {
                const inbound = callMetrics.inbound.total_calls || 0;
                const outbound = (callMetrics.outbound && callMetrics.outbound.total_calls) || 0;
                
                if (inbound > 0 || outbound > 0) {
                    const ctx = callDistEl.getContext('2d');
                    this.charts.callDist = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Eingehend', 'Ausgehend'],
                            datasets: [{
                                data: [inbound, outbound],
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
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                    console.log('✅ Call distribution chart created');
                }
            } catch (e) {
                console.error('❌ Call distribution failed:', e);
            }
        }
        
        // 4. Calls Timeline
        const callsTimelineEl = document.getElementById('callsTimelineChart');
        if (callsTimelineEl && chartData.labels && chartData.calls) {
            try {
                const ctx = callsTimelineEl.getContext('2d');
                this.charts.callsTimeline = new Chart(ctx, {
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
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
                console.log('✅ Calls timeline chart created');
            } catch (e) {
                console.error('❌ Calls timeline failed:', e);
            }
        }
        
        // 5. Heatmap
        const heatmapEl = document.getElementById('heatmap');
        if (heatmapEl && heatmapData && heatmapData.length > 0) {
            try {
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
                                    { from: 4, to: 7, name: 'Mittel', color: '#93C5FD' },
                                    { from: 8, to: 12, name: 'Viel', color: '#3B82F6' }
                                ]
                            }
                        }
                    },
                    xaxis: {
                        type: 'category',
                        categories: ['8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00']
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' Termine';
                            }
                        }
                    }
                };
                
                this.charts.heatmap = new ApexCharts(heatmapEl, heatmapOptions);
                this.charts.heatmap.render();
                console.log('✅ Heatmap created');
            } catch (e) {
                console.error('❌ Heatmap failed:', e);
            }
        }
        
        console.log('🎉 Chart creation complete');
    },
    
    refresh() {
        console.log('🔄 Refreshing charts...');
        this.init();
    }
};

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 DOM loaded, initializing analytics charts...');
    window.AnalyticsCharts.init();
});

// Livewire integration
if (window.Livewire) {
    document.addEventListener('livewire:navigated', () => {
        console.log('🔄 Livewire navigated, refreshing charts...');
        setTimeout(() => window.AnalyticsCharts.refresh(), 500);
    });
    
    Livewire.hook('message.processed', () => {
        console.log('🔄 Livewire message processed, refreshing charts...');
        setTimeout(() => window.AnalyticsCharts.refresh(), 500);
    });
}