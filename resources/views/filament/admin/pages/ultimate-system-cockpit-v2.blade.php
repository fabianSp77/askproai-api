<x-filament-panels::page>
    @push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        /* Dark Theme Base */
        .cockpit-container {
            background: #0a0e1a;
            background-image: 
                radial-gradient(ellipse at top left, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Grid Background */
        .grid-background {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-move 20s linear infinite;
            opacity: 0.3;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.2),
                inset 0 0 0 1px rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                inset 0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        /* Neon Text Effects */
        .neon-text {
            font-family: 'Orbitron', monospace;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .neon-green {
            color: #00ff88;
            text-shadow: 
                0 0 10px #00ff88,
                0 0 20px #00ff88,
                0 0 30px #00ff88;
        }
        
        .neon-blue {
            color: #00d4ff;
            text-shadow: 
                0 0 10px #00d4ff,
                0 0 20px #00d4ff,
                0 0 30px #00d4ff;
        }
        
        .neon-purple {
            color: #bf00ff;
            text-shadow: 
                0 0 10px #bf00ff,
                0 0 20px #bf00ff,
                0 0 30px #bf00ff;
        }
        
        .neon-yellow {
            color: #ffea00;
            text-shadow: 
                0 0 10px #ffea00,
                0 0 20px #ffea00,
                0 0 30px #ffea00;
        }
        
        .neon-red {
            color: #ff0040;
            text-shadow: 
                0 0 10px #ff0040,
                0 0 20px #ff0040,
                0 0 30px #ff0040;
        }
        
        /* Health Ring Animation */
        .health-ring {
            width: 300px;
            height: 300px;
            position: relative;
        }
        
        .health-ring svg {
            transform: rotate(-90deg);
        }
        
        .health-ring-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 20;
        }
        
        .health-ring-progress {
            fill: none;
            stroke-width: 20;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
            filter: drop-shadow(0 0 10px currentColor);
        }
        
        /* Company Grid Hexagon Style */
        .company-hexagon {
            width: 200px;
            height: 220px;
            position: relative;
            margin: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .company-hexagon-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform: rotate(30deg);
            border-radius: 20px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .company-hexagon-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transform: rotate(-30deg);
            padding: 20px;
            text-align: center;
        }
        
        .company-hexagon:hover {
            transform: scale(1.1);
            z-index: 10;
        }
        
        .company-hexagon:hover .company-hexagon-inner {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Service Status Pills */
        .service-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .service-pill:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.5); }
        }
        
        /* Metric Cards with Gradient Borders */
        .metric-card {
            position: relative;
            padding: 2px;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6, #a855f7);
            transition: all 0.3s ease;
        }
        
        .metric-card-inner {
            background: #0a0e1a;
            padding: 24px;
            border-radius: 14px;
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #60a5fa, #c084fc);
        }
        
        /* Data Flow Animation */
        .data-flow {
            position: absolute;
            width: 2px;
            height: 20px;
            background: linear-gradient(to bottom, transparent, #00ff88, transparent);
            animation: flow 2s linear infinite;
        }
        
        @keyframes flow {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }
        
        /* Company Detail Modal */
        .company-detail {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
            backdrop-filter: blur(10px);
        }
        
        .company-detail.active {
            display: flex;
        }
        
        .company-detail-card {
            background: rgba(10, 14, 26, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        /* Connection Lines */
        .connection-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            transform-origin: left center;
            animation: connection-pulse 2s ease-in-out infinite;
        }
        
        @keyframes connection-pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        
        /* Loading Animation */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: #00ff88;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Advanced animations for alerts */
        .alert-card {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Recommendation cards hover effect */
        .recommendation-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .recommendation-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
        
        /* Network canvas glow */
        #networkCanvas {
            filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3));
        }
        
        /* Pulsing critical alerts */
        @keyframes criticalPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
        }
        
        .alert-card.bg-critical-900\/20 {
            animation: criticalPulse 2s infinite;
        }
        
        /* Chart container animations */
        .chart-container {
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Real-time data indicator */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 20px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }
        
        .live-dot {
            width: 8px;
            height: 8px;
            background: #00ff88;
            border-radius: 50%;
            animation: livePulse 1.5s infinite;
        }
        
        @keyframes livePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.5);
            }
        }
    </style>
    @endpush
    
    <div class="cockpit-container relative">
        <div class="grid-background"></div>
        
        <!-- Live Data Indicator -->
        <div class="live-indicator">
            <div class="live-dot"></div>
            <span class="text-sm text-gray-300">Live Data</span>
        </div>
        
        <!-- Data Flow Animations -->
        @for($i = 0; $i < 5; $i++)
            <div class="data-flow" style="left: {{ rand(10, 90) }}%; animation-delay: {{ $i * 0.4 }}s;"></div>
        @endfor
        
        <div class="relative z-10 p-8 space-y-8">
            <!-- Header Section -->
            <div class="text-center mb-12">
                <h1 class="neon-text neon-blue text-5xl font-black mb-2">ASKPROAI SYSTEM COCKPIT</h1>
                <p class="text-gray-400 text-lg">Real-time System Monitoring & Analytics</p>
            </div>
            
            <!-- Main Health Display -->
            <div class="flex justify-center mb-12">
                <div class="health-ring">
                    <svg viewBox="0 0 300 300">
                        <circle cx="150" cy="150" r="130" class="health-ring-bg" />
                        <circle 
                            cx="150" 
                            cy="150" 
                            r="130" 
                            class="health-ring-progress
                                @if($systemMetrics['overall_health'] >= 90) stroke-green-400
                                @elseif($systemMetrics['overall_health'] >= 70) stroke-yellow-400
                                @else stroke-red-400
                                @endif"
                            stroke-dasharray="{{ 2 * pi() * 130 }}"
                            stroke-dashoffset="{{ 2 * pi() * 130 * (1 - $systemMetrics['overall_health'] / 100) }}"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-7xl font-black neon-text
                            @if($systemMetrics['overall_health'] >= 90) neon-green
                            @elseif($systemMetrics['overall_health'] >= 70) neon-yellow
                            @else neon-red
                            @endif">
                            {{ $systemMetrics['overall_health'] }}%
                        </div>
                        <div class="text-sm uppercase tracking-wider text-gray-400 mt-2">System Health</div>
                    </div>
                </div>
            </div>
            
            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="metric-card">
                    <div class="metric-card-inner">
                        <div class="text-gray-400 text-sm uppercase tracking-wider mb-2">Active Calls</div>
                        <div class="text-4xl font-bold neon-text neon-green">{{ $systemMetrics['active_calls'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Last 5 minutes</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-card-inner">
                        <div class="text-gray-400 text-sm uppercase tracking-wider mb-2">Queue Size</div>
                        <div class="text-4xl font-bold neon-text 
                            @if($systemMetrics['queue_size'] < 50) neon-green
                            @elseif($systemMetrics['queue_size'] < 100) neon-yellow
                            @else neon-red
                            @endif">
                            {{ $systemMetrics['queue_size'] }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Pending jobs</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-card-inner">
                        <div class="text-gray-400 text-sm uppercase tracking-wider mb-2">Error Rate</div>
                        <div class="text-4xl font-bold neon-text 
                            @if($systemMetrics['error_rate'] < 0.01) neon-green
                            @elseif($systemMetrics['error_rate'] < 0.05) neon-yellow
                            @else neon-red
                            @endif">
                            {{ number_format($systemMetrics['error_rate'] * 100, 1) }}%
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Last hour</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-card-inner">
                        <div class="text-gray-400 text-sm uppercase tracking-wider mb-2">Response Time</div>
                        <div class="text-4xl font-bold neon-text 
                            @if($systemMetrics['response_time'] < 200) neon-green
                            @elseif($systemMetrics['response_time'] < 500) neon-yellow
                            @else neon-red
                            @endif">
                            {{ $systemMetrics['response_time'] }}ms
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Average</div>
                    </div>
                </div>
            </div>
            
            <!-- Service Status -->
            <div class="glass-card p-8 mb-12">
                <h2 class="neon-text neon-purple text-2xl font-bold mb-6">Service Status</h2>
                <div class="flex flex-wrap gap-4">
                    @foreach($serviceHealth as $service => $health)
                        <div class="service-pill">
                            <div class="status-dot 
                                @if($health >= 90) bg-green-400
                                @elseif($health >= 70) bg-yellow-400
                                @else bg-red-400
                                @endif">
                            </div>
                            <span class="text-gray-300 font-medium">{{ str_replace('_', ' ', ucfirst($service)) }}</span>
                            <span class="text-xs text-gray-500">{{ $health }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Companies Overview -->
            <div class="glass-card p-8">
                <h2 class="neon-text neon-purple text-2xl font-bold mb-6">Companies Overview</h2>
                <div class="flex flex-wrap justify-center gap-4">
                    @foreach($companyMetrics as $company)
                        <div class="company-hexagon" onclick="showCompanyDetail({{ json_encode($company) }})">
                            <div class="company-hexagon-inner">
                                <div class="company-hexagon-content">
                                    <h3 class="font-bold text-lg mb-2 text-white">{{ $company['name'] }}</h3>
                                    <div class="text-4xl font-bold mb-2
                                        @if($company['health'] >= 90) neon-green
                                        @elseif($company['health'] >= 70) neon-yellow
                                        @else neon-red
                                        @endif">
                                        {{ $company['health'] }}%
                                    </div>
                                    <div class="text-xs text-gray-400 space-y-1">
                                        <div>{{ $company['branch_count'] }} Branches</div>
                                        <div>{{ $company['active_staff'] }} Staff</div>
                                        <div>{{ $company['calls_today'] }} Calls</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Real-time Stats Ticker -->
            <div class="glass-card p-4">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-8">
                        <div>
                            <span class="text-gray-400">Calls/Min:</span>
                            <span class="font-bold neon-text neon-green ml-2">{{ $realtimeStats['calls_per_minute'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Appointments/Hour:</span>
                            <span class="font-bold neon-text neon-blue ml-2">{{ $realtimeStats['appointments_per_hour'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">New Customers:</span>
                            <span class="font-bold neon-text neon-purple ml-2">{{ $realtimeStats['new_customers_today'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Peak Hour:</span>
                            <span class="font-bold text-gray-300 ml-2">{{ $realtimeStats['peak_hour'] }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-400">Uptime:</span>
                        <span class="font-bold neon-text neon-green ml-2">{{ $systemMetrics['uptime'] }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Historical Data Charts -->
            @if(!empty($historicalData))
            <div class="glass-card p-8 mb-12">
                <h2 class="neon-text neon-purple text-2xl font-bold mb-6">Historical Analytics</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 24 Hour Activity Chart -->
                    <div class="bg-black/30 rounded-lg p-6">
                        <h3 class="text-gray-300 font-medium mb-4">24 Hour Activity</h3>
                        <canvas id="hourlyChart" height="200"></canvas>
                    </div>
                    
                    <!-- 7 Day Trend Chart -->
                    <div class="bg-black/30 rounded-lg p-6">
                        <h3 class="text-gray-300 font-medium mb-4">7 Day Trend</h3>
                        <canvas id="dailyChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Anomaly Detection & Alerts -->
            @if(!empty($anomalies))
            <div class="glass-card p-8 mb-12">
                <h2 class="neon-text neon-red text-2xl font-bold mb-6">System Anomalies & Alerts</h2>
                <div class="space-y-4">
                    @foreach($anomalies as $anomaly)
                        @if(isset($anomaly['severity']))
                        <div class="alert-card bg-{{ $anomaly['severity'] }}-900/20 border border-{{ $anomaly['severity'] }}-500/50 rounded-lg p-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    @if($anomaly['severity'] === 'critical')
                                        <div class="w-8 h-8 bg-red-500 rounded-full animate-pulse flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                    @elseif($anomaly['severity'] === 'warning')
                                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-{{ $anomaly['severity'] === 'critical' ? 'red' : ($anomaly['severity'] === 'warning' ? 'yellow' : 'blue') }}-400">
                                        {{ $anomaly['title'] ?? 'System Alert' }}
                                    </h4>
                                    <p class="text-gray-300 mt-1">{{ $anomaly['description'] ?? '' }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-400">
                                        <span>{{ $anomaly['timestamp'] ?? now()->format('H:i:s') }}</span>
                                        @if(isset($anomaly['affected_component']))
                                            <span>Component: {{ $anomaly['affected_component'] }}</span>
                                        @endif
                                        @if(isset($anomaly['recommended_action']))
                                            <span class="text-blue-400">Action: {{ $anomaly['recommended_action'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- System Recommendations -->
            @if(!empty($systemRecommendations))
            <div class="glass-card p-8 mb-12">
                <h2 class="neon-text neon-blue text-2xl font-bold mb-6">AI-Powered Recommendations</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($systemRecommendations as $recommendation)
                        @if(isset($recommendation['title']) && isset($recommendation['description']))
                        <div class="recommendation-card bg-gradient-to-br from-blue-900/30 to-purple-900/30 rounded-lg p-6 border border-blue-500/30">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-white">{{ $recommendation['title'] }}</h3>
                            </div>
                            <p class="text-gray-300 text-sm mb-3">{{ $recommendation['description'] }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Impact: <span class="text-{{ ($recommendation['impact'] ?? 'medium') === 'high' ? 'green' : (($recommendation['impact'] ?? 'medium') === 'medium' ? 'yellow' : 'blue') }}-400">{{ ucfirst($recommendation['impact'] ?? 'medium') }}</span></span>
                                @if(isset($recommendation['estimated_improvement']))
                                    <span class="text-xs text-green-400">+{{ $recommendation['estimated_improvement'] }}%</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Global System Network Visualization -->
            @if(!empty($globalSystemData))
            <div class="glass-card p-8">
                <h2 class="neon-text neon-purple text-2xl font-bold mb-6">Global System Network</h2>
                <div class="relative h-96 bg-black/50 rounded-lg overflow-hidden">
                    <canvas id="networkCanvas" class="w-full h-full"></canvas>
                    <div class="absolute top-4 right-4 bg-black/70 rounded-lg p-4">
                        <div class="text-sm space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                <span class="text-gray-300">Healthy (>90%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                <span class="text-gray-300">Warning (70-90%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                <span class="text-gray-300">Critical (<70%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Company Detail Modal -->
    <div id="companyDetailModal" class="company-detail" onclick="if(event.target === this) hideCompanyDetail()">
        <div class="company-detail-card">
            <button onclick="hideCompanyDetail()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div id="companyDetailContent">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Real-time polling for updates
        @if($refreshInterval > 0)
        setInterval(() => {
            @this.refresh();
        }, {{ $refreshInterval * 1000 }});
        @endif
        
        // Initialize charts when data is available
        document.addEventListener('DOMContentLoaded', function() {
            @if(!empty($historicalData))
                initializeCharts();
            @endif
            
            @if(!empty($globalSystemData))
                initializeNetworkVisualization();
            @endif
        });
        
        function initializeCharts() {
            // Hourly Activity Chart
            const hourlyCtx = document.getElementById('hourlyChart');
            if (hourlyCtx && @json($historicalData['hourly'] ?? [])) {
                new Chart(hourlyCtx, {
                    type: 'line',
                    data: {
                        labels: @json(array_column($historicalData['hourly'] ?? [], 'hour')),
                        datasets: [{
                            label: 'Calls',
                            data: @json(array_column($historicalData['hourly'] ?? [], 'calls')),
                            borderColor: '#00ff88',
                            backgroundColor: 'rgba(0, 255, 136, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Appointments',
                            data: @json(array_column($historicalData['hourly'] ?? [], 'appointments')),
                            borderColor: '#00d4ff',
                            backgroundColor: 'rgba(0, 212, 255, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#9CA3AF' }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { color: '#9CA3AF' }
                            },
                            y: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { color: '#9CA3AF' }
                            }
                        }
                    }
                });
            }
            
            // Daily Trend Chart
            const dailyCtx = document.getElementById('dailyChart');
            if (dailyCtx && @json($historicalData['daily'] ?? [])) {
                new Chart(dailyCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($historicalData['daily'] ?? [], 'date')),
                        datasets: [{
                            label: 'Calls',
                            data: @json(array_column($historicalData['daily'] ?? [], 'calls')),
                            backgroundColor: 'rgba(0, 255, 136, 0.5)',
                            borderColor: '#00ff88',
                            borderWidth: 1
                        }, {
                            label: 'Appointments',
                            data: @json(array_column($historicalData['daily'] ?? [], 'appointments')),
                            backgroundColor: 'rgba(0, 212, 255, 0.5)',
                            borderColor: '#00d4ff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#9CA3AF' }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { color: '#9CA3AF' }
                            },
                            y: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { color: '#9CA3AF' }
                            }
                        }
                    }
                });
            }
        }
        
        function initializeNetworkVisualization() {
            const canvas = document.getElementById('networkCanvas');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const nodes = @json($globalSystemData['nodes'] ?? []);
            const connections = @json($globalSystemData['connections'] ?? []);
            
            // Set canvas size
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            
            // Animation loop
            let animationFrame = 0;
            
            function drawNetwork() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw connections
                connections.forEach(conn => {
                    const sourceNode = nodes.find(n => n.id === conn.source);
                    const targetNode = nodes.find(n => n.id === conn.target);
                    
                    if (sourceNode && targetNode) {
                        ctx.beginPath();
                        ctx.moveTo(sourceNode.x || canvas.width/2, sourceNode.y || canvas.height/2);
                        ctx.lineTo(targetNode.x || canvas.width/2, targetNode.y || canvas.height/2);
                        ctx.strokeStyle = 'rgba(59, 130, 246, 0.3)';
                        ctx.stroke();
                        
                        // Animated data flow
                        const progress = (animationFrame % 100) / 100;
                        const x = sourceNode.x + (targetNode.x - sourceNode.x) * progress;
                        const y = sourceNode.y + (targetNode.y - sourceNode.y) * progress;
                        
                        ctx.beginPath();
                        ctx.arc(x, y, 3, 0, Math.PI * 2);
                        ctx.fillStyle = '#00ff88';
                        ctx.fill();
                    }
                });
                
                // Draw nodes
                nodes.forEach((node, index) => {
                    // Calculate position if not set
                    if (!node.x || !node.y) {
                        const angle = (index / nodes.length) * Math.PI * 2;
                        const radius = Math.min(canvas.width, canvas.height) * 0.3;
                        node.x = canvas.width/2 + Math.cos(angle) * radius;
                        node.y = canvas.height/2 + Math.sin(angle) * radius;
                    }
                    
                    // Node color based on health
                    let color = '#00ff88'; // green
                    if (node.health < 70) color = '#ff0040'; // red
                    else if (node.health < 90) color = '#ffea00'; // yellow
                    
                    // Draw node
                    ctx.beginPath();
                    ctx.arc(node.x, node.y, node.size || 20, 0, Math.PI * 2);
                    ctx.fillStyle = color + '40'; // 25% opacity
                    ctx.fill();
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    
                    // Draw label
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '12px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText(node.name, node.x, node.y + (node.size || 20) + 15);
                });
                
                animationFrame++;
                requestAnimationFrame(drawNetwork);
            }
            
            drawNetwork();
        }
        
        // Listen for Livewire refresh events
        Livewire.on('refreshed', () => {
            setTimeout(() => {
                initializeCharts();
                initializeNetworkVisualization();
            }, 100);
        });
        
        function showCompanyDetail(company) {
            const modal = document.getElementById('companyDetailModal');
            const content = document.getElementById('companyDetailContent');
            
            // Build detailed view HTML
            let branchesHtml = '';
            if (company.branches && company.branches.length > 0) {
                branchesHtml = company.branches.map(branch => {
                    const healthColor = branch.health >= 90 ? 'green' : (branch.health >= 70 ? 'yellow' : 'red');
                    const serviceStatuses = [];
                    
                    if (branch.services) {
                        if (branch.services.calcom_connected) {
                            serviceStatuses.push('<span class="text-green-400 text-xs">✓ Cal.com</span>');
                        } else {
                            serviceStatuses.push('<span class="text-red-400 text-xs">✗ Cal.com</span>');
                        }
                        
                        if (branch.services.staff_active) {
                            serviceStatuses.push('<span class="text-green-400 text-xs">✓ Personal</span>');
                        } else {
                            serviceStatuses.push('<span class="text-red-400 text-xs">✗ Personal</span>');
                        }
                        
                        if (branch.services.phone_assigned) {
                            serviceStatuses.push('<span class="text-green-400 text-xs">✓ Telefon</span>');
                        } else {
                            serviceStatuses.push('<span class="text-red-400 text-xs">✗ Telefon</span>');
                        }
                    }
                    
                    return `
                        <div class="glass-card p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-20 h-20 opacity-20">
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="${healthColor === 'green' ? '#00ff88' : (healthColor === 'yellow' ? '#ffea00' : '#ff0040')}" stroke-width="10" stroke-dasharray="${branch.health * 2.83} 283" transform="rotate(-90 50 50)"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-lg mb-2">${branch.name}</h4>
                            <div class="text-sm text-gray-400 space-y-2">
                                <div>📍 ${branch.city}${branch.address ? `, ${branch.address}` : ''}</div>
                                <div>👥 ${branch.staff_count} Mitarbeiter</div>
                                <div>📅 ${branch.appointments_today} Termine heute / ${branch.appointments_week} diese Woche</div>
                                <div class="flex gap-3 mt-3">
                                    ${serviceStatuses.join(' ')}
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <div class="text-xl font-bold ${healthColor === 'green' ? 'text-green-400' : (healthColor === 'yellow' ? 'text-yellow-400' : 'text-red-400')}">${branch.health}%</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="text-center">
                        <h2 class="neon-text neon-blue text-3xl font-bold mb-2">${company.name}</h2>
                        <div class="text-6xl font-bold ${company.health >= 90 ? 'neon-green' : (company.health >= 70 ? 'neon-yellow' : 'neon-red')}">
                            ${company.health}%
                        </div>
                        <div class="text-sm text-gray-400 mt-2">Mitglied seit ${company.created_at}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="glass-card p-4 text-center">
                            <div class="text-2xl font-bold neon-text neon-green">${company.calls_today}</div>
                            <div class="text-sm text-gray-400">Anrufe Heute</div>
                            <div class="text-xs text-gray-500">${company.calls_week} diese Woche</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div class="text-2xl font-bold neon-text neon-blue">${company.appointments_today}</div>
                            <div class="text-sm text-gray-400">Termine Heute</div>
                            <div class="text-xs text-gray-500">${company.appointments_week} diese Woche</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div class="text-2xl font-bold neon-text neon-purple">${company.active_staff}/${company.total_staff}</div>
                            <div class="text-sm text-gray-400">Aktive Mitarbeiter</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div class="text-2xl font-bold neon-text neon-yellow">${company.branch_count}</div>
                            <div class="text-sm text-gray-400">Filialen</div>
                            <div class="text-xs text-gray-500">${company.phone_numbers} Telefonnummern</div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="neon-text neon-purple text-xl font-bold mb-4">System-Verbindungen</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.retell_ai ? 'bg-green-400' : 'bg-red-400'}"></div>
                                <span>Retell AI</span>
                            </div>
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.calcom ? 'bg-green-400' : 'bg-red-400'}"></div>
                                <span>Cal.com</span>
                            </div>
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.email ? 'bg-green-400' : 'bg-red-400'}"></div>
                                <span>Email Service</span>
                            </div>
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.sms ? 'bg-green-400' : 'bg-gray-400'}"></div>
                                <span>SMS Gateway</span>
                            </div>
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.whatsapp ? 'bg-green-400' : 'bg-gray-400'}"></div>
                                <span>WhatsApp</span>
                            </div>
                            <div class="service-pill justify-center">
                                <div class="status-dot ${company.integrations.api ? 'bg-green-400' : 'bg-red-400'}"></div>
                                <span>API Access</span>
                            </div>
                        </div>
                    </div>
                    
                    ${branchesHtml ? `
                        <div>
                            <h3 class="neon-text neon-purple text-xl font-bold mb-4">Filialen & Standorte</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                ${branchesHtml}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div>
                        <h3 class="neon-text neon-purple text-xl font-bold mb-4">Performance Trend</h3>
                        <div class="glass-card p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Last 7 Days</span>
                                <span class="text-green-400 font-bold">↑ ${company.performance_trend ? company.performance_trend.growth : 'N/A'}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            modal.classList.add('active');
        }
        
        function hideCompanyDetail() {
            document.getElementById('companyDetailModal').classList.remove('active');
        }
    </script>
    @endpush
</x-filament-panels::page>