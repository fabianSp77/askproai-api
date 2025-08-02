<x-filament-panels::page>
    @push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;700&display=swap');
        
        /* Neural Command Center Theme */
        * {
            box-sizing: border-box;
        }
        
        body {
            overflow-x: hidden;
        }
        
        .neural-command-center {
            background: #000;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            font-family: 'Rajdhani', sans-serif;
            perspective: 1000px;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.02) 0%, transparent 50%);
        }
        
        /* Matrix Rain Background */
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: monospace;
            font-size: 16px;
            line-height: 1.2;
            color: #0f0;
            text-shadow: 0 0 5px #0f0;
            animation: matrix-fall linear infinite;
            opacity: 0.8;
        }
        
        @keyframes matrix-fall {
            to { top: 100%; }
        }
        
        /* Holographic Container */
        .holo-container {
            position: relative;
            z-index: 10;
            display: grid;
            gap: 30px;
            padding: 30px;
            transform-style: preserve-3d;
        }
        
        /* Holographic Panel */
        .holo-panel {
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.1) 0%, rgba(255, 0, 255, 0.1) 100%);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 0 40px rgba(0, 255, 255, 0.3),
                inset 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateZ(20px);
            transition: all 0.3s ease;
        }
        
        .holo-panel:hover {
            transform: translateZ(40px) rotateX(-2deg) rotateY(2deg);
            box-shadow: 
                0 0 60px rgba(0, 255, 255, 0.5),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }
        
        /* Glitch Effect */
        .glitch {
            position: relative;
            animation: glitch 5s infinite;
        }
        
        .glitch::before,
        .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .glitch::before {
            animation: glitch-1 0.5s infinite;
            color: #0ff;
            z-index: -1;
        }
        
        .glitch::after {
            animation: glitch-2 0.5s infinite;
            color: #f0f;
            z-index: -2;
        }
        
        @keyframes glitch {
            0%, 90%, 100% { opacity: 1; }
            92% { opacity: 0.8; }
            94% { opacity: 1; }
            96% { opacity: 0.6; }
            98% { opacity: 1; }
        }
        
        @keyframes glitch-1 {
            0%, 100% { clip-path: inset(0 0 0 0); transform: translate(0); }
            20% { clip-path: inset(10% 0 50% 0); transform: translate(-2px, 2px); }
            40% { clip-path: inset(50% 0 20% 0); transform: translate(2px, -2px); }
            60% { clip-path: inset(30% 0 40% 0); transform: translate(-2px, 0); }
            80% { clip-path: inset(60% 0 10% 0); transform: translate(2px, 0); }
        }
        
        @keyframes glitch-2 {
            0%, 100% { clip-path: inset(0 0 0 0); transform: translate(0); }
            20% { clip-path: inset(60% 0 10% 0); transform: translate(2px, -2px); }
            40% { clip-path: inset(20% 0 60% 0); transform: translate(-2px, 2px); }
            60% { clip-path: inset(40% 0 30% 0); transform: translate(2px, 0); }
            80% { clip-path: inset(10% 0 70% 0); transform: translate(-2px, 0); }
        }
        
        /* Neural Network Visualization */
        .neural-network {
            position: relative;
            width: 100%;
            height: 600px;
            background: radial-gradient(ellipse at center, rgba(0, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 20px;
            overflow: hidden;
        }
        
        #neuralCanvas {
            width: 100%;
            height: 100%;
        }
        
        /* Cyber Stats */
        .cyber-stat {
            background: linear-gradient(45deg, #00ffff20, #ff00ff20);
            border: 1px solid #00ffff40;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .cyber-stat::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #00ff00, #ff0000);
            border-radius: 15px;
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
            animation: rotate-border 4s linear infinite;
        }
        
        @keyframes rotate-border {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cyber-stat:hover::before {
            opacity: 1;
        }
        
        .cyber-stat-value {
            font-size: 3em;
            font-weight: 900;
            font-family: 'Orbitron', monospace;
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        
        /* Threat Level Indicator */
        .threat-level {
            width: 300px;
            height: 300px;
            position: relative;
            margin: 0 auto;
        }
        
        .threat-ring {
            position: absolute;
            border-radius: 50%;
            border: 2px solid;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .threat-ring.outer {
            width: 100%;
            height: 100%;
            border-color: #00ffff;
            animation-delay: 0s;
        }
        
        .threat-ring.middle {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border-color: #ff00ff;
            animation-delay: 0.5s;
        }
        
        .threat-ring.inner {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            border-color: #00ff00;
            animation-delay: 1s;
        }
        
        .threat-core {
            position: absolute;
            width: 40%;
            height: 40%;
            top: 30%;
            left: 30%;
            background: radial-gradient(circle, #fff 0%, #00ffff 50%, transparent 70%);
            border-radius: 50%;
            animation: core-pulse 1s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }
        
        @keyframes core-pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px #00ffff; }
            50% { transform: scale(1.2); box-shadow: 0 0 40px #00ffff; }
        }
        
        /* Data Stream Visualization */
        .data-stream {
            height: 100px;
            background: linear-gradient(90deg, transparent 0%, rgba(0, 255, 255, 0.1) 50%, transparent 100%);
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }
        
        .data-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00ffff;
            border-radius: 50%;
            box-shadow: 0 0 10px #00ffff;
            animation: flow 3s linear infinite;
        }
        
        @keyframes flow {
            0% { left: -10px; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { left: 100%; opacity: 0; }
        }
        
        /* Holographic Button */
        .holo-button {
            background: linear-gradient(45deg, rgba(0, 255, 255, 0.2), rgba(255, 0, 255, 0.2));
            border: 2px solid #00ffff;
            color: #00ffff;
            padding: 15px 30px;
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 50px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .holo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 30px rgba(0, 255, 255, 0.5);
            text-shadow: 0 0 10px #00ffff;
        }
        
        .holo-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        
        .holo-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Terminal Window */
        .terminal {
            background: #000;
            border: 1px solid #0f0;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            color: #0f0;
            overflow: auto;
            max-height: 300px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        
        .terminal-line {
            margin: 5px 0;
            opacity: 0;
            animation: type-in 0.1s forwards;
        }
        
        @keyframes type-in {
            to { opacity: 1; }
        }
        
        /* 3D Globe */
        .globe-container {
            width: 400px;
            height: 400px;
            margin: 0 auto;
            position: relative;
        }
        
        #globeCanvas {
            width: 100%;
            height: 100%;
        }
        
        /* Notification Style */
        .cyber-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid #00ffff;
            border-radius: 10px;
            padding: 20px;
            color: #00ffff;
            font-family: 'Orbitron', monospace;
            max-width: 300px;
            z-index: 1000;
            animation: slide-in 0.5s ease;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        
        @keyframes slide-in {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Loading Animation */
        .loading-dna {
            width: 60px;
            height: 60px;
            margin: 20px auto;
            position: relative;
        }
        
        .loading-dna::before,
        .loading-dna::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #00ffff;
            animation: dna-spin 2s linear infinite;
        }
        
        .loading-dna::after {
            border-top-color: #ff00ff;
            animation-delay: 0.5s;
        }
        
        @keyframes dna-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Hexagon Grid */
        .hex-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px 0;
        }
        
        .hexagon {
            width: 100px;
            height: 57.74px;
            background: linear-gradient(45deg, rgba(0, 255, 255, 0.2), rgba(255, 0, 255, 0.2));
            position: relative;
            margin: 28.87px 5px;
            border-left: 1px solid #00ffff;
            border-right: 1px solid #00ffff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .hexagon:before,
        .hexagon:after {
            content: "";
            position: absolute;
            width: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            left: 0;
        }
        
        .hexagon:before {
            bottom: 100%;
            border-bottom: 28.87px solid;
            border-bottom-color: inherit;
        }
        
        .hexagon:after {
            top: 100%;
            border-top: 28.87px solid;
            border-top-color: inherit;
        }
        
        .hexagon:hover {
            transform: scale(1.1) translateZ(20px);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .neural-network {
                height: 400px;
            }
            
            .cyber-stat-value {
                font-size: 2em;
            }
            
            .globe-container {
                width: 300px;
                height: 300px;
            }
        }
    </style>
    @endpush
    
    <div class="neural-command-center">
        <!-- Matrix Rain Effect -->
        <div class="matrix-rain" id="matrixRain"></div>
        
        <div class="holo-container">
            <!-- Main Header -->
            <div class="text-center mb-8">
                <h1 class="text-6xl font-black glitch" data-text="NEURAL COMMAND CENTER">
                    <span style="font-family: 'Orbitron', monospace; color: #00ffff; text-shadow: 0 0 20px #00ffff;">
                        NEURAL COMMAND CENTER
                    </span>
                </h1>
                <p class="text-xl mt-2" style="color: #ff00ff; font-family: 'Rajdhani', sans-serif;">
                    ASKPROAI SYSTEM NEXUS // STATUS: <span id="systemStatus" style="color: #00ff00;">ONLINE</span>
                </p>
            </div>
            
            <!-- Critical System Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <div class="cyber-stat">
                    <div class="text-sm uppercase" style="color: #00ffff;">System Health</div>
                    <div class="cyber-stat-value">{{ $systemMetrics['overall_health'] ?? 0 }}%</div>
                    <div class="data-stream mt-2">
                        @for($i = 0; $i < 10; $i++)
                            <div class="data-particle" style="animation-delay: {{ $i * 0.3 }}s;"></div>
                        @endfor
                    </div>
                </div>
                
                <div class="cyber-stat">
                    <div class="text-sm uppercase" style="color: #ff00ff;">Neural Activity</div>
                    <div class="cyber-stat-value">{{ $realtimeStats['calls_per_minute'] ?? 0 }}</div>
                    <div class="text-xs" style="color: #00ff00;">CALLS/MIN</div>
                </div>
                
                <div class="cyber-stat">
                    <div class="text-sm uppercase" style="color: #00ff00;">Data Throughput</div>
                    <div class="cyber-stat-value">{{ number_format($systemMetrics['response_time'] ?? 0) }}ms</div>
                    <div class="loading-dna"></div>
                </div>
                
                <div class="cyber-stat">
                    <div class="text-sm uppercase" style="color: #ff0000;">Threat Level</div>
                    <div class="cyber-stat-value">
                        @php
                            $errorRate = $systemMetrics['error_rate'] ?? 0;
                            $threatLevel = $errorRate > 0.05 ? 'HIGH' : ($errorRate > 0.02 ? 'MEDIUM' : 'LOW');
                            $threatColor = $errorRate > 0.05 ? '#ff0000' : ($errorRate > 0.02 ? '#ffff00' : '#00ff00');
                        @endphp
                        <span style="color: {{ $threatColor }};">{{ $threatLevel }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Neural Network Visualization -->
            <div class="holo-panel mb-8">
                <h2 class="text-3xl font-bold mb-6" style="font-family: 'Orbitron', monospace; color: #00ffff;">
                    LIVE NEURAL NETWORK
                </h2>
                <div class="neural-network">
                    <canvas id="neuralCanvas"></canvas>
                </div>
            </div>
            
            <!-- Company Data Flow Matrix -->
            <div class="holo-panel mb-8">
                <h2 class="text-3xl font-bold mb-6" style="font-family: 'Orbitron', monospace; color: #ff00ff;">
                    DATA FLOW MATRIX
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($companyMetrics as $company)
                        <div class="holo-panel" style="background: rgba(0, 255, 255, 0.05);">
                            <h3 class="text-xl font-bold glitch" data-text="{{ $company['name'] }}" 
                                style="color: #00ffff; font-family: 'Orbitron', monospace;">
                                {{ $company['name'] }}
                            </h3>
                            
                            <!-- Company Health Indicator -->
                            <div class="threat-level mt-4" style="width: 150px; height: 150px;">
                                <div class="threat-ring outer"></div>
                                <div class="threat-ring middle"></div>
                                <div class="threat-ring inner"></div>
                                <div class="threat-core"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-3xl font-bold" style="color: #fff;">{{ $company['health'] }}%</span>
                                </div>
                            </div>
                            
                            <!-- Data Flow Visualization -->
                            <div class="mt-6">
                                <div class="flex justify-between mb-2">
                                    <span style="color: #00ff00;">INCOMING</span>
                                    <span style="color: #00ffff;">{{ $company['calls_today'] }} calls</span>
                                </div>
                                <div class="data-stream">
                                    @for($i = 0; $i < min(10, $company['calls_today']); $i++)
                                        <div class="data-particle" style="animation-delay: {{ $i * 0.2 }}s; background: #00ff00;"></div>
                                    @endfor
                                </div>
                                
                                <div class="flex justify-between mb-2 mt-4">
                                    <span style="color: #ff00ff;">PROCESSING</span>
                                    <span style="color: #00ffff;">{{ $company['appointments_today'] }} appointments</span>
                                </div>
                                <div class="data-stream">
                                    @for($i = 0; $i < min(10, $company['appointments_today']); $i++)
                                        <div class="data-particle" style="animation-delay: {{ $i * 0.3 }}s; background: #ff00ff;"></div>
                                    @endfor
                                </div>
                            </div>
                            
                            <!-- Branch Network -->
                            <div class="hex-grid mt-6">
                                @foreach($company['branches'] as $branch)
                                    <div class="hexagon" 
                                         style="background: linear-gradient(45deg, rgba(0, 255, 255, {{ $branch['health'] / 100 }}), rgba(255, 0, 255, {{ $branch['health'] / 100 }}));"
                                         title="{{ $branch['name'] }} - {{ $branch['city'] }}">
                                        <div class="absolute inset-0 flex items-center justify-center text-xs" style="color: #fff;">
                                            {{ $branch['health'] }}%
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- AI Threat Detection Terminal -->
            @if(!empty($anomalies))
            <div class="holo-panel mb-8">
                <h2 class="text-3xl font-bold mb-6" style="font-family: 'Orbitron', monospace; color: #ff0000;">
                    THREAT DETECTION SYSTEM
                </h2>
                <div class="terminal">
                    @foreach($anomalies as $index => $anomaly)
                        <div class="terminal-line" style="animation-delay: {{ $index * 0.1 }}s;">
                            [{{ $anomaly['timestamp'] ?? now()->format('H:i:s') }}] 
                            <span style="color: {{ $anomaly['severity'] == 'critical' ? '#ff0000' : ($anomaly['severity'] == 'warning' ? '#ffff00' : '#00ff00') }};">
                                {{ strtoupper($anomaly['severity']) }}
                            </span>: 
                            {{ $anomaly['title'] }} - {{ $anomaly['description'] }}
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Global System Network -->
            <div class="holo-panel mb-8">
                <h2 class="text-3xl font-bold mb-6" style="font-family: 'Orbitron', monospace; color: #00ff00;">
                    GLOBAL SYSTEM NETWORK
                </h2>
                <div class="globe-container">
                    <canvas id="globeCanvas"></canvas>
                </div>
                <div class="grid grid-cols-4 gap-4 mt-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #00ffff;">{{ $globalSystemData['stats']['total_companies'] ?? 0 }}</div>
                        <div class="text-xs uppercase" style="color: #00ff00;">COMPANIES</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #ff00ff;">{{ $globalSystemData['stats']['total_branches'] ?? 0 }}</div>
                        <div class="text-xs uppercase" style="color: #00ff00;">BRANCHES</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #00ffff;">{{ $globalSystemData['stats']['total_staff'] ?? 0 }}</div>
                        <div class="text-xs uppercase" style="color: #00ff00;">AGENTS</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold" style="color: #ff00ff;">{{ $globalSystemData['stats']['total_calls_today'] ?? 0 }}</div>
                        <div class="text-xs uppercase" style="color: #00ff00;">CALLS TODAY</div>
                    </div>
                </div>
            </div>
            
            <!-- AI Recommendations -->
            @if(!empty($systemRecommendations))
            <div class="holo-panel">
                <h2 class="text-3xl font-bold mb-6" style="font-family: 'Orbitron', monospace; color: #ffff00;">
                    AI OPTIMIZATION PROTOCOL
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($systemRecommendations as $rec)
                        <div class="cyber-stat">
                            <h3 class="text-lg font-bold" style="color: #00ffff;">{{ $rec['title'] }}</h3>
                            <p class="mt-2" style="color: #00ff00;">{{ $rec['description'] }}</p>
                            <div class="mt-4 flex justify-between">
                                <span style="color: #ff00ff;">IMPACT: {{ strtoupper($rec['impact']) }}</span>
                                <span style="color: #00ffff;">+{{ $rec['estimated_improvement'] }}% PERFORMANCE</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // Matrix Rain Effect
        function createMatrixRain() {
            const container = document.getElementById('matrixRain');
            const columns = Math.floor(window.innerWidth / 20);
            
            for (let i = 0; i < columns; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = i * 20 + 'px';
                column.style.animationDuration = (Math.random() * 15 + 10) + 's';
                column.style.animationDelay = Math.random() * 10 + 's';
                
                // Random characters
                let chars = '';
                for (let j = 0; j < 50; j++) {
                    chars += String.fromCharCode(0x30A0 + Math.random() * 96);
                    chars += '\n';
                }
                column.textContent = chars;
                
                container.appendChild(column);
            }
        }
        
        // Neural Network Canvas
        function initNeuralNetwork() {
            const canvas = document.getElementById('neuralCanvas');
            const ctx = canvas.getContext('2d');
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            
            const nodes = [];
            const connections = [];
            
            // Create nodes
            for (let i = 0; i < 50; i++) {
                nodes.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    radius: Math.random() * 3 + 2,
                    pulsePhase: Math.random() * Math.PI * 2
                });
            }
            
            // Create connections
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const dist = Math.sqrt(
                        Math.pow(nodes[i].x - nodes[j].x, 2) + 
                        Math.pow(nodes[i].y - nodes[j].y, 2)
                    );
                    if (dist < 150) {
                        connections.push({ from: i, to: j });
                    }
                }
            }
            
            function animate() {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Update nodes
                nodes.forEach(node => {
                    node.x += node.vx;
                    node.y += node.vy;
                    node.pulsePhase += 0.05;
                    
                    if (node.x < 0 || node.x > canvas.width) node.vx *= -1;
                    if (node.y < 0 || node.y > canvas.height) node.vy *= -1;
                });
                
                // Draw connections
                ctx.strokeStyle = 'rgba(0, 255, 255, 0.2)';
                ctx.lineWidth = 1;
                connections.forEach(conn => {
                    const from = nodes[conn.from];
                    const to = nodes[conn.to];
                    const dist = Math.sqrt(
                        Math.pow(from.x - to.x, 2) + 
                        Math.pow(from.y - to.y, 2)
                    );
                    
                    if (dist < 150) {
                        ctx.globalAlpha = 1 - dist / 150;
                        ctx.beginPath();
                        ctx.moveTo(from.x, from.y);
                        ctx.lineTo(to.x, to.y);
                        ctx.stroke();
                        
                        // Data flow particles
                        if (Math.random() < 0.01) {
                            const particle = {
                                x: from.x,
                                y: from.y,
                                targetX: to.x,
                                targetY: to.y,
                                progress: 0
                            };
                            animateDataFlow(ctx, particle);
                        }
                    }
                });
                
                // Draw nodes
                ctx.globalAlpha = 1;
                nodes.forEach(node => {
                    const pulse = Math.sin(node.pulsePhase) * 0.5 + 0.5;
                    const radius = node.radius * (1 + pulse * 0.5);
                    
                    ctx.beginPath();
                    ctx.arc(node.x, node.y, radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(0, 255, 255, ${0.5 + pulse * 0.5})`;
                    ctx.fill();
                    
                    ctx.beginPath();
                    ctx.arc(node.x, node.y, radius * 1.5, 0, Math.PI * 2);
                    ctx.strokeStyle = `rgba(255, 0, 255, ${0.3 * pulse})`;
                    ctx.lineWidth = 2;
                    ctx.stroke();
                });
                
                requestAnimationFrame(animate);
            }
            
            animate();
        }
        
        function animateDataFlow(ctx, particle) {
            function move() {
                particle.progress += 0.02;
                if (particle.progress <= 1) {
                    const x = particle.x + (particle.targetX - particle.x) * particle.progress;
                    const y = particle.y + (particle.targetY - particle.y) * particle.progress;
                    
                    ctx.beginPath();
                    ctx.arc(x, y, 3, 0, Math.PI * 2);
                    ctx.fillStyle = '#00ff00';
                    ctx.fill();
                    
                    requestAnimationFrame(move);
                }
            }
            move();
        }
        
        // 3D Globe
        function init3DGlobe() {
            const container = document.getElementById('globeCanvas');
            const width = container.offsetWidth;
            const height = container.offsetHeight;
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(width, height);
            container.appendChild(renderer.domElement);
            
            // Create globe
            const geometry = new THREE.SphereGeometry(5, 32, 32);
            const material = new THREE.MeshPhongMaterial({
                color: 0x00ffff,
                wireframe: true,
                opacity: 0.3,
                transparent: true
            });
            const globe = new THREE.Mesh(geometry, material);
            scene.add(globe);
            
            // Add points for locations
            const pointsData = @json($globalSystemData['nodes'] ?? []);
            const points = new THREE.Group();
            
            pointsData.forEach(point => {
                const lat = (point.lat || 0) * Math.PI / 180;
                const lng = (point.lng || 0) * Math.PI / 180;
                const radius = 5.1;
                
                const x = radius * Math.cos(lat) * Math.cos(lng);
                const y = radius * Math.sin(lat);
                const z = radius * Math.cos(lat) * Math.sin(lng);
                
                const pointGeometry = new THREE.SphereGeometry(0.1, 8, 8);
                const pointMaterial = new THREE.MeshBasicMaterial({
                    color: point.type === 'company' ? 0xff00ff : 0x00ff00
                });
                const pointMesh = new THREE.Mesh(pointGeometry, pointMaterial);
                pointMesh.position.set(x, y, z);
                points.add(pointMesh);
            });
            
            scene.add(points);
            
            // Lights
            const ambientLight = new THREE.AmbientLight(0x404040);
            scene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0x00ffff, 1);
            directionalLight.position.set(1, 1, 1);
            scene.add(directionalLight);
            
            camera.position.z = 10;
            
            function animate() {
                requestAnimationFrame(animate);
                globe.rotation.y += 0.005;
                points.rotation.y += 0.005;
                renderer.render(scene, camera);
            }
            
            animate();
        }
        
        // Sound Effects (optional)
        function playSound(type) {
            // Add sound effects for interactions
            const audio = new Audio();
            switch(type) {
                case 'hover':
                    // audio.src = '/sounds/hover.mp3';
                    break;
                case 'click':
                    // audio.src = '/sounds/click.mp3';
                    break;
                case 'alert':
                    // audio.src = '/sounds/alert.mp3';
                    break;
            }
            // audio.play();
        }
        
        // Real-time updates
        @if($refreshInterval > 0)
        setInterval(() => {
            @this.refresh();
            
            // Add visual feedback
            document.getElementById('systemStatus').style.color = '#ffff00';
            document.getElementById('systemStatus').textContent = 'UPDATING...';
            
            setTimeout(() => {
                document.getElementById('systemStatus').style.color = '#00ff00';
                document.getElementById('systemStatus').textContent = 'ONLINE';
            }, 30000);
        }, {{ $refreshInterval * 1000 }});
        @endif
        
        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            createMatrixRain();
            initNeuralNetwork();
            init3DGlobe();
            
            // Add hover sounds to buttons
            document.querySelectorAll('.holo-button, .cyber-stat, .hexagon').forEach(elem => {
                elem.addEventListener('mouseenter', () => playSound('hover'));
                elem.addEventListener('click', () => playSound('click'));
            });
            
            // Simulate incoming alerts
            @if(!empty($anomalies))
            setTimeout(() => {
                playSound('alert');
                const notification = document.createElement('div');
                notification.className = 'cyber-notification';
                notification.innerHTML = `
                    <div class="font-bold mb-2">SYSTEM ALERT</div>
                    <div>New anomaly detected in neural network</div>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slide-in 0.5s ease reverse';
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            }, 3000);
            @endif
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        @this.refresh();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.documentElement.requestFullscreen();
                        break;
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>