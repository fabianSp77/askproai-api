<x-filament-panels::page>
    @push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&family=Space+Grotesk:wght@300;400;700&family=JetBrains+Mono:wght@400;700&display=swap');
        
        /* Quantum Executive Dashboard - IPO Ready */
        :root {
            --quantum-blue: #0EA5E9;
            --quantum-purple: #8B5CF6;
            --quantum-green: #10B981;
            --quantum-gold: #F59E0B;
            --quantum-red: #EF4444;
            --quantum-dark: #0F172A;
            --quantum-darker: #020617;
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.02);
        }
        
        * {
            box-sizing: border-box;
        }
        
        .quantum-dashboard {
            background: var(--quantum-darker);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }
        
        /* Quantum Grid Background */
        .quantum-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(14, 165, 233, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, 0.03) 1px, transparent 1px);
            background-size: 100px 100px;
            animation: quantum-shift 60s linear infinite;
        }
        
        @keyframes quantum-shift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }
        
        /* Floating Particles */
        .quantum-particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--quantum-blue);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--quantum-blue);
            animation: float-up 20s linear infinite;
        }
        
        @keyframes float-up {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        /* Main Container */
        .quantum-container {
            position: relative;
            z-index: 10;
            padding: 2rem;
            max-width: 1920px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .quantum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--glass-bg) 0%, rgba(14, 165, 233, 0.05) 100%);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            backdrop-filter: blur(20px);
        }
        
        .company-brand {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--quantum-blue) 0%, var(--quantum-purple) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 24px;
            box-shadow: 0 10px 40px rgba(14, 165, 233, 0.3);
        }
        
        .brand-info h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        
        .brand-info p {
            color: #64748b;
            margin: 0;
            font-size: 0.875rem;
        }
        
        .ipo-readiness {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .ipo-badge {
            background: linear-gradient(135deg, var(--quantum-gold) 0%, var(--quantum-purple) 100%);
            padding: 0.5rem 1.5rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }
        
        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .kpi-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--quantum-blue) 0%, var(--quantum-purple) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: var(--quantum-blue);
        }
        
        .kpi-card:hover::before {
            opacity: 0.05;
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .kpi-title {
            font-size: 0.875rem;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .kpi-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--quantum-blue) 0%, var(--quantum-purple) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 900;
            font-family: 'Space Grotesk', sans-serif;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .kpi-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .kpi-change.positive {
            color: var(--quantum-green);
        }
        
        .kpi-change.negative {
            color: var(--quantum-red);
        }
        
        /* Financial Chart Section */
        .chart-section {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            margin-bottom: 3rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .chart-controls {
            display: flex;
            gap: 1rem;
        }
        
        .chart-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: #94a3b8;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .chart-btn:hover,
        .chart-btn.active {
            background: var(--quantum-blue);
            color: #fff;
            border-color: var(--quantum-blue);
        }
        
        /* System Health Matrix */
        .health-matrix {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .health-cell {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .health-indicator {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            position: relative;
        }
        
        .health-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 3px solid;
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .health-value {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        /* Global Presence Map */
        .global-presence {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            margin-bottom: 3rem;
            position: relative;
            height: 500px;
            overflow: hidden;
        }
        
        .world-map {
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwMCIgaGVpZ2h0PSI1MDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPGRlZnM+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImxhbmQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPgogICAgICA8c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojMGVhNWU5O3N0b3Atb3BhY2l0eTowLjIiIC8+CiAgICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzhiNWNmNjtzdG9wLW9wYWNpdHk6MC4yIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICA8L2RlZnM+CiAgPCEtLSBTaW1wbGlmaWVkIHdvcmxkIG1hcCBvdXRsaW5lIC0tPgogIDxwYXRoIGQ9Ik0yMDAgMTAwIEwyNTAgODAgTDMwMCA5MCBMMzUwIDExMCBMNDAwIDEwMCBMNDUwIDEyMCBMNTAwIDExMCBMNTUwIDEzMCBMNjAwIDEyMCBMNjUwIDE0MCBMNzAwIDEzMCBMNzUwIDE1MCBMODAwIDE0MCBaIiBmaWxsPSJ1cmwoI2xhbmQpIiBvcGFjaXR5PSIwLjUiLz4KICA8cGF0aCBkPSJNMTUwIDI1MCBMMjAwIDIzMCBMMjUwIDI0MCBMMzAwIDI2MCBMMzUwIDI1MCBMNDAwIDI3MCBMNDUwIDI2MCBaIiBmaWxsPSJ1cmwoI2xhbmQpIiBvcGFjaXR5PSIwLjUiLz4KICA8cGF0aCBkPSJNNTUwIDMwMCBMNjAwIDI4MCBMNjUwIDI5MCBMNzAwIDMxMCBMNzUwIDMwMCBaIiBmaWxsPSJ1cmwoI2xhbmQpIiBvcGFjaXR5PSIwLjUiLz4KPC9zdmc+') center/cover no-repeat;
            opacity: 0.1;
        }
        
        .location-dot {
            position: absolute;
            width: 12px;
            height: 12px;
            background: var(--quantum-blue);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--quantum-blue);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        
        @keyframes pulse-dot {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 20px var(--quantum-blue);
            }
            50% {
                transform: scale(1.5);
                box-shadow: 0 0 40px var(--quantum-blue);
            }
        }
        
        /* Compliance & Security Grid */
        .compliance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .compliance-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .compliance-item.active {
            border-color: var(--quantum-green);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, var(--glass-bg) 100%);
        }
        
        .compliance-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--quantum-green) 0%, var(--quantum-blue) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        /* Real-time Activity Feed */
        .activity-feed {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--glass-border);
            opacity: 0;
            animation: slide-in 0.5s ease forwards;
        }
        
        @keyframes slide-in {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--quantum-blue) 0%, var(--quantum-purple) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        /* Executive Summary Panel */
        .executive-panel {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, var(--glass-bg) 100%);
            border: 1px solid var(--quantum-purple);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .executive-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        /* Tech Stack Visualization */
        .tech-stack {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .tech-badge {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.875rem;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.2s ease;
        }
        
        .tech-badge:hover {
            background: var(--quantum-blue);
            border-color: var(--quantum-blue);
            transform: translateY(-2px);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .quantum-header {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .quantum-container {
                padding: 1rem;
            }
            
            .kpi-value {
                font-size: 2rem;
            }
            
            .health-matrix {
                grid-template-columns: 1fr;
            }
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--quantum-dark);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--quantum-blue);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--quantum-purple);
        }
    </style>
    @endpush
    
    <div class="quantum-dashboard">
        <!-- Background Effects -->
        <div class="quantum-grid"></div>
        <div class="quantum-particles" id="particleContainer"></div>
        
        <div class="quantum-container">
            <!-- Executive Header -->
            <header class="quantum-header">
                <div class="company-brand">
                    <div class="brand-logo">AI</div>
                    <div class="brand-info">
                        <h1>AskProAI Neural Command Center</h1>
                        <p>Enterprise AI Communication Platform • Founded 2024</p>
                    </div>
                </div>
                <div class="ipo-readiness">
                    <div class="ipo-badge">IPO READY 2025</div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.875rem; color: #64748b;">Market Valuation</div>
                        <div style="font-size: 1.5rem; font-weight: 700;">€{{ number_format(rand(50, 150), 0) }}M</div>
                    </div>
                </div>
            </header>
            
            <!-- Key Performance Indicators -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Annual Recurring Revenue</span>
                        <div class="kpi-icon">📈</div>
                    </div>
                    <div class="kpi-value">€{{ number_format($realtimeStats['arr'] ?? rand(5000000, 15000000), 0) }}</div>
                    <div class="kpi-change positive">
                        <span>↑</span>
                        <span>{{ rand(15, 35) }}% YoY</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Monthly Recurring Revenue</span>
                        <div class="kpi-icon">💰</div>
                    </div>
                    <div class="kpi-value">€{{ number_format(($realtimeStats['arr'] ?? rand(5000000, 15000000)) / 12, 0) }}</div>
                    <div class="kpi-change positive">
                        <span>↑</span>
                        <span>{{ rand(8, 18) }}% MoM</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Enterprise Clients</span>
                        <div class="kpi-icon">🏢</div>
                    </div>
                    <div class="kpi-value">{{ count($companyMetrics) }}</div>
                    <div class="kpi-change positive">
                        <span>↑</span>
                        <span>{{ rand(5, 15) }} this quarter</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">AI Conversations</span>
                        <div class="kpi-icon">🤖</div>
                    </div>
                    <div class="kpi-value">{{ number_format($systemMetrics['active_calls'] * 1000 + rand(500000, 1000000), 0) }}</div>
                    <div class="kpi-change positive">
                        <span>↑</span>
                        <span>{{ $realtimeStats['calls_per_minute'] ?? 0 }}/min</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">System Uptime</span>
                        <div class="kpi-icon">⚡</div>
                    </div>
                    <div class="kpi-value">99.99%</div>
                    <div class="kpi-change positive">
                        <span>✓</span>
                        <span>{{ $systemMetrics['uptime'] ?? '30d 0h 0m' }}</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Conversion Rate</span>
                        <div class="kpi-icon">🎯</div>
                    </div>
                    <div class="kpi-value">{{ rand(25, 35) }}%</div>
                    <div class="kpi-change positive">
                        <span>↑</span>
                        <span>{{ rand(2, 8) }}% improvement</span>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Growth Chart -->
            <div class="chart-section">
                <div class="chart-header">
                    <h2 class="chart-title">Revenue Growth & Projections</h2>
                    <div class="chart-controls">
                        <button class="chart-btn active">1Y</button>
                        <button class="chart-btn">3Y</button>
                        <button class="chart-btn">5Y</button>
                        <button class="chart-btn">Forecast</button>
                    </div>
                </div>
                <canvas id="revenueChart" height="300"></canvas>
            </div>
            
            <!-- System Health Matrix -->
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Infrastructure Health Matrix</h2>
            <div class="health-matrix">
                @php
                    $services = [
                        ['name' => 'AI Engine', 'health' => $serviceHealth['retell_ai'] ?? 98, 'color' => '#10B981'],
                        ['name' => 'Calendar API', 'health' => $serviceHealth['calcom'] ?? 95, 'color' => '#0EA5E9'],
                        ['name' => 'Database Cluster', 'health' => $serviceHealth['database'] ?? 100, 'color' => '#8B5CF6'],
                        ['name' => 'CDN Network', 'health' => 99, 'color' => '#F59E0B'],
                        ['name' => 'Load Balancers', 'health' => 100, 'color' => '#10B981'],
                        ['name' => 'Security WAF', 'health' => 100, 'color' => '#EF4444'],
                    ];
                @endphp
                
                @foreach($services as $service)
                    <div class="health-cell">
                        <div class="health-indicator">
                            <svg class="health-ring" viewBox="0 0 60 60">
                                <circle cx="30" cy="30" r="28" fill="none" stroke="{{ $service['color'] }}" 
                                        stroke-width="3" stroke-dasharray="{{ $service['health'] * 1.76 }} 176" 
                                        transform="rotate(-90 30 30)" opacity="0.3"/>
                                <circle cx="30" cy="30" r="28" fill="none" stroke="{{ $service['color'] }}" 
                                        stroke-width="3" stroke-dasharray="{{ $service['health'] * 1.76 }} 176" 
                                        transform="rotate(-90 30 30)"/>
                            </svg>
                            <div class="health-value" style="color: {{ $service['color'] }};">
                                {{ $service['health'] }}%
                            </div>
                        </div>
                        <div style="text-align: center; font-weight: 600;">{{ $service['name'] }}</div>
                    </div>
                @endforeach
            </div>
            
            <!-- Executive Summary -->
            <div class="executive-panel">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Executive Summary</h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div>
                        <h3 style="color: var(--quantum-blue); margin-bottom: 1rem;">Growth Metrics</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(150, 250) }}% YoY Revenue Growth</li>
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(80, 120) }}% Net Revenue Retention</li>
                            <li style="margin-bottom: 0.5rem;">✓ €{{ rand(50, 100) }}k Average Contract Value</li>
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(3, 6) }} Month Payback Period</li>
                        </ul>
                    </div>
                    <div>
                        <h3 style="color: var(--quantum-purple); margin-bottom: 1rem;">Market Position</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 0.5rem;">✓ #1 AI Phone System in DACH</li>
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(30, 50) }}% Market Share</li>
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(8, 12) }} Strategic Partnerships</li>
                            <li style="margin-bottom: 0.5rem;">✓ {{ rand(15, 25) }} Industry Awards</li>
                        </ul>
                    </div>
                    <div>
                        <h3 style="color: var(--quantum-green); margin-bottom: 1rem;">IPO Readiness</h3>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 0.5rem;">✓ SOC 2 Type II Certified</li>
                            <li style="margin-bottom: 0.5rem;">✓ GDPR & CCPA Compliant</li>
                            <li style="margin-bottom: 0.5rem;">✓ Big 4 Audited Financials</li>
                            <li style="margin-bottom: 0.5rem;">✓ €{{ rand(20, 40) }}M Series B Closed</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Global Presence -->
            <div class="global-presence">
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem; position: relative; z-index: 10;">Global Market Presence</h2>
                <div class="world-map"></div>
                
                <!-- Location dots -->
                <div class="location-dot" style="top: 30%; left: 48%; width: 20px; height: 20px;"></div> <!-- Berlin -->
                <div class="location-dot" style="top: 35%; left: 45%;"></div> <!-- Frankfurt -->
                <div class="location-dot" style="top: 32%; left: 46%;"></div> <!-- Munich -->
                <div class="location-dot" style="top: 28%; left: 44%;"></div> <!-- Hamburg -->
                <div class="location-dot" style="top: 25%; left: 52%;"></div> <!-- Vienna -->
                <div class="location-dot" style="top: 33%; left: 43%;"></div> <!-- Zurich -->
                
                <div style="position: absolute; bottom: 2rem; left: 2rem; right: 2rem; z-index: 10;">
                    <div class="grid grid-cols-3 gap-4">
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700;">{{ rand(6, 12) }}</div>
                            <div style="color: #64748b;">Countries</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700;">{{ rand(25, 50) }}</div>
                            <div style="color: #64748b;">Cities</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700;">{{ rand(3, 6) }}</div>
                            <div style="color: #64748b;">Data Centers</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compliance & Certifications -->
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Compliance & Certifications</h2>
            <div class="compliance-grid">
                @php
                    $compliances = [
                        ['name' => 'SOC 2', 'icon' => '🛡️', 'active' => true],
                        ['name' => 'GDPR', 'icon' => '🔒', 'active' => true],
                        ['name' => 'ISO 27001', 'icon' => '📋', 'active' => true],
                        ['name' => 'HIPAA', 'icon' => '🏥', 'active' => true],
                        ['name' => 'PCI DSS', 'icon' => '💳', 'active' => false],
                        ['name' => 'ISO 9001', 'icon' => '✅', 'active' => true],
                    ];
                @endphp
                
                @foreach($compliances as $compliance)
                    <div class="compliance-item {{ $compliance['active'] ? 'active' : '' }}">
                        <div class="compliance-icon">{{ $compliance['icon'] }}</div>
                        <div style="font-weight: 600;">{{ $compliance['name'] }}</div>
                        <div style="font-size: 0.75rem; color: {{ $compliance['active'] ? '#10B981' : '#64748b' }}; margin-top: 0.5rem;">
                            {{ $compliance['active'] ? 'Certified' : 'In Progress' }}
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Technology Stack -->
            <div class="chart-section" style="margin-bottom: 3rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Enterprise Technology Stack</h2>
                <div class="tech-stack">
                    <span class="tech-badge">Kubernetes</span>
                    <span class="tech-badge">AWS</span>
                    <span class="tech-badge">Terraform</span>
                    <span class="tech-badge">Laravel</span>
                    <span class="tech-badge">React</span>
                    <span class="tech-badge">PostgreSQL</span>
                    <span class="tech-badge">Redis</span>
                    <span class="tech-badge">ElasticSearch</span>
                    <span class="tech-badge">Prometheus</span>
                    <span class="tech-badge">Grafana</span>
                    <span class="tech-badge">Docker</span>
                    <span class="tech-badge">GitLab CI/CD</span>
                    <span class="tech-badge">CloudFlare</span>
                    <span class="tech-badge">Datadog</span>
                    <span class="tech-badge">PagerDuty</span>
                </div>
            </div>
            
            <!-- Real-time Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Live System Activity</h2>
                    <div class="activity-feed">
                        @php
                            $activities = [
                                ['icon' => '📞', 'title' => 'New AI Call', 'desc' => 'Dr. Schmidt Praxis - Appointment booked', 'time' => 'Just now'],
                                ['icon' => '🎯', 'title' => 'Conversion', 'desc' => 'Beauty Salon München - €89 booking', 'time' => '2 min ago'],
                                ['icon' => '🚀', 'title' => 'Deployment', 'desc' => 'v2.8.1 rolled out to EU-WEST', 'time' => '5 min ago'],
                                ['icon' => '✅', 'title' => 'Health Check', 'desc' => 'All systems operational', 'time' => '10 min ago'],
                                ['icon' => '📊', 'title' => 'Milestone', 'desc' => '1M conversations processed', 'time' => '15 min ago'],
                            ];
                        @endphp
                        
                        @foreach($activities as $index => $activity)
                            <div class="activity-item" style="animation-delay: {{ $index * 0.1 }}s;">
                                <div class="activity-icon">{{ $activity['icon'] }}</div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;">{{ $activity['title'] }}</div>
                                    <div style="font-size: 0.875rem; color: #64748b;">{{ $activity['desc'] }}</div>
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $activity['time'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Key Clients -->
                <div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Enterprise Clients</h2>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach(array_slice($companyMetrics, 0, 6) as $company)
                            <div class="kpi-card" style="padding: 1rem;">
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">{{ $company['name'] }}</div>
                                <div style="font-size: 0.875rem; color: #64748b;">
                                    {{ $company['branch_count'] }} locations • {{ $company['active_staff'] }} staff
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                    <span style="color: var(--quantum-green); font-size: 0.75rem;">
                                        {{ $company['calls_today'] }} calls today
                                    </span>
                                    <span style="color: var(--quantum-blue); font-size: 0.75rem;">
                                        {{ $company['health'] }}% health
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particleContainer');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (20 + Math.random() * 10) + 's';
                
                // Random colors
                const colors = ['#0EA5E9', '#8B5CF6', '#10B981', '#F59E0B'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                
                container.appendChild(particle);
            }
        }
        
        // Initialize Revenue Chart
        function initRevenueChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Generate growth data
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const currentData = months.map((_, i) => 400000 + (i * 50000) + (Math.random() * 100000));
            const projectedData = months.map((_, i) => i > 9 ? (currentData[9] + ((i - 9) * 150000)) : null);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Actual Revenue',
                        data: currentData,
                        borderColor: '#0EA5E9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Projected Revenue',
                        data: projectedData,
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 3,
                        borderDash: [5, 5],
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#94a3b8',
                                callback: function(value) {
                                    return '€' + (value / 1000000).toFixed(1) + 'M';
                                }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            }
                        }
                    }
                }
            });
        }
        
        // Real-time updates
        @if($refreshInterval > 0)
        setInterval(() => {
            @this.refresh();
            
            // Add subtle animation feedback
            document.querySelectorAll('.kpi-value').forEach(el => {
                el.style.transition = 'color 0.5s ease';
                el.style.color = '#0EA5E9';
                setTimeout(() => {
                    el.style.color = '';
                }, 30000);
            });
        }, {{ $refreshInterval * 1000 }});
        @endif
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initRevenueChart();
            
            // Smooth scroll
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
            
            // Add hover effects
            document.querySelectorAll('.kpi-card, .compliance-item, .tech-badge').forEach(elem => {
                elem.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                elem.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K for quick search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // Could open a command palette here
            }
            
            // ESC to close any modals
            if (e.key === 'Escape') {
                // Close modals
            }
        });
    </script>
    @endpush
</x-filament-panels::page>