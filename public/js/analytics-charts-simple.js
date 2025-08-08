// Simple Analytics Charts - Direct approach
console.log('📊 Analytics Charts Simple loaded');

function createAnalyticsCharts() {
    console.log('🎨 Creating analytics charts (simple version)...');
    
    // Check if we have data
    if (!window.analyticsChartData) {
        console.log('⚠️ No chart data available');
        return;
    }
    
    const chartData = window.analyticsChartData;
    const heatmapData = window.analyticsHeatmapData || [];
    const callMetrics = window.analyticsCallMetrics || {};
    
    // Wait a bit for DOM to stabilize
    setTimeout(() => {
        // 1. Appointments Chart
        const appointmentsEl = document.getElementById('appointmentsChart');
        if (appointmentsEl && chartData.labels && chartData.appointments) {
            console.log('📊 Creating appointments chart...');
            try {
                new Chart(appointmentsEl.getContext('2d'), {
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
                console.error('❌ Appointments chart error:', e);
            }
        }
        
        // 2. Revenue Chart
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl && chartData.labels && chartData.revenue) {
            console.log('💰 Creating revenue chart...');
            try {
                new Chart(revenueEl.getContext('2d'), {
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
                console.error('❌ Revenue chart error:', e);
            }
        }
        
        // 3. Call Distribution
        const callDistEl = document.getElementById('callDistributionChart');
        if (callDistEl && callMetrics.inbound) {
            console.log('📞 Creating call distribution chart...');
            try {
                const inbound = callMetrics.inbound.total_calls || 0;
                const outbound = (callMetrics.outbound && callMetrics.outbound.total_calls) || 0;
                
                if (inbound > 0 || outbound > 0) {
                    new Chart(callDistEl.getContext('2d'), {
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
                console.error('❌ Call distribution error:', e);
            }
        }
        
        // 4. Calls Timeline
        const callsTimelineEl = document.getElementById('callsTimelineChart');
        if (callsTimelineEl && chartData.labels && chartData.calls) {
            console.log('📈 Creating calls timeline chart...');
            try {
                new Chart(callsTimelineEl.getContext('2d'), {
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
                console.error('❌ Calls timeline error:', e);
            }
        }
        
        // 5. Heatmap
        const heatmapEl = document.getElementById('heatmap');
        if (heatmapEl && heatmapData && heatmapData.length > 0) {
            console.log('🔥 Creating heatmap...');
            try {
                new ApexCharts(heatmapEl, {
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
                }).render();
                console.log('✅ Heatmap created');
            } catch (e) {
                console.error('❌ Heatmap error:', e);
            }
        }
        
        console.log('🎉 All charts created');
    }, 1000); // Wait 1 second for DOM to be ready
}

// Try multiple times to ensure libraries are loaded
function tryCreateCharts(attempt = 1) {
    console.log('🔄 Attempting to create charts (attempt ' + attempt + ')...');
    
    if (typeof Chart !== 'undefined' && typeof ApexCharts !== 'undefined') {
        createAnalyticsCharts();
    } else if (attempt < 10) {
        console.log('⏳ Libraries not ready, retrying in 500ms...');
        setTimeout(() => tryCreateCharts(attempt + 1), 500);
    } else {
        console.error('❌ Failed to load chart libraries after 5 seconds');
    }
}

// Start when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📄 DOM loaded, starting chart creation...');
        tryCreateCharts();
    });
} else {
    console.log('📄 DOM already loaded, starting chart creation...');
    tryCreateCharts();
}

// Also try after a delay
setTimeout(() => {
    console.log('⏰ Delayed chart creation attempt...');
    tryCreateCharts();
}, 2000);

// Expose for manual testing
window.manualCreateCharts = createAnalyticsCharts;