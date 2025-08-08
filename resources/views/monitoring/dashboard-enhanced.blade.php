<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring Pro - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .status-dot { animation: pulse-dot 2s infinite; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .gradient-healthy { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .gradient-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .gradient-critical { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        /* Fix for chart container to prevent growing */
        #performanceChart {
            max-height: 300px !important;
            height: 100% !important;
            width: 100% !important;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="monitoringDashboard()">
    <div class="min-h-screen">
        <!-- Enhanced Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                            <span class="text-3xl mr-2">🔭</span>
                            System Monitoring Pro
                        </h1>
                        <span class="px-3 py-1 text-xs font-medium rounded-full"
                              :class="systemStatus === 'healthy' ? 'bg-green-100 text-green-800' : 
                                      systemStatus === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 
                                      'bg-red-100 text-red-800'">
                            <span class="inline-block w-2 h-2 rounded-full mr-1 status-dot"
                                  :class="systemStatus === 'healthy' ? 'bg-green-500' : 
                                          systemStatus === 'degraded' ? 'bg-yellow-500' : 'bg-red-500'"></span>
                            System <span x-text="systemStatus"></span>
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-500">
                            Auto-Refresh: 
                            <button @click="toggleAutoRefresh()" 
                                    class="ml-1 px-2 py-1 rounded"
                                    :class="autoRefresh ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                <span x-text="autoRefresh ? 'ON' : 'OFF'"></span>
                            </button>
                        </div>
                        <span class="text-sm text-gray-500">
                            Letzte Aktualisierung: <span x-text="lastUpdated"></span>
                        </span>
                        <span class="text-sm text-gray-500">{{ auth()->user()->email }}</span>
                        <a href="/admin" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Zurück zum Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-3">
                    <a href="/telescope" class="text-blue-600 border-b-2 border-blue-600 pb-3 px-1 font-medium text-sm">
                        Dashboard
                    </a>
                    <a href="/telescope/logs" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Logs
                    </a>
                    <a href="/telescope/queries" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Queries
                    </a>
                    <a href="/horizon" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Horizon
                    </a>
                    <a href="/telescope/health" target="_blank" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Health API
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Critical Alerts (if any) -->
            @if(($metrics['errors']['critical'] ?? 0) > 0 || ($metrics['queue']['failed'] ?? 0) > 10)
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-medium text-red-900">Kritische Warnungen</h3>
                        <ul class="mt-2 text-sm text-red-700">
                            @if(($metrics['errors']['critical'] ?? 0) > 0)
                            <li>• {{ $metrics['errors']['critical'] }} kritische Fehler in den letzten 24h</li>
                            @endif
                            @if(($metrics['queue']['failed'] ?? 0) > 10)
                            <li>• {{ $metrics['queue']['failed'] }} fehlgeschlagene Queue-Jobs</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            <!-- System Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- CPU Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-600">CPU Auslastung</h3>
                        <span class="text-2xl">🖥️</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">
                        {{ $metrics['system']['cpu']['load_percentage'] ?? 'N/A' }}%
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500"
                                 :class="cpuStatus === 'healthy' ? 'gradient-healthy' : 
                                         cpuStatus === 'warning' ? 'gradient-warning' : 'gradient-critical'"
                                 style="width: {{ $metrics['system']['cpu']['load_percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Load: {{ $metrics['system']['cpu']['load_1'] ?? 'N/A' }} / 
                        {{ $metrics['system']['cpu']['cores'] ?? 'N/A' }} Cores
                    </p>
                </div>

                <!-- Memory Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-600">Memory (RAM)</h3>
                        <span class="text-2xl">💾</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">
                        {{ $metrics['system']['memory']['percentage'] ?? 'N/A' }}
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="gradient-healthy h-2 rounded-full transition-all duration-500"
                                 style="width: {{ str_replace('%', '', $metrics['system']['memory']['percentage'] ?? 0) }}%"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ $metrics['system']['memory']['used'] ?? 'N/A' }} / 
                        {{ $metrics['system']['memory']['total'] ?? 'N/A' }}
                    </p>
                </div>

                <!-- Disk Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-600">Festplatte</h3>
                        <span class="text-2xl">💿</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">
                        {{ $metrics['system']['disk']['percentage'] ?? 'N/A' }}
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="gradient-healthy h-2 rounded-full transition-all duration-500"
                                 style="width: {{ str_replace('%', '', $metrics['system']['disk']['percentage'] ?? 0) }}%"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ $metrics['system']['disk']['free'] ?? 'N/A' }} frei von 
                        {{ $metrics['system']['disk']['total'] ?? 'N/A' }}
                    </p>
                </div>

                <!-- Uptime Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-600">Uptime</h3>
                        <span class="text-2xl">⏱️</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">
                        {{ $metrics['system']['uptime'] ?? 'N/A' }}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        System läuft stabil
                    </p>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Calls & Errors Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Performance (24h)</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- Business Metrics -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">💼 Business Metriken</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-sm text-blue-600 font-medium">Anrufe heute</p>
                            <p class="text-2xl font-bold text-blue-900">{{ $metrics['business']['calls_today'] ?? 0 }}</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-sm text-green-600 font-medium">Termine heute</p>
                            <p class="text-2xl font-bold text-green-900">{{ $metrics['business']['appointments_today'] ?? 0 }}</p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <p class="text-sm text-purple-600 font-medium">Aktive Firmen</p>
                            <p class="text-2xl font-bold text-purple-900">{{ $metrics['business']['active_companies'] ?? 0 }}</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <p class="text-sm text-yellow-600 font-medium">Umsatz heute</p>
                            <p class="text-2xl font-bold text-yellow-900">€{{ number_format($metrics['business']['revenue_today'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Status -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Database Status -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🗄️ Database</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Verbindungen</span>
                            <span class="text-sm font-medium">
                                {{ $metrics['database']['connections'] ?? 0 }} / 
                                {{ $metrics['database']['max_connections'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Queries heute</span>
                            <span class="text-sm font-medium">{{ number_format($metrics['database']['queries_today'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Langsame Queries</span>
                            <span class="text-sm font-medium 
                                {{ ($metrics['database']['slow_queries'] ?? 0) > 10 ? 'text-orange-600' : '' }}">
                                {{ $metrics['database']['slow_queries'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Queue Status -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📬 Queue System</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pending Jobs</span>
                            <span class="text-sm font-medium">{{ $metrics['queue']['jobs'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Failed Jobs</span>
                            <span class="text-sm font-medium 
                                {{ ($metrics['queue']['failed'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $metrics['queue']['failed'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Recent Failed (24h)</span>
                            <span class="text-sm font-medium">{{ $metrics['queue']['recent_failed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- API Health -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🌐 API Status</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Retell.ai</span>
                            <div class="flex items-center">
                                <span class="inline-block w-2 h-2 rounded-full mr-2 
                                    {{ ($metrics['api_health']['retell']['status'] ?? 'offline') === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                <span class="text-sm font-medium">
                                    {{ $metrics['api_health']['retell']['response_time'] ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Cal.com</span>
                            <div class="flex items-center">
                                <span class="inline-block w-2 h-2 rounded-full mr-2 
                                    {{ ($metrics['api_health']['calcom']['status'] ?? 'offline') === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                <span class="text-sm font-medium">
                                    {{ $metrics['api_health']['calcom']['response_time'] ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Summary -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Fehler-Übersicht</h3>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900">{{ $metrics['errors']['total'] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">Gesamt Fehler</p>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <p class="text-3xl font-bold text-yellow-900">{{ $metrics['errors']['warnings'] ?? 0 }}</p>
                        <p class="text-sm text-yellow-600">Warnungen</p>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <p class="text-3xl font-bold text-orange-900">{{ $metrics['errors']['last_24h'] ?? 0 }}</p>
                        <p class="text-sm text-orange-600">Fehler (24h)</p>
                    </div>
                    <div class="text-center p-4 {{ ($metrics['errors']['critical'] ?? 0) > 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg">
                        <p class="text-3xl font-bold {{ ($metrics['errors']['critical'] ?? 0) > 0 ? 'text-red-900' : 'text-green-900' }}">
                            {{ $metrics['errors']['critical'] ?? 0 }}
                        </p>
                        <p class="text-sm {{ ($metrics['errors']['critical'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            Kritisch (24h)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alpine.js Component für interaktive Features
        function monitoringDashboard() {
            return {
                autoRefresh: true,
                refreshInterval: null,
                lastUpdated: '{{ $metrics['last_updated'] ?? 'N/A' }}',
                systemStatus: 'healthy',
                cpuStatus: 'healthy',
                
                init() {
                    this.evaluateSystemStatus();
                    this.initPerformanceChart();
                    if (this.autoRefresh) {
                        this.startAutoRefresh();
                    }
                },
                
                evaluateSystemStatus() {
                    const cpuLoad = {{ str_replace('%', '', $metrics['system']['cpu']['load_percentage'] ?? 0) }};
                    const errors = {{ $metrics['errors']['critical'] ?? 0 }};
                    const failedJobs = {{ $metrics['queue']['failed'] ?? 0 }};
                    
                    if (errors > 0 || failedJobs > 10) {
                        this.systemStatus = 'critical';
                    } else if (cpuLoad > 80 || failedJobs > 5) {
                        this.systemStatus = 'degraded';
                    } else {
                        this.systemStatus = 'healthy';
                    }
                    
                    this.cpuStatus = cpuLoad < 70 ? 'healthy' : (cpuLoad < 90 ? 'warning' : 'critical');
                },
                
                toggleAutoRefresh() {
                    this.autoRefresh = !this.autoRefresh;
                    if (this.autoRefresh) {
                        this.startAutoRefresh();
                    } else {
                        this.stopAutoRefresh();
                    }
                },
                
                startAutoRefresh() {
                    this.refreshInterval = setInterval(() => {
                        this.refreshData();
                    }, 30000); // 30 seconds
                },
                
                stopAutoRefresh() {
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                    }
                },
                
                async refreshData() {
                    try {
                        const response = await fetch('/telescope/refresh');
                        const data = await response.json();
                        this.lastUpdated = data.last_updated;
                        // Update real-time metrics
                        this.updateMetrics(data);
                    } catch (error) {
                        console.error('Refresh failed:', error);
                    }
                },
                
                updateMetrics(data) {
                    // Update DOM elements with new data
                    // This would be more sophisticated in production
                    console.log('Metrics updated:', data);
                },
                
                initPerformanceChart() {
                    const ctx = document.getElementById('performanceChart').getContext('2d');
                    const performanceData = @json($metrics['performance_history'] ?? []);
                    
                    // Destroy existing chart if it exists to prevent duplicates
                    if (window.performanceChartInstance) {
                        window.performanceChartInstance.destroy();
                    }
                    
                    window.performanceChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: performanceData.map(d => d.hour),
                            datasets: [{
                                label: 'Anrufe',
                                data: performanceData.map(d => d.calls),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4
                            }, {
                                label: 'Fehler',
                                data: performanceData.map(d => d.errors),
                                borderColor: 'rgb(239, 68, 68)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
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
        }
    </script>
</body>
</html>