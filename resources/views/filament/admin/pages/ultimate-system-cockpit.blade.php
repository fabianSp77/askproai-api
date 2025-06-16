<x-filament-panels::page>
    @push('styles')
    <style>
        /* Glassmorphism Effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        /* Neon Glow Effects */
        .neon-green { color: #00ff41; text-shadow: 0 0 10px #00ff41; }
        .neon-yellow { color: #ffff00; text-shadow: 0 0 10px #ffff00; }
        .neon-red { color: #ff0040; text-shadow: 0 0 10px #ff0040; }
        .neon-blue { color: #00d4ff; text-shadow: 0 0 10px #00d4ff; }
        
        /* Pulse Animation */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Health Bar Animation */
        .health-bar {
            position: relative;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .health-bar-fill {
            height: 100%;
            transition: width 0.5s ease, background-color 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .health-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Grid Animation */
        .metric-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .metric-card {
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        /* Company Honeycomb */
        .company-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .company-cell {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .company-cell::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, transparent 30%, currentColor 70%);
            opacity: 0.1;
        }
        
        .company-cell:hover {
            transform: scale(1.1);
            z-index: 10;
        }
        
        /* Status Indicators */
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .status-healthy { background: #00ff41; box-shadow: 0 0 10px #00ff41; }
        .status-warning { background: #ffff00; box-shadow: 0 0 10px #ffff00; }
        .status-critical { background: #ff0040; box-shadow: 0 0 10px #ff0040; }
        
        /* 3D Globe Container */
        #globe-container {
            width: 100%;
            height: 600px;
            position: relative;
            background: radial-gradient(ellipse at center, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
            border-radius: 1rem;
            overflow: hidden;
        }
        
        /* Anomaly Alert */
        .anomaly-alert {
            animation: alert-pulse 1s infinite;
            border: 2px solid currentColor;
        }
        
        @keyframes alert-pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.98); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            z-index: 40;
        }
        
        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 50;
        }
        
        /* WebSocket Status */
        .ws-status {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .ws-connected {
            background: rgba(0, 255, 65, 0.2);
            color: #00ff41;
            border: 1px solid #00ff41;
        }
        
        .ws-disconnected {
            background: rgba(255, 0, 64, 0.2);
            color: #ff0040;
            border: 1px solid #ff0040;
        }
    </style>
    @endpush
    
    <div class="space-y-6" x-data="systemCockpit()" x-init="init()">
        
        <!-- Header with Overall Health -->
        <div class="glass-card rounded-xl p-8 text-center">
            <h1 class="text-4xl font-bold mb-4">AskProAI System Health</h1>
            <div class="relative inline-block">
                <div class="text-8xl font-bold pulse-animation"
                     :class="{
                         'neon-green': overallHealth >= 90,
                         'neon-yellow': overallHealth >= 70 && overallHealth < 90,
                         'neon-red': overallHealth < 70
                     }"
                     x-text="overallHealth + '%'">
                </div>
                <div class="text-sm opacity-70 mt-2">Overall System Health</div>
            </div>
            
            <!-- Real-time Stats -->
            <div class="grid grid-cols-4 gap-4 mt-8">
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green" x-text="realtimeStats.calls_per_minute">{{ $realtimeStats['calls_per_minute'] }}</div>
                    <div class="text-xs opacity-70">Calls/Min</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green" x-text="realtimeStats.appointments_per_hour">{{ $realtimeStats['appointments_per_hour'] }}</div>
                    <div class="text-xs opacity-70">Appointments/Hour</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green" x-text="realtimeStats.new_customers_today">{{ $realtimeStats['new_customers_today'] }}</div>
                    <div class="text-xs opacity-70">New Customers</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-bold" x-text="realtimeStats.peak_hour">{{ $realtimeStats['peak_hour'] }}</div>
                    <div class="text-xs opacity-70">Peak Hour</div>
                </div>
            </div>
        </div>
        
        <!-- Anomaly Alerts -->
        <div x-show="anomalies.length > 0" class="space-y-2">
            <template x-for="anomaly in anomalies" :key="anomaly.timestamp">
                <div class="glass-card rounded-lg p-4 anomaly-alert"
                     :class="{
                         'border-red-500 neon-red': anomaly.severity === 'high',
                         'border-yellow-500 neon-yellow': anomaly.severity === 'medium',
                         'border-blue-500 neon-blue': anomaly.severity === 'low'
                     }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 mr-2" />
                            <span x-text="anomaly.message"></span>
                        </div>
                        <span class="text-xs opacity-70" x-text="formatTime(anomaly.timestamp)"></span>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- 3D System Visualization -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <x-heroicon-o-globe-alt class="w-6 h-6 mr-2" />
                Global System Overview
            </h2>
            <div id="globe-container"></div>
        </div>
        
        <!-- Historical Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Hourly Activity Chart -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4">24-Hour Activity</h3>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
            
            <!-- Daily Trends Chart -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4">7-Day Trends</h3>
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Service Health Matrix -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <x-heroicon-o-server-stack class="w-6 h-6 mr-2" />
                Service Health Matrix
            </h2>
            
            <div class="space-y-4">
                <template x-for="(health, service) in serviceHealth" :key="service">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="status-dot" 
                                  :class="{
                                      'status-healthy': health >= 90,
                                      'status-warning': health >= 70 && health < 90,
                                      'status-critical': health < 70
                                  }"></span>
                            <span class="font-medium" x-text="formatServiceName(service)"></span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-48">
                                <div class="health-bar">
                                    <div class="health-bar-fill" 
                                         :style="`width: ${health}%; background: ${getHealthColor(health)}`">
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm font-mono" x-text="health + '%'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- System Metrics Grid -->
        <div class="metric-grid">
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-clock class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Response Time</span>
                </div>
                <div class="text-3xl font-bold" x-text="systemMetrics.response_time + 'ms'">{{ $systemMetrics['response_time'] }}ms</div>
                <div class="text-xs opacity-50 mt-1">Average</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Error Rate</span>
                </div>
                <div class="text-3xl font-bold" x-text="(systemMetrics.error_rate * 100).toFixed(2) + '%'">{{ number_format($systemMetrics['error_rate'] * 100, 2) }}%</div>
                <div class="text-xs opacity-50 mt-1">Last Hour</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-queue-list class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Queue Size</span>
                </div>
                <div class="text-3xl font-bold" x-text="systemMetrics.queue_size.toLocaleString()">{{ number_format($systemMetrics['queue_size']) }}</div>
                <div class="text-xs opacity-50 mt-1">Pending Jobs</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-arrow-trending-up class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Uptime</span>
                </div>
                <div class="text-2xl font-bold" x-text="systemMetrics.uptime">{{ $systemMetrics['uptime'] }}</div>
                <div class="text-xs opacity-50 mt-1">Since Last Restart</div>
            </div>
        </div>
        
        <!-- Company Health Overview -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <x-heroicon-o-building-office-2 class="w-6 h-6 mr-2" />
                Company Health Overview
            </h2>
            
            <div class="company-grid">
                <template x-for="company in companyMetrics" :key="company.id">
                    <div class="glass-card rounded-lg p-4 company-cell"
                         :class="{
                             'border-green-500': company.health >= 85,
                             'border-yellow-500': company.health >= 60 && company.health < 85,
                             'border-red-500': company.health < 60
                         }"
                         style="border-width: 2px;"
                         @click="showCompanyDetails(company)">
                        <h3 class="font-semibold text-sm mb-2 truncate w-full text-center" x-text="company.name"></h3>
                        <div class="text-3xl font-bold mb-2"
                             :class="{
                                 'neon-green': company.health >= 85,
                                 'neon-yellow': company.health >= 60 && company.health < 85,
                                 'neon-red': company.health < 60
                             }"
                             x-text="company.health + '%'">
                        </div>
                        <div class="text-xs space-y-1 w-full">
                            <div class="flex justify-between">
                                <span class="opacity-70">Calls:</span>
                                <span class="font-mono" x-text="company.calls_today"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Appts:</span>
                                <span class="font-mono" x-text="company.appointments_today"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Branches:</span>
                                <span class="font-mono" x-text="company.branch_count"></span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs" x-show="company.performance_trend">
                            <span :class="{
                                'text-green-400': company.performance_trend.trend === 'up',
                                'text-red-400': company.performance_trend.trend === 'down',
                                'text-gray-400': company.performance_trend.trend === 'stable'
                            }">
                                <span x-show="company.performance_trend.trend === 'up'">↑</span>
                                <span x-show="company.performance_trend.trend === 'down'">↓</span>
                                <span x-show="company.performance_trend.trend === 'stable'">→</span>
                                <span x-text="Math.abs(company.performance_trend.daily_change) + '%'"></span>
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Company Details Modal -->
        <div x-show="showModal" class="modal-backdrop" @click="showModal = false">
            <div class="modal-content glass-card rounded-xl p-6" @click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold" x-text="selectedCompany?.name"></h2>
                    <button @click="showModal = false" class="p-2 hover:bg-gray-700 rounded-lg">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>
                
                <div x-show="selectedCompany" class="space-y-6">
                    <!-- Company Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold neon-green" x-text="selectedCompany?.stats?.total_calls"></div>
                            <div class="text-xs opacity-70">Total Calls</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold neon-green" x-text="selectedCompany?.stats?.calls_today"></div>
                            <div class="text-xs opacity-70">Calls Today</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold neon-blue" x-text="selectedCompany?.stats?.appointments_this_week"></div>
                            <div class="text-xs opacity-70">Appointments This Week</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold" x-text="selectedCompany?.stats?.active_staff"></div>
                            <div class="text-xs opacity-70">Active Staff</div>
                        </div>
                    </div>
                    
                    <!-- Branches -->
                    <div x-show="selectedCompany?.branches?.length > 0">
                        <h3 class="text-lg font-semibold mb-3">Branches</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="branch in selectedCompany.branches" :key="branch.id">
                                <div class="glass-card rounded-lg p-4">
                                    <h4 class="font-medium" x-text="branch.name"></h4>
                                    <p class="text-sm opacity-70" x-text="branch.city"></p>
                                    <div class="mt-2 text-xs space-y-1">
                                        <div>Staff: <span x-text="branch.staff_count"></span></div>
                                        <div>Appointments Today: <span x-text="branch.appointments_today"></span></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div x-show="selectedCompany?.recent_activity?.length > 0">
                        <h3 class="text-lg font-semibold mb-3">Recent Activity</h3>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <template x-for="activity in selectedCompany.recent_activity" :key="activity.id">
                                <div class="flex justify-between text-sm">
                                    <span x-text="activity.customer?.name || 'Unknown'"></span>
                                    <span class="opacity-70" x-text="formatTime(activity.created_at)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="fixed bottom-6 right-6 flex space-x-3">
            <button @click="toggleAutoRefresh()"
                    class="glass-card rounded-full p-4 hover:scale-110 transition-transform"
                    :class="{ 'neon-green': autoRefresh }">
                <x-heroicon-o-arrow-path class="w-6 h-6" />
            </button>
            <button @click="toggleFullscreen()"
                    class="glass-card rounded-full p-4 hover:scale-110 transition-transform">
                <x-heroicon-o-arrows-pointing-out class="w-6 h-6" />
            </button>
        </div>
    </div>
    
    @push('scripts')
    <!-- Three.js for 3D visualization -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.150.0/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.150.0/examples/js/controls/OrbitControls.js"></script>
    
    <!-- Chart.js for historical charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.0/dist/chart.umd.js"></script>
    
    <!-- Echo for WebSocket -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.0.0/dist/web/pusher.min.js"></script>
    
    <script>
        function systemCockpit() {
            return {
                // Data properties
                overallHealth: {{ $systemMetrics['overall_health'] }},
                systemMetrics: @json($systemMetrics),
                serviceHealth: @json($serviceHealth),
                companyMetrics: @json($companyMetrics),
                realtimeStats: @json($realtimeStats),
                historicalData: @json($historicalData),
                anomalies: @json($anomalies),
                globalSystemData: @json($globalSystemData),
                
                // UI state
                autoRefresh: true,
                refreshInterval: null,
                wsConnected: false,
                showModal: false,
                selectedCompany: null,
                
                // Three.js objects
                scene: null,
                camera: null,
                renderer: null,
                globe: null,
                controls: null,
                
                // Chart.js objects
                hourlyChart: null,
                dailyChart: null,
                
                // Echo instance
                echo: null,
                
                init() {
                    this.initWebSocket();
                    this.init3DGlobe();
                    this.initCharts();
                    this.startAutoRefresh();
                    
                    // Listen for Livewire updates
                    Livewire.on('metrics-updated', (data) => {
                        this.updateMetrics(data);
                    });
                },
                
                initWebSocket() {
                    // Initialize Laravel Echo
                    this.echo = new Echo({
                        broadcaster: 'pusher',
                        key: '{{ config('broadcasting.connections.pusher.key') }}',
                        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                        forceTLS: true,
                        encrypted: true
                    });
                    
                    // Subscribe to system metrics channel
                    this.echo.channel('system-metrics')
                        .listen('MetricsUpdated', (e) => {
                            this.wsConnected = true;
                            this.updateMetrics(e);
                        })
                        .error((error) => {
                            this.wsConnected = false;
                            console.error('WebSocket error:', error);
                        });
                    
                    // Connection status
                    this.echo.connector.pusher.connection.bind('connected', () => {
                        this.wsConnected = true;
                    });
                    
                    this.echo.connector.pusher.connection.bind('disconnected', () => {
                        this.wsConnected = false;
                    });
                },
                
                init3DGlobe() {
                    const container = document.getElementById('globe-container');
                    if (!container) return;
                    
                    // Scene setup
                    this.scene = new THREE.Scene();
                    this.scene.background = null;
                    
                    // Camera setup
                    const aspect = container.clientWidth / container.clientHeight;
                    this.camera = new THREE.PerspectiveCamera(75, aspect, 0.1, 1000);
                    this.camera.position.z = 2.5;
                    
                    // Renderer setup
                    this.renderer = new THREE.WebGLRenderer({ 
                        antialias: true, 
                        alpha: true 
                    });
                    this.renderer.setSize(container.clientWidth, container.clientHeight);
                    this.renderer.setPixelRatio(window.devicePixelRatio);
                    container.appendChild(this.renderer.domElement);
                    
                    // Create globe
                    const geometry = new THREE.SphereGeometry(1, 64, 64);
                    const material = new THREE.MeshPhongMaterial({
                        color: 0x0077ff,
                        emissive: 0x0044aa,
                        emissiveIntensity: 0.1,
                        shininess: 100,
                        opacity: 0.8,
                        transparent: true,
                        wireframe: false
                    });
                    this.globe = new THREE.Mesh(geometry, material);
                    this.scene.add(this.globe);
                    
                    // Add wireframe overlay
                    const wireframeGeometry = new THREE.SphereGeometry(1.01, 32, 32);
                    const wireframeMaterial = new THREE.MeshBasicMaterial({
                        color: 0x00ffff,
                        wireframe: true,
                        opacity: 0.2,
                        transparent: true
                    });
                    const wireframe = new THREE.Mesh(wireframeGeometry, wireframeMaterial);
                    this.globe.add(wireframe);
                    
                    // Add nodes for companies/branches
                    this.addSystemNodes();
                    
                    // Lighting
                    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
                    this.scene.add(ambientLight);
                    
                    const pointLight = new THREE.PointLight(0xffffff, 1);
                    pointLight.position.set(5, 5, 5);
                    this.scene.add(pointLight);
                    
                    // Controls
                    this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
                    this.controls.enableDamping = true;
                    this.controls.dampingFactor = 0.05;
                    this.controls.rotateSpeed = 0.5;
                    this.controls.enableZoom = true;
                    this.controls.minDistance = 1.5;
                    this.controls.maxDistance = 5;
                    
                    // Animation loop
                    this.animate3D();
                    
                    // Handle resize
                    window.addEventListener('resize', () => this.handleResize());
                },
                
                addSystemNodes() {
                    if (!this.globalSystemData.nodes) return;
                    
                    const nodeGroup = new THREE.Group();
                    
                    this.globalSystemData.nodes.forEach(node => {
                        // Convert lat/lng to 3D coordinates
                        const phi = (90 - node.lat) * Math.PI / 180;
                        const theta = (node.lng + 180) * Math.PI / 180;
                        
                        const x = Math.sin(phi) * Math.cos(theta);
                        const y = Math.cos(phi);
                        const z = Math.sin(phi) * Math.sin(theta);
                        
                        // Create node
                        const nodeGeometry = new THREE.SphereGeometry(node.size / 1000, 8, 8);
                        const nodeColor = node.health >= 85 ? 0x00ff41 : 
                                         node.health >= 60 ? 0xffff00 : 0xff0040;
                        const nodeMaterial = new THREE.MeshBasicMaterial({
                            color: nodeColor,
                            emissive: nodeColor,
                            emissiveIntensity: 0.5
                        });
                        const nodeMesh = new THREE.Mesh(nodeGeometry, nodeMaterial);
                        nodeMesh.position.set(x * 1.05, y * 1.05, z * 1.05);
                        nodeMesh.userData = node;
                        nodeGroup.add(nodeMesh);
                        
                        // Add glow effect
                        const glowGeometry = new THREE.SphereGeometry(node.size / 800, 8, 8);
                        const glowMaterial = new THREE.MeshBasicMaterial({
                            color: nodeColor,
                            transparent: true,
                            opacity: 0.3
                        });
                        const glowMesh = new THREE.Mesh(glowGeometry, glowMaterial);
                        glowMesh.position.copy(nodeMesh.position);
                        nodeGroup.add(glowMesh);
                    });
                    
                    // Add connections
                    this.globalSystemData.connections.forEach(conn => {
                        const sourceNode = this.globalSystemData.nodes.find(n => n.id === conn.source);
                        const targetNode = this.globalSystemData.nodes.find(n => n.id === conn.target);
                        
                        if (sourceNode && targetNode) {
                            const curve = this.createConnectionCurve(sourceNode, targetNode);
                            const tubeGeometry = new THREE.TubeGeometry(curve, 32, 0.001, 8, false);
                            const tubeMaterial = new THREE.MeshBasicMaterial({
                                color: 0x00ffff,
                                opacity: 0.5,
                                transparent: true
                            });
                            const tube = new THREE.Mesh(tubeGeometry, tubeMaterial);
                            nodeGroup.add(tube);
                        }
                    });
                    
                    this.globe.add(nodeGroup);
                },
                
                createConnectionCurve(source, target) {
                    const sourcePhi = (90 - source.lat) * Math.PI / 180;
                    const sourceTheta = (source.lng + 180) * Math.PI / 180;
                    const targetPhi = (90 - target.lat) * Math.PI / 180;
                    const targetTheta = (target.lng + 180) * Math.PI / 180;
                    
                    const sourceVec = new THREE.Vector3(
                        Math.sin(sourcePhi) * Math.cos(sourceTheta),
                        Math.cos(sourcePhi),
                        Math.sin(sourcePhi) * Math.sin(sourceTheta)
                    ).multiplyScalar(1.05);
                    
                    const targetVec = new THREE.Vector3(
                        Math.sin(targetPhi) * Math.cos(targetTheta),
                        Math.cos(targetPhi),
                        Math.sin(targetPhi) * Math.sin(targetTheta)
                    ).multiplyScalar(1.05);
                    
                    const midpoint = sourceVec.clone().add(targetVec).multiplyScalar(0.5);
                    midpoint.multiplyScalar(1.2);
                    
                    return new THREE.QuadraticBezierCurve3(sourceVec, midpoint, targetVec);
                },
                
                animate3D() {
                    if (!this.renderer) return;
                    
                    requestAnimationFrame(() => this.animate3D());
                    
                    // Rotate globe
                    if (this.globe) {
                        this.globe.rotation.y += 0.002;
                    }
                    
                    // Update controls
                    if (this.controls) {
                        this.controls.update();
                    }
                    
                    // Render
                    this.renderer.render(this.scene, this.camera);
                },
                
                handleResize() {
                    const container = document.getElementById('globe-container');
                    if (!container || !this.camera || !this.renderer) return;
                    
                    this.camera.aspect = container.clientWidth / container.clientHeight;
                    this.camera.updateProjectionMatrix();
                    this.renderer.setSize(container.clientWidth, container.clientHeight);
                },
                
                initCharts() {
                    // Hourly activity chart
                    const hourlyCtx = document.getElementById('hourlyChart');
                    if (hourlyCtx) {
                        this.hourlyChart = new Chart(hourlyCtx, {
                            type: 'line',
                            data: {
                                labels: this.historicalData.hourly.map(h => h.hour),
                                datasets: [{
                                    label: 'Calls',
                                    data: this.historicalData.hourly.map(h => h.calls),
                                    borderColor: '#00ff41',
                                    backgroundColor: 'rgba(0, 255, 65, 0.1)',
                                    tension: 0.4
                                }, {
                                    label: 'Appointments',
                                    data: this.historicalData.hourly.map(h => h.appointments),
                                    borderColor: '#00d4ff',
                                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                                    tension: 0.4
                                }, {
                                    label: 'Errors',
                                    data: this.historicalData.hourly.map(h => h.errors),
                                    borderColor: '#ff0040',
                                    backgroundColor: 'rgba(255, 0, 64, 0.1)',
                                    tension: 0.4,
                                    yAxisID: 'y1'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        labels: { color: '#fff' }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                        ticks: { color: '#fff' }
                                    },
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                        ticks: { color: '#fff' }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        grid: { drawOnChartArea: false },
                                        ticks: { color: '#fff' }
                                    }
                                }
                            }
                        });
                    }
                    
                    // Daily trends chart
                    const dailyCtx = document.getElementById('dailyChart');
                    if (dailyCtx) {
                        this.dailyChart = new Chart(dailyCtx, {
                            type: 'bar',
                            data: {
                                labels: this.historicalData.daily.map(d => d.date),
                                datasets: [{
                                    label: 'Calls',
                                    data: this.historicalData.daily.map(d => d.calls),
                                    backgroundColor: 'rgba(0, 255, 65, 0.5)',
                                    borderColor: '#00ff41',
                                    borderWidth: 1
                                }, {
                                    label: 'Appointments',
                                    data: this.historicalData.daily.map(d => d.appointments),
                                    backgroundColor: 'rgba(0, 212, 255, 0.5)',
                                    borderColor: '#00d4ff',
                                    borderWidth: 1
                                }, {
                                    label: 'New Customers',
                                    data: this.historicalData.daily.map(d => d.customers),
                                    backgroundColor: 'rgba(255, 255, 0, 0.5)',
                                    borderColor: '#ffff00',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        labels: { color: '#fff' }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                        ticks: { color: '#fff' }
                                    },
                                    y: {
                                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                        ticks: { color: '#fff' }
                                    }
                                }
                            }
                        });
                    }
                },
                
                updateMetrics(data) {
                    // Update data
                    if (data.systemMetrics) {
                        this.systemMetrics = data.systemMetrics;
                        this.overallHealth = data.systemMetrics.overall_health;
                    }
                    if (data.serviceHealth) this.serviceHealth = data.serviceHealth;
                    if (data.companyMetrics) this.companyMetrics = data.companyMetrics;
                    if (data.realtimeStats) this.realtimeStats = data.realtimeStats;
                    if (data.anomalies) this.anomalies = data.anomalies;
                    if (data.historicalData) {
                        this.historicalData = data.historicalData;
                        this.updateCharts();
                    }
                },
                
                updateCharts() {
                    // Update hourly chart
                    if (this.hourlyChart && this.historicalData.hourly) {
                        this.hourlyChart.data.labels = this.historicalData.hourly.map(h => h.hour);
                        this.hourlyChart.data.datasets[0].data = this.historicalData.hourly.map(h => h.calls);
                        this.hourlyChart.data.datasets[1].data = this.historicalData.hourly.map(h => h.appointments);
                        this.hourlyChart.data.datasets[2].data = this.historicalData.hourly.map(h => h.errors);
                        this.hourlyChart.update('none');
                    }
                    
                    // Update daily chart
                    if (this.dailyChart && this.historicalData.daily) {
                        this.dailyChart.data.labels = this.historicalData.daily.map(d => d.date);
                        this.dailyChart.data.datasets[0].data = this.historicalData.daily.map(d => d.calls);
                        this.dailyChart.data.datasets[1].data = this.historicalData.daily.map(d => d.appointments);
                        this.dailyChart.data.datasets[2].data = this.historicalData.daily.map(d => d.customers);
                        this.dailyChart.update('none');
                    }
                },
                
                showCompanyDetails(company) {
                    this.selectedCompany = company;
                    this.showModal = true;
                    
                    // Load detailed company data
                    @this.call('getCompanyDetails', company.id)
                        .then(details => {
                            this.selectedCompany = { ...company, ...details };
                        });
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
                        @this.refresh();
                    }, 5000); // Refresh every 5 seconds
                },
                
                stopAutoRefresh() {
                    if (this.refreshInterval) {
                        clearInterval(this.refreshInterval);
                    }
                },
                
                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                },
                
                formatTime(timestamp) {
                    return new Date(timestamp).toLocaleTimeString();
                },
                
                formatServiceName(service) {
                    return service.replace(/_/g, ' ')
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');
                },
                
                getHealthColor(health) {
                    if (health >= 90) return '#00ff41';
                    if (health >= 70) return '#ffff00';
                    return '#ff0040';
                }
            }
        }
    </script>
    @endpush
</x-filament-panels::page>