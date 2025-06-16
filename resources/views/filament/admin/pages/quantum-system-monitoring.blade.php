<x-filament-panels::page>
    @push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        :root {
            --quantum-primary: #3b82f6;
            --quantum-secondary: #8b5cf6;
            --quantum-success: #10b981;
            --quantum-warning: #f59e0b;
            --quantum-danger: #ef4444;
            --quantum-dark: #0f172a;
            --quantum-darker: #020617;
            --quantum-light: #f8fafc;
            --quantum-border: rgba(148, 163, 184, 0.1);
            --quantum-glow: rgba(59, 130, 246, 0.5);
        }

        * {
            box-sizing: border-box;
        }

        .quantum-container {
            font-family: 'Inter', sans-serif;
            background: var(--quantum-darker);
            min-height: 100vh;
            color: var(--quantum-light);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .quantum-bg {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            animation: quantumShift 30s ease-in-out infinite;
        }

        @keyframes quantumShift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-20px, -20px) scale(1.1); }
            66% { transform: translate(20px, -10px) scale(0.95); }
        }

        /* Grid Background */
        .quantum-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 60s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Main Content */
        .quantum-content {
            position: relative;
            z-index: 10;
            padding: 2rem;
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Header */
        .quantum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .quantum-title {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--quantum-primary) 0%, var(--quantum-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.05em;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantum-subtitle {
            color: #94a3b8;
            font-size: 1.125rem;
            margin-top: 0.5rem;
        }

        .quantum-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .quantum-refresh {
            background: linear-gradient(135deg, var(--quantum-primary) 0%, var(--quantum-secondary) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .quantum-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        /* Health Score */
        .quantum-health {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--quantum-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }

        .health-score {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .health-excellent { color: var(--quantum-success); }
        .health-good { color: #22c55e; }
        .health-fair { color: var(--quantum-warning); }
        .health-poor { color: var(--quantum-danger); }

        /* Metric Cards */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--quantum-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: left 0.5s;
        }

        .metric-card:hover::before {
            left: 100%;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            border-color: var(--quantum-primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .metric-title {
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--quantum-primary);
        }

        .metric-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: white;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .metric-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .metric-change.positive { color: var(--quantum-success); }
        .metric-change.negative { color: var(--quantum-danger); }

        .metric-subtext {
            color: #64748b;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        /* Large Cards */
        .card-large {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--quantum-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Charts */
        .chart-container {
            background: rgba(2, 6, 23, 0.4);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            min-height: 300px;
            position: relative;
        }

        /* Activity Feed */
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .activity-feed::-webkit-scrollbar {
            width: 6px;
        }

        .activity-feed::-webkit-scrollbar-track {
            background: rgba(148, 163, 184, 0.1);
            border-radius: 3px;
        }

        .activity-feed::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 3px;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .activity-item:hover {
            background: rgba(30, 41, 59, 0.6);
            border-color: var(--quantum-border);
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .activity-desc {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #64748b;
            white-space: nowrap;
        }

        /* Status Indicators */
        .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-operational { background: var(--quantum-success); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2); }
        .status-warning { background: var(--quantum-warning); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2); }
        .status-error { background: var(--quantum-danger); box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2); }

        /* Anomaly Cards */
        .anomaly-card {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .anomaly-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .anomaly-type {
            font-weight: 600;
            color: var(--quantum-danger);
        }

        .anomaly-severity {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .severity-high { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .severity-medium { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .severity-low { background: rgba(59, 130, 246, 0.2); color: #93bbfc; }

        /* Progress Bars */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--quantum-primary) 0%, var(--quantum-secondary) 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Tables */
        .quantum-table {
            width: 100%;
            border-collapse: collapse;
        }

        .quantum-table th {
            text-align: left;
            padding: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--quantum-border);
        }

        .quantum-table td {
            padding: 0.75rem;
            color: #e2e8f0;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.05);
        }

        .quantum-table tr:hover td {
            background: rgba(59, 130, 246, 0.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .quantum-content {
                padding: 1rem;
            }

            .quantum-title {
                font-size: 1.875rem;
            }

            .metric-grid {
                grid-template-columns: 1fr;
            }

            .quantum-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Loading State */
        .quantum-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 3px solid rgba(59, 130, 246, 0.2);
            border-top-color: var(--quantum-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush

    <div class="quantum-container">
        <div class="quantum-bg"></div>
        <div class="quantum-grid"></div>
        
        <div class="quantum-content">
            <!-- Header -->
            <div class="quantum-header">
                <div>
                    <h1 class="quantum-title">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        Quantum System Monitoring
                    </h1>
                    <p class="quantum-subtitle">Real-time intelligence & predictive analytics</p>
                </div>
                <div class="quantum-actions">
                    <div class="quantum-health">
                        <div class="health-score health-{{ $systemHealth['status'] ?? 'good' }}">
                            {{ $systemHealth['overall'] ?? 0 }}%
                        </div>
                        <div class="text-sm text-gray-400">System Health</div>
                    </div>
                    <button wire:click="refreshData" class="quantum-refresh">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Real-time Metrics -->
            <div class="metric-grid">
                <!-- Calls Today -->
                <div class="metric-card fade-in">
                    <div class="metric-header">
                        <div>
                            <div class="metric-title">Calls Today</div>
                            <div class="metric-value">{{ number_format($realtimeMetrics['calls']['today'] ?? 0) }}</div>
                            <div class="metric-change positive">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                +{{ number_format($realtimeMetrics['calls']['last_24h'] ?? 0) }} last 24h
                            </div>
                            <div class="metric-subtext">Active now: {{ $realtimeMetrics['calls']['active_now'] ?? 0 }}</div>
                        </div>
                        <div class="metric-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Appointments Today -->
                <div class="metric-card fade-in" style="animation-delay: 0.1s">
                    <div class="metric-header">
                        <div>
                            <div class="metric-title">Appointments</div>
                            <div class="metric-value">{{ number_format($realtimeMetrics['appointments']['today'] ?? 0) }}</div>
                            <div class="metric-change positive">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ number_format($realtimeMetrics['appointments']['completed_today'] ?? 0) }} completed
                            </div>
                            <div class="metric-subtext">Upcoming 24h: {{ $realtimeMetrics['appointments']['upcoming_24h'] ?? 0 }}</div>
                        </div>
                        <div class="metric-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="metric-card fade-in" style="animation-delay: 0.2s">
                    <div class="metric-header">
                        <div>
                            <div class="metric-title">Conversion Rate</div>
                            <div class="metric-value">{{ $realtimeMetrics['conversions']['rate_24h'] ?? 0 }}%</div>
                            <div class="metric-change positive">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                {{ $realtimeMetrics['conversions']['appointments_from_calls'] ?? 0 }} converted
                            </div>
                            <div class="metric-subtext">Avg lead time: {{ $realtimeMetrics['conversions']['booking_lead_time'] ?? 0 }}h</div>
                        </div>
                        <div class="metric-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Revenue Today -->
                <div class="metric-card fade-in" style="animation-delay: 0.3s">
                    <div class="metric-header">
                        <div>
                            <div class="metric-title">Revenue Today</div>
                            <div class="metric-value">€{{ number_format($businessIntelligence['revenue']['today'] ?? 0, 0) }}</div>
                            <div class="metric-change {{ ($businessIntelligence['revenue']['growth_rate'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $businessIntelligence['revenue']['growth_rate'] ?? 0 }}% growth
                            </div>
                            <div class="metric-subtext">30d: €{{ number_format($businessIntelligence['revenue']['last_30d'] ?? 0, 0) }}</div>
                        </div>
                        <div class="metric-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Intelligence Section -->
            <div class="card-large fade-in">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Business Intelligence
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Performers -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Top Performers</h3>
                        <div class="space-y-3">
                            @foreach($businessIntelligence['top_performers'] ?? [] as $performer)
                                <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                                    <div>
                                        <div class="font-medium text-white">{{ $performer['name'] }}</div>
                                        <div class="text-sm text-gray-400">{{ $performer['appointments'] }} appointments</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-green-400">€{{ number_format($performer['revenue'] / 100, 0) }}</div>
                                        <div class="text-xs text-gray-500">Revenue</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Popular Services -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Popular Services</h3>
                        <div class="space-y-3">
                            @foreach(array_slice($businessIntelligence['popular_services'] ?? [], 0, 5) as $service)
                                <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                                    <div>
                                        <div class="font-medium text-white">{{ $service->name }}</div>
                                        <div class="text-sm text-gray-400">{{ $service->count }} bookings</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-blue-400">€{{ number_format($service->price / 100, 0) }}</div>
                                        <div class="text-xs text-gray-500">Per session</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="chart-container mt-6">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Performance & Security Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Performance Metrics -->
                <div class="card-large fade-in">
                    <h2 class="card-title">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Performance Metrics
                    </h2>

                    <div class="space-y-4">
                        <!-- Response Times -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-400">API Response Time</span>
                                <span class="text-sm font-semibold text-white">{{ $performanceMetrics['response_times']['p50'] ?? 0 }}ms</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="bg-gray-800/50 rounded p-2 text-center">
                                    <div class="text-gray-500">P50</div>
                                    <div class="font-semibold text-green-400">{{ $performanceMetrics['response_times']['p50'] ?? 0 }}ms</div>
                                </div>
                                <div class="bg-gray-800/50 rounded p-2 text-center">
                                    <div class="text-gray-500">P95</div>
                                    <div class="font-semibold text-yellow-400">{{ $performanceMetrics['response_times']['p95'] ?? 0 }}ms</div>
                                </div>
                                <div class="bg-gray-800/50 rounded p-2 text-center">
                                    <div class="text-gray-500">P99</div>
                                    <div class="font-semibold text-red-400">{{ $performanceMetrics['response_times']['p99'] ?? 0 }}ms</div>
                                </div>
                            </div>
                        </div>

                        <!-- System Resources -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-400">CPU Load</span>
                                <span class="text-sm font-semibold text-white">{{ $performanceMetrics['system']['cpu_load']['1min'] ?? 0 }}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ min(100, ($performanceMetrics['system']['cpu_load']['1min'] ?? 0) * 25) }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-400">Memory Usage</span>
                                <span class="text-sm font-semibold text-white">{{ $performanceMetrics['system']['memory']['used_mb'] ?? 0 }}MB</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $performanceMetrics['system']['memory']['percentage'] ?? 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-400">Disk Usage</span>
                                <span class="text-sm font-semibold text-white">{{ $performanceMetrics['system']['disk']['percentage'] ?? 0 }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $performanceMetrics['system']['disk']['percentage'] ?? 0 }}%"></div>
                            </div>
                        </div>

                        <!-- Cache Performance -->
                        <div class="bg-gray-800/50 rounded-lg p-4 mt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs text-gray-500">Cache Hit Rate</div>
                                    <div class="text-xl font-bold text-green-400">{{ $performanceMetrics['cache']['hit_rate'] ?? 0 }}%</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Queue Depth</div>
                                    <div class="text-xl font-bold text-blue-400">{{ number_format($performanceMetrics['queue']['jobs_pending'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Metrics -->
                <div class="card-large fade-in">
                    <h2 class="card-title">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Security Status
                    </h2>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-800/50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white mb-1">{{ $securityMetrics['events_24h'] ?? 0 }}</div>
                            <div class="text-xs text-gray-400">Security Events (24h)</div>
                        </div>
                        <div class="bg-gray-800/50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-white mb-1">{{ $securityMetrics['failed_logins_24h'] ?? 0 }}</div>
                            <div class="text-xs text-gray-400">Failed Logins (24h)</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="status-dot status-{{ $securityMetrics['backup']['status'] === 'healthy' ? 'operational' : ($securityMetrics['backup']['status'] === 'warning' ? 'warning' : 'error') }}"></span>
                                <span class="text-sm font-medium">Backup Status</span>
                            </div>
                            <span class="text-sm text-gray-400">{{ $securityMetrics['backup']['last_backup_hours_ago'] ?? 999 }}h ago</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="status-dot status-operational"></span>
                                <span class="text-sm font-medium">Encryption</span>
                            </div>
                            <span class="text-sm text-gray-400">{{ $securityMetrics['compliance']['encryption_coverage'] ?? 0 }}% coverage</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="status-dot status-{{ ($securityMetrics['rate_limit_violations_24h'] ?? 0) > 100 ? 'warning' : 'operational' }}"></span>
                                <span class="text-sm font-medium">Rate Limiting</span>
                            </div>
                            <span class="text-sm text-gray-400">{{ $securityMetrics['rate_limit_violations_24h'] ?? 0 }} violations</span>
                        </div>
                    </div>

                    <!-- Threat Overview -->
                    <div class="bg-red-900/20 border border-red-900/50 rounded-lg p-4 mt-4">
                        <h4 class="font-semibold text-red-400 mb-2">Threat Detection</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">SQL Injection:</span>
                                <span class="font-semibold text-white ml-2">{{ $securityMetrics['threats']['sql_injection_attempts'] ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">XSS Attempts:</span>
                                <span class="font-semibold text-white ml-2">{{ $securityMetrics['threats']['xss_attempts'] ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Suspicious IPs:</span>
                                <span class="font-semibold text-white ml-2">{{ $securityMetrics['threats']['suspicious_ips'] ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Blocked IPs:</span>
                                <span class="font-semibold text-white ml-2">{{ $securityMetrics['threats']['blocked_ips'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Predictive Analytics & Anomalies -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Predictive Analytics -->
                <div class="lg:col-span-2 card-large fade-in">
                    <h2 class="card-title">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        Predictive Analytics
                    </h2>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-800/50 rounded-lg p-4">
                            <div class="text-xs text-gray-400 mb-1">Calls Forecast (7d)</div>
                            <div class="text-2xl font-bold text-blue-400">{{ number_format($predictiveAnalytics['forecasts']['calls_next_7d'] ?? 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span class="text-{{ $predictiveAnalytics['trends']['call_trend'] === 'increasing' ? 'green' : ($predictiveAnalytics['trends']['call_trend'] === 'decreasing' ? 'red' : 'gray') }}-400">
                                    {{ ucfirst($predictiveAnalytics['trends']['call_trend'] ?? 'stable') }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-gray-800/50 rounded-lg p-4">
                            <div class="text-xs text-gray-400 mb-1">Appointments (7d)</div>
                            <div class="text-2xl font-bold text-purple-400">{{ number_format($predictiveAnalytics['forecasts']['appointments_next_7d'] ?? 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span class="text-{{ $predictiveAnalytics['trends']['appointment_trend'] === 'increasing' ? 'green' : ($predictiveAnalytics['trends']['appointment_trend'] === 'decreasing' ? 'red' : 'gray') }}-400">
                                    {{ ucfirst($predictiveAnalytics['trends']['appointment_trend'] ?? 'stable') }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-gray-800/50 rounded-lg p-4">
                            <div class="text-xs text-gray-400 mb-1">Revenue (30d)</div>
                            <div class="text-2xl font-bold text-green-400">€{{ number_format($predictiveAnalytics['forecasts']['revenue_next_30d'] ?? 0, 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span class="text-{{ $predictiveAnalytics['trends']['revenue_trend'] === 'increasing' ? 'green' : ($predictiveAnalytics['trends']['revenue_trend'] === 'decreasing' ? 'red' : 'gray') }}-400">
                                    {{ ucfirst($predictiveAnalytics['trends']['revenue_trend'] ?? 'stable') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Insights -->
                    <div class="bg-gray-800/50 rounded-lg p-4">
                        <h4 class="font-semibold text-white mb-3">AI Insights</h4>
                        <div class="space-y-2">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-white">Peak Hours Identified</div>
                                    <div class="text-xs text-gray-400">
                                        Highest activity at {{ implode(':00, ', $predictiveAnalytics['insights']['peak_hours'] ?? []) }}:00
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-purple-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-white">Capacity Recommendation</div>
                                    <div class="text-xs text-gray-400">{{ $predictiveAnalytics['insights']['capacity_recommendation'] ?? 'Analyzing...' }}</div>
                                </div>
                            </div>

                            @if(count($predictiveAnalytics['insights']['churn_risk_companies'] ?? []) > 0)
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-white">Churn Risk Alert</div>
                                    <div class="text-xs text-gray-400">
                                        {{ count($predictiveAnalytics['insights']['churn_risk_companies'] ?? []) }} companies at risk
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Anomalies -->
                <div class="card-large fade-in">
                    <h2 class="card-title">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Anomaly Detection
                    </h2>

                    @if(count($anomalies) > 0)
                        <div class="space-y-3">
                            @foreach(array_slice($anomalies, 0, 5) as $anomaly)
                                <div class="anomaly-card">
                                    <div class="anomaly-header">
                                        <div class="anomaly-type">{{ $anomaly['type'] }}</div>
                                        <span class="anomaly-severity severity-{{ strtolower($anomaly['severity']) }}">
                                            {{ ucfirst($anomaly['severity']) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-300 mt-1">{{ $anomaly['message'] }}</div>
                                    @if(isset($anomaly['deviation']))
                                        <div class="text-xs text-gray-400 mt-1">
                                            Deviation: {{ $anomaly['deviation'] }}%
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-gray-400">No anomalies detected</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Live Activity Feed -->
            <div class="card-large fade-in">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Live Activity Feed
                </h2>

                <div class="activity-feed">
                    @foreach($liveActivities as $activity)
                        <div class="activity-item">
                            <div class="activity-icon">
                                @if($activity['type'] === 'call')
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                @elseif($activity['type'] === 'appointment')
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $activity['title'] }}</div>
                                <div class="activity-desc">{{ $activity['description'] }}</div>
                                @if(isset($activity['status']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                                        {{ $activity['status'] === 'converted' ? 'bg-green-900/50 text-green-300' : 
                                           ($activity['status'] === 'completed' ? 'bg-blue-900/50 text-blue-300' : 
                                           'bg-gray-900/50 text-gray-300') }}">
                                        {{ ucfirst($activity['status']) }}
                                    </span>
                                @endif
                            </div>
                            <div class="activity-time">
                                {{ \Carbon\Carbon::parse($activity['time'])->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Health Recommendations -->
            @if(count($systemHealth['recommendations'] ?? []) > 0)
            <div class="card-large fade-in">
                <h2 class="card-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    System Recommendations
                </h2>
                <div class="space-y-3">
                    @foreach($systemHealth['recommendations'] as $recommendation)
                        <div class="flex items-start gap-3 p-3 bg-yellow-900/20 border border-yellow-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-yellow-100">{{ $recommendation }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['7d ago', '6d ago', '5d ago', '4d ago', '3d ago', '2d ago', 'Yesterday', 'Today'],
                datasets: [{
                    label: 'Revenue',
                    data: [1200, 1400, 1100, 1600, 1800, 1500, 2000, {{ $businessIntelligence['revenue']['today'] ?? 0 }}],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return '€' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });

        // Auto-refresh
        setInterval(() => {
            @this.refreshData();
        }, 60000);
    </script>
    @endpush
</x-filament-panels::page>