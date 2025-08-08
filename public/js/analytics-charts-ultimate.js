// Ultimate Analytics Charts - Bulletproof Implementation
console.log('🚀 Analytics Charts Ultimate loaded');

(function() {
    'use strict';
    
    // Global state management
    const ChartManager = {
        instances: {},
        isInitialized: false,
        retryCount: 0,
        maxRetries: 20,
        
        // Check if libraries are loaded
        librariesReady() {
            return typeof Chart !== 'undefined' && typeof ApexCharts !== 'undefined';
        },
        
        // Check if we have valid data
        hasValidData() {
            return window.analyticsChartData && 
                   Object.keys(window.analyticsChartData).length > 0;
        },
        
        // Destroy all existing charts
        destroyAll() {
            console.log('🗑️ Destroying all charts...');
            
            Object.keys(this.instances).forEach(key => {
                try {
                    if (this.instances[key]) {
                        if (typeof this.instances[key].destroy === 'function') {
                            this.instances[key].destroy();
                        }
                        delete this.instances[key];
                        console.log(`✅ Destroyed ${key}`);
                    }
                } catch (e) {
                    console.error(`❌ Error destroying ${key}:`, e);
                }
            });
        },
        
        // Create a single chart with error handling
        createChart(id, config, type = 'chartjs') {
            try {
                const element = document.getElementById(id);
                if (!element) {
                    console.warn(`⚠️ Element ${id} not found`);
                    return null;
                }
                
                // Destroy existing chart if any
                if (this.instances[id]) {
                    if (typeof this.instances[id].destroy === 'function') {
                        this.instances[id].destroy();
                    }
                    delete this.instances[id];
                }
                
                // Create new chart
                if (type === 'chartjs') {
                    const ctx = element.getContext('2d');
                    this.instances[id] = new Chart(ctx, config);
                } else if (type === 'apex') {
                    element.innerHTML = ''; // Clear content
                    this.instances[id] = new ApexCharts(element, config);
                    this.instances[id].render();
                }
                
                console.log(`✅ Created ${id}`);
                return this.instances[id];
            } catch (e) {
                console.error(`❌ Failed to create ${id}:`, e);
                return null;
            }
        },
        
        // Create all charts
        createAllCharts() {
            console.log('📊 Creating all charts...');
            
            const data = window.analyticsChartData || {};
            const heatmap = window.analyticsHeatmapData || [];
            const metrics = window.analyticsCallMetrics || {};
            
            // 1. Appointments Bar Chart
            if (data.labels && data.appointments) {
                this.createChart('appointmentsChart', {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Termine',
                            data: data.appointments,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 750 },
                        plugins: { 
                            legend: { display: false },
                            tooltip: { enabled: true }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                grid: { display: true }
                            }
                        }
                    }
                });
            }
            
            // 2. Revenue Line Chart
            if (data.labels && data.revenue) {
                this.createChart('revenueChart', {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Umsatz',
                            data: data.revenue,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 750 },
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toLocaleString('de-DE') + ' €';
                                    }
                                }
                            }
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
            
            // 3. Call Distribution Doughnut
            if (metrics.inbound || metrics.outbound) {
                const inbound = (metrics.inbound && metrics.inbound.total_calls) || 0;
                const outbound = (metrics.outbound && metrics.outbound.total_calls) || 0;
                
                if (inbound > 0 || outbound > 0) {
                    this.createChart('callDistributionChart', {
                        type: 'doughnut',
                        data: {
                            labels: ['Eingehend', 'Ausgehend'],
                            datasets: [{
                                data: [inbound, outbound],
                                backgroundColor: [
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(59, 130, 246, 0.8)'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: { duration: 750 },
                            plugins: { 
                                legend: { 
                                    position: 'bottom',
                                    labels: { padding: 15 }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // 4. Calls Timeline
            if (data.labels && data.calls) {
                this.createChart('callsTimelineChart', {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Anrufe',
                            data: data.calls,
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 750 },
                        plugins: { 
                            legend: { display: false },
                            tooltip: { enabled: true }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
            
            // 5. Heatmap
            if (heatmap && heatmap.length > 0) {
                this.createChart('heatmap', {
                    series: heatmap,
                    chart: {
                        height: 350,
                        type: 'heatmap',
                        toolbar: { show: false },
                        animations: { 
                            enabled: true, 
                            speed: 800,
                            animateGradually: { enabled: true }
                        }
                    },
                    plotOptions: {
                        heatmap: {
                            shadeIntensity: 0.5,
                            radius: 0,
                            useFillColorAsStroke: true,
                            colorScale: {
                                ranges: [
                                    { from: 0, to: 0, name: 'Keine', color: '#E5E7EB' },
                                    { from: 1, to: 3, name: 'Wenig', color: '#DBEAFE' },
                                    { from: 4, to: 7, name: 'Mittel', color: '#93C5FD' },
                                    { from: 8, to: 12, name: 'Viel', color: '#3B82F6' },
                                    { from: 13, to: 999, name: 'Sehr viel', color: '#1E40AF' }
                                ]
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        type: 'category',
                        categories: ['8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00']
                    },
                    yaxis: {
                        reversed: false
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + ' Termine';
                            }
                        }
                    }
                }, 'apex');
            }
            
            console.log('✅ Chart creation complete');
            this.isInitialized = true;
        },
        
        // Initialize with retry logic
        initialize() {
            console.log(`🔄 Initialize attempt ${this.retryCount + 1}/${this.maxRetries}`);
            
            if (!this.librariesReady()) {
                if (this.retryCount < this.maxRetries) {
                    this.retryCount++;
                    setTimeout(() => this.initialize(), 250);
                } else {
                    console.error('❌ Failed to load chart libraries after maximum retries');
                }
                return;
            }
            
            if (!this.hasValidData()) {
                console.log('⚠️ No chart data available');
                return;
            }
            
            // Wait a bit for DOM to stabilize
            setTimeout(() => {
                this.createAllCharts();
            }, 100);
        },
        
        // Refresh charts (for Livewire updates)
        refresh() {
            console.log('🔄 Refreshing charts...');
            this.retryCount = 0;
            this.initialize();
        }
    };
    
    // Livewire Integration
    if (window.Livewire) {
        console.log('🔌 Setting up Livewire hooks...');
        
        // Hook into component initialization
        Livewire.hook('component.initialized', (component) => {
            if (component.fingerprint && component.fingerprint.name && 
                component.fingerprint.name.includes('event-analytics-dashboard')) {
                console.log('📊 Analytics component initialized');
                setTimeout(() => ChartManager.refresh(), 500);
            }
        });
        
        // Hook into message processing
        Livewire.hook('message.processed', (message, component) => {
            if (component && component.fingerprint && component.fingerprint.name && 
                component.fingerprint.name.includes('event-analytics-dashboard')) {
                console.log('📨 Analytics component updated');
                setTimeout(() => ChartManager.refresh(), 300);
            }
        });
    }
    
    // Alpine.js Integration
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', () => {
            console.log('🏔️ Alpine initialized');
            setTimeout(() => ChartManager.initialize(), 200);
        });
    }
    
    // DOM Ready
    function onDOMReady() {
        console.log('📄 DOM Ready - Starting initialization');
        ChartManager.initialize();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDOMReady);
    } else {
        onDOMReady();
    }
    
    // Fallback initialization
    setTimeout(() => {
        if (!ChartManager.isInitialized) {
            console.log('⏰ Fallback initialization');
            ChartManager.initialize();
        }
    }, 3000);
    
    // Expose for debugging
    window.analyticsChartManager = ChartManager;
    console.log('💡 Debug: window.analyticsChartManager.refresh() to manually refresh charts');
    
})();