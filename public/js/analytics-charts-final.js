// Final Analytics Charts Solution - Works with Livewire Updates
console.log('🚀 Analytics Charts Final Solution loaded');

(function() {
    'use strict';
    
    let chartInstances = {};
    let initAttempts = 0;
    const MAX_INIT_ATTEMPTS = 30;
    
    // Destroy all charts
    function destroyAllCharts() {
        console.log('🗑️ Destroying existing charts...');
        Object.keys(chartInstances).forEach(key => {
            try {
                if (chartInstances[key]) {
                    if (typeof chartInstances[key].destroy === 'function') {
                        chartInstances[key].destroy();
                    }
                    delete chartInstances[key];
                }
            } catch (e) {
                console.error(`Error destroying ${key}:`, e);
            }
        });
    }
    
    // Wait for libraries and data
    function waitForDependencies(callback) {
        if (typeof Chart === 'undefined' || typeof ApexCharts === 'undefined') {
            console.log('⏳ Waiting for chart libraries...');
            setTimeout(() => waitForDependencies(callback), 100);
            return;
        }
        
        // Libraries are ready
        callback();
    }
    
    // Create all charts
    function createCharts() {
        console.log('📊 Attempting to create charts...');
        
        // Always use window data (simpler and more reliable)
        const chartData = window.analyticsChartData;
        const heatmapData = window.analyticsHeatmapData;
        const callMetrics = window.analyticsCallMetrics;
        
        console.log('📦 Data from window:', {
            hasChartData: chartData && Object.keys(chartData).length > 0,
            hasHeatmap: heatmapData && Array.isArray(heatmapData) && heatmapData.length > 0,
            hasMetrics: callMetrics && Object.keys(callMetrics).length > 0,
            dataKeys: chartData ? Object.keys(chartData) : []
        });
        
        // Check if we have data
        if (!chartData || Object.keys(chartData).length === 0) {
            console.log('⚠️ No chart data available');
            return false;
        }
        
        // Destroy existing charts first
        destroyAllCharts();
        
        let chartsCreated = 0;
        
        // 1. Appointments Chart
        const appointmentsEl = document.getElementById('appointmentsChart');
        if (appointmentsEl && chartData.labels && chartData.appointments) {
            try {
                const ctx = appointmentsEl.getContext('2d');
                chartInstances.appointments = new Chart(ctx, {
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
                chartsCreated++;
            } catch (e) {
                console.error('❌ Appointments chart error:', e);
            }
        }
        
        // 2. Revenue Chart
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl && chartData.labels && chartData.revenue) {
            try {
                const ctx = revenueEl.getContext('2d');
                chartInstances.revenue = new Chart(ctx, {
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
                chartsCreated++;
            } catch (e) {
                console.error('❌ Revenue chart error:', e);
            }
        }
        
        // 3. Call Distribution
        const callDistEl = document.getElementById('callDistributionChart');
        if (callDistEl && callMetrics && callMetrics.inbound) {
            try {
                const inbound = callMetrics.inbound.total_calls || 0;
                const outbound = (callMetrics.outbound && callMetrics.outbound.total_calls) || 0;
                
                if (inbound > 0 || outbound > 0) {
                    const ctx = callDistEl.getContext('2d');
                    chartInstances.callDistribution = new Chart(ctx, {
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
                    chartsCreated++;
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
                chartInstances.callsTimeline = new Chart(ctx, {
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
                chartsCreated++;
            } catch (e) {
                console.error('❌ Calls timeline error:', e);
            }
        }
        
        // 5. Heatmap
        const heatmapEl = document.getElementById('heatmap');
        if (heatmapEl && heatmapData && heatmapData.length > 0) {
            try {
                heatmapEl.innerHTML = '';
                chartInstances.heatmap = new ApexCharts(heatmapEl, {
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
                });
                chartInstances.heatmap.render();
                console.log('✅ Heatmap created');
                chartsCreated++;
            } catch (e) {
                console.error('❌ Heatmap error:', e);
            }
        }
        
        console.log(`📈 Created ${chartsCreated} charts`);
        return chartsCreated > 0;
    }
    
    // Initialize charts with retry
    function initializeCharts() {
        initAttempts++;
        console.log(`🔄 Initialization attempt ${initAttempts}/${MAX_INIT_ATTEMPTS}`);
        
        waitForDependencies(() => {
            // Try to create charts
            const success = createCharts();
            
            // If no charts created and we haven't exceeded attempts, retry
            if (!success && initAttempts < MAX_INIT_ATTEMPTS) {
                setTimeout(initializeCharts, 500);
            }
        });
    }
    
    // Livewire integration - Most important part!
    if (window.Livewire) {
        console.log('🔌 Livewire detected, setting up hooks...');
        
        // Listen for when Livewire finishes updating the DOM
        Livewire.hook('message.processed', (message, component) => {
            // Check if this is our analytics dashboard
            if (component && component.fingerprint && 
                component.fingerprint.name && 
                component.fingerprint.name.includes('event-analytics-dashboard')) {
                
                console.log('📨 Analytics dashboard updated via Livewire');
                
                // Wait for DOM to settle
                setTimeout(() => {
                    initAttempts = 0; // Reset attempts
                    initializeCharts();
                }, 500);
            }
        });
        
        // Also listen for morph completion
        Livewire.hook('morph.updated', (el, component) => {
            // Check if chart containers were updated
            if (el.querySelector && (el.querySelector('#appointmentsChart') || 
                el.querySelector('#revenueChart') || 
                el.querySelector('#heatmap'))) {
                console.log('📊 Chart container morphed, recreating charts...');
                setTimeout(() => {
                    initAttempts = 0;
                    initializeCharts();
                }, 300);
            }
        });
    }
    
    // Initial load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCharts);
    } else {
        // DOM already loaded, wait a bit for Livewire to initialize
        setTimeout(initializeCharts, 500);
    }
    
    // Expose for debugging
    window.analyticsChartDebug = {
        create: createCharts,
        destroy: destroyAllCharts,
        init: initializeCharts,
        instances: chartInstances
    };
    
    console.log('💡 Debug commands available:');
    console.log('  window.analyticsChartDebug.create() - Create charts');
    console.log('  window.analyticsChartDebug.destroy() - Destroy charts');
    console.log('  window.analyticsChartDebug.init() - Initialize');
    
})();