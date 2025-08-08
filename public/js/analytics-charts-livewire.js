// Analytics Charts with Livewire Integration
console.log('📊 Analytics Charts Livewire loaded');

// Store chart instances globally to manage lifecycle
window.analyticsChartInstances = {
    appointments: null,
    revenue: null,
    callDistribution: null,
    callsTimeline: null,
    heatmap: null
};

// Destroy existing charts
function destroyExistingCharts() {
    console.log('🗑️ Destroying existing charts...');
    
    // Destroy Chart.js charts
    ['appointments', 'revenue', 'callDistribution', 'callsTimeline'].forEach(key => {
        if (window.analyticsChartInstances[key]) {
            try {
                window.analyticsChartInstances[key].destroy();
                window.analyticsChartInstances[key] = null;
                console.log(`✅ Destroyed ${key} chart`);
            } catch (e) {
                console.error(`❌ Error destroying ${key}:`, e);
            }
        }
    });
    
    // Destroy ApexCharts heatmap
    if (window.analyticsChartInstances.heatmap) {
        try {
            window.analyticsChartInstances.heatmap.destroy();
            window.analyticsChartInstances.heatmap = null;
            console.log('✅ Destroyed heatmap');
        } catch (e) {
            console.error('❌ Error destroying heatmap:', e);
        }
    }
}

// Create all charts
function createAnalyticsCharts() {
    console.log('🎨 Creating analytics charts with Livewire support...');
    
    // First destroy any existing charts
    destroyExistingCharts();
    
    // Check if we have data
    const chartData = window.analyticsChartData || {};
    const heatmapData = window.analyticsHeatmapData || [];
    const callMetrics = window.analyticsCallMetrics || {};
    
    console.log('📊 Data available:', {
        chartData: Object.keys(chartData),
        heatmapEntries: heatmapData.length,
        hasCallMetrics: Object.keys(callMetrics).length > 0
    });
    
    // 1. Appointments Chart
    const appointmentsEl = document.getElementById('appointmentsChart');
    if (appointmentsEl && chartData.labels && chartData.appointments) {
        try {
            const ctx = appointmentsEl.getContext('2d');
            window.analyticsChartInstances.appointments = new Chart(ctx, {
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
                    animation: { duration: 500 },
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
        try {
            const ctx = revenueEl.getContext('2d');
            window.analyticsChartInstances.revenue = new Chart(ctx, {
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
                    animation: { duration: 500 },
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
        try {
            const inbound = callMetrics.inbound.total_calls || 0;
            const outbound = (callMetrics.outbound && callMetrics.outbound.total_calls) || 0;
            
            if (inbound > 0 || outbound > 0) {
                const ctx = callDistEl.getContext('2d');
                window.analyticsChartInstances.callDistribution = new Chart(ctx, {
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
                        animation: { duration: 500 },
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
        try {
            const ctx = callsTimelineEl.getContext('2d');
            window.analyticsChartInstances.callsTimeline = new Chart(ctx, {
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
                    animation: { duration: 500 },
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
        try {
            // Clear any existing content
            heatmapEl.innerHTML = '';
            
            window.analyticsChartInstances.heatmap = new ApexCharts(heatmapEl, {
                series: heatmapData,
                chart: {
                    height: 350,
                    type: 'heatmap',
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 500 }
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
            });
            window.analyticsChartInstances.heatmap.render();
            console.log('✅ Heatmap created');
        } catch (e) {
            console.error('❌ Heatmap error:', e);
        }
    }
    
    console.log('🎉 All charts creation attempted');
}

// Initialize charts when libraries are ready
function initializeCharts() {
    console.log('🚀 Initializing charts...');
    
    if (typeof Chart === 'undefined' || typeof ApexCharts === 'undefined') {
        console.log('⏳ Waiting for chart libraries...');
        setTimeout(initializeCharts, 100);
        return;
    }
    
    // Check if we have a company selected
    const hasCompany = window.analyticsChartData && Object.keys(window.analyticsChartData).length > 0;
    if (!hasCompany) {
        console.log('⚠️ No company data available, skipping chart creation');
        return;
    }
    
    createAnalyticsCharts();
}

// Listen for Livewire updates
if (window.Livewire) {
    console.log('🔌 Livewire detected, setting up listeners...');
    
    // Listen for Livewire navigation events
    document.addEventListener('livewire:navigated', () => {
        console.log('📍 Livewire navigated event');
        setTimeout(initializeCharts, 500);
    });
    
    // Listen for Livewire content updates
    Livewire.hook('message.processed', (message, component) => {
        console.log('📨 Livewire message processed');
        
        // Check if this is our analytics component
        if (component && component.fingerprint && component.fingerprint.name && 
            component.fingerprint.name.includes('event-analytics-dashboard')) {
            console.log('📊 Analytics dashboard updated, recreating charts...');
            setTimeout(initializeCharts, 100);
        }
    });
    
    // Listen for morph completion
    Livewire.hook('element.updated', (el, component) => {
        // Check if any chart container was updated
        if (el.id && (el.id.includes('Chart') || el.id === 'heatmap')) {
            console.log(`📈 Chart container ${el.id} updated`);
            setTimeout(initializeCharts, 100);
        }
    });
}

// Alpine.js integration for better Livewire compatibility
if (window.Alpine) {
    console.log('🏔️ Alpine.js detected');
    
    document.addEventListener('alpine:initialized', () => {
        console.log('🏔️ Alpine initialized, creating charts...');
        setTimeout(initializeCharts, 100);
    });
}

// Standard DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📄 DOM loaded');
        initializeCharts();
    });
} else {
    console.log('📄 DOM already loaded');
    initializeCharts();
}

// Fallback initialization
setTimeout(() => {
    console.log('⏰ Fallback initialization');
    initializeCharts();
}, 2000);

// Expose functions for debugging
window.debugAnalyticsCharts = {
    create: createAnalyticsCharts,
    destroy: destroyExistingCharts,
    init: initializeCharts,
    instances: window.analyticsChartInstances
};

console.log('💡 Debug: window.debugAnalyticsCharts.create() to manually create charts');