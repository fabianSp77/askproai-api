<x-filament-panels::page>
    <div class="space-y-6" 
         x-data="analyticsCharts"
         x-init="$nextTick(() => init())">
        <!-- Filter Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
            {{ $this->form }}
        </div>
        
        @if(!$companyId && auth()->user()->hasRole(['Super Admin', 'super_admin']))
            <!-- Gesamt-Übersicht für alle Unternehmen -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    📊 Gesamt-Übersicht aller Unternehmen
                </h2>
                
                <!-- Hauptmetriken -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Aktive Unternehmen</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_companies'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Termine</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_appointments'] ?? 0) }}</div>
                        <div class="text-xs text-green-600 mt-1">{{ $stats['completion_rate'] ?? 0 }}% abgeschlossen</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Anrufe</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_calls'] ?? 0) }}</div>
                        <div class="text-xs text-blue-600 mt-1">{{ $stats['call_success_rate'] ?? 0 }}% erfolgreich</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Umsatz</div>
                        <div class="text-2xl font-bold text-green-600">{{ number_format($stats['revenue'] ?? 0, 2, ',', '.') }} €</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Ø {{ number_format($stats['avg_revenue_per_appointment'] ?? 0, 2, ',', '.') }} €/Termin</div>
                    </div>
                </div>
            </div>
        @elseif($companyId)
            <!-- Set data for Alpine to use -->
            <div x-show="false" 
                 x-data="{
                     chartData: @js($chartData ?? []),
                     heatmapData: @js($heatmapData ?? []),
                     callMetrics: @js($callMetrics ?? [])
                 }"
                 x-init="
                     // Set global data
                     window.analyticsChartData = chartData;
                     window.analyticsHeatmapData = heatmapData;
                     window.analyticsCallMetrics = callMetrics;
                     
                     console.log('📊 Data set via Alpine for company {{ $companyId }}');
                     
                     // Trigger chart creation
                     $nextTick(() => {
                         if (window.createAnalyticsChartsNow) {
                             window.createAnalyticsChartsNow();
                         }
                     });
                 ">
            </div>
            
            <!-- Company Selected - Show Stats and Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stats Cards -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gesamt-Termine</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['total_appointments'] ?? 0) }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <x-heroicon-o-calendar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Umsatz</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['revenue'] ?? 0, 2, ',', '.') }} €
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                            <x-heroicon-o-currency-euro class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Auslastung</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stats['utilization'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No-Show Rate</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stats['no_show_rate'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                            <x-heroicon-o-x-circle class="w-8 h-8 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Container -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Appointments Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termine im Zeitverlauf</h3>
                    <div style="position: relative; height: 300px; overflow: hidden;">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                </div>
                
                <!-- Revenue Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Umsatzentwicklung</h3>
                    <div style="position: relative; height: 300px; overflow: hidden;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Call Charts -->
            @if($viewMode === 'inbound' || $viewMode === 'combined')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Anrufverteilung</h3>
                        <div style="position: relative; height: 300px; overflow: hidden;">
                            <canvas id="callDistributionChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Anrufe im Zeitverlauf</h3>
                        <div style="position: relative; height: 300px; overflow: hidden;">
                            <canvas id="callsTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Heatmap -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termine Heatmap</h3>
                <div id="heatmap" style="height: 400px; overflow: hidden;"></div>
            </div>
        @else
            <!-- No Company Selected -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-yellow-600 dark:text-yellow-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Kein Unternehmen ausgewählt
                </h3>
                <p class="text-gray-600 dark:text-gray-300">
                    Bitte wählen Sie ein Unternehmen aus der Dropdown-Liste aus, um die Analytics anzuzeigen.
                </p>
            </div>
        @endif
    </div>
    
    @push('scripts')
    <!-- Chart Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Alpine component for managing charts
        if (!window.analyticsChartsRegistered) {
            window.analyticsChartsRegistered = true;
            
            document.addEventListener('alpine:init', () => {
                Alpine.data('analyticsCharts', () => ({
                chartInstances: {},
                isInitialized: false,
                
                init() {
                    if (this.isInitialized) {
                        console.log('⚠️ Already initialized, skipping...');
                        return;
                    }
                    this.isInitialized = true;
                    console.log('🏔️ Alpine Analytics Charts initialized');
                    this.setupChartCreator();
                },
                
                setupChartCreator() {
                    // Global function to create charts
                    window.createAnalyticsChartsNow = () => {
                        console.log('🎨 Creating charts via Alpine...');
                        
                        // Destroy existing charts
                        this.destroyAllCharts();
                        
                        // Wait for libraries
                        this.waitForLibraries(() => {
                            this.createAllCharts();
                        });
                    };
                },
                
                waitForLibraries(callback) {
                    if (typeof Chart !== 'undefined' && typeof ApexCharts !== 'undefined') {
                        callback();
                    } else {
                        console.log('⏳ Waiting for chart libraries...');
                        setTimeout(() => this.waitForLibraries(callback), 100);
                    }
                },
                
                destroyAllCharts() {
                    console.log('🗑️ Destroying existing charts...');
                    Object.keys(this.chartInstances).forEach(key => {
                        if (this.chartInstances[key]) {
                            try {
                                if (typeof this.chartInstances[key].destroy === 'function') {
                                    this.chartInstances[key].destroy();
                                    console.log(`✅ Destroyed ${key}`);
                                }
                            } catch (e) {
                                console.error(`❌ Error destroying ${key}:`, e);
                            }
                            delete this.chartInstances[key];
                        }
                    });
                    
                    // Clear canvases
                    ['appointmentsChart', 'revenueChart', 'callDistributionChart', 'callsTimelineChart'].forEach(id => {
                        const canvas = document.getElementById(id);
                        if (canvas) {
                            const ctx = canvas.getContext('2d');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                        }
                    });
                    
                    // Clear heatmap
                    const heatmapEl = document.getElementById('heatmap');
                    if (heatmapEl) {
                        heatmapEl.innerHTML = '';
                    }
                },
                
                createAllCharts() {
                    const chartData = window.analyticsChartData || {};
                    const heatmapData = window.analyticsHeatmapData || [];
                    const callMetrics = window.analyticsCallMetrics || {};
                    
                    console.log('📊 Creating charts with data:', {
                        hasChartData: Object.keys(chartData).length > 0,
                        hasHeatmap: heatmapData.length > 0,
                        hasMetrics: Object.keys(callMetrics).length > 0
                    });
                    
                    // Create each chart
                    this.createAppointmentsChart(chartData);
                    this.createRevenueChart(chartData);
                    this.createCallDistributionChart(callMetrics);
                    this.createCallsTimelineChart(chartData);
                    this.createHeatmap(heatmapData);
                },
                
                createAppointmentsChart(data) {
                    const el = document.getElementById('appointmentsChart');
                    if (el && data.labels && data.appointments) {
                        // Destroy existing chart if it exists
                        if (this.chartInstances.appointments) {
                            this.chartInstances.appointments.destroy();
                            delete this.chartInstances.appointments;
                        }
                        
                        try {
                            this.chartInstances.appointments = new Chart(el.getContext('2d'), {
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
                                    plugins: { legend: { display: false } },
                                    scales: { y: { beginAtZero: true } }
                                }
                            });
                            console.log('✅ Appointments chart created');
                        } catch (e) {
                            console.error('❌ Appointments chart error:', e);
                        }
                    }
                },
                
                createRevenueChart(data) {
                    const el = document.getElementById('revenueChart');
                    if (el && data.labels && data.revenue) {
                        // Destroy existing chart if it exists
                        if (this.chartInstances.revenue) {
                            this.chartInstances.revenue.destroy();
                            delete this.chartInstances.revenue;
                        }
                        
                        try {
                            this.chartInstances.revenue = new Chart(el.getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Umsatz',
                                        data: data.revenue,
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
                },
                
                createCallDistributionChart(metrics) {
                    const el = document.getElementById('callDistributionChart');
                    if (el && metrics.inbound) {
                        const inbound = metrics.inbound.total_calls || 0;
                        const outbound = (metrics.outbound && metrics.outbound.total_calls) || 0;
                        
                        if (inbound > 0 || outbound > 0) {
                            // Destroy existing chart if it exists
                            if (this.chartInstances.callDist) {
                                this.chartInstances.callDist.destroy();
                                delete this.chartInstances.callDist;
                            }
                            
                            try {
                                this.chartInstances.callDist = new Chart(el.getContext('2d'), {
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
                            } catch (e) {
                                console.error('❌ Call distribution error:', e);
                            }
                        }
                    }
                },
                
                createCallsTimelineChart(data) {
                    const el = document.getElementById('callsTimelineChart');
                    if (el && data.labels && data.calls) {
                        // Destroy existing chart if it exists
                        if (this.chartInstances.callsTimeline) {
                            this.chartInstances.callsTimeline.destroy();
                            delete this.chartInstances.callsTimeline;
                        }
                        
                        try {
                            this.chartInstances.callsTimeline = new Chart(el.getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Anrufe',
                                        data: data.calls,
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
                },
                
                createHeatmap(data) {
                    const el = document.getElementById('heatmap');
                    if (el && data && data.length > 0) {
                        // Destroy existing chart if it exists
                        if (this.chartInstances.heatmap) {
                            this.chartInstances.heatmap.destroy();
                            delete this.chartInstances.heatmap;
                        }
                        
                        try {
                            el.innerHTML = '';
                            this.chartInstances.heatmap = new ApexCharts(el, {
                                series: data,
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
                            this.chartInstances.heatmap.render();
                            console.log('✅ Heatmap created');
                        } catch (e) {
                            console.error('❌ Heatmap error:', e);
                        }
                    }
                }
                }));
            });
        }
    </script>
    @endpush
</x-filament-panels::page>