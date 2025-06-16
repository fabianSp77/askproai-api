<x-filament-panels::page>
    @push('styles')
    <style>
        .cockpit-container {
            background: #1e293b;
            border-radius: 16px;
            padding: 32px;
            color: #f1f5f9;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 24px;
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
            background: linear-gradient(90deg, transparent, rgba(66, 153, 225, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .metric-card:hover::before {
            left: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: #3b82f6;
        }
        
        .metric-value {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .metric-label {
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .metric-trend {
            font-size: 0.75rem;
            color: #10b981;
            margin-top: 8px;
            display: flex;
            align-items: center;
        }
        
        .service-status {
            display: flex;
            align-items: center;
            padding: 16px;
            background: rgba(51, 65, 85, 0.5);
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        
        .service-status:hover {
            background: rgba(51, 65, 85, 0.8);
            border-color: #475569;
            transform: translateX(4px);
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 16px;
            position: relative;
        }
        
        .status-operational { 
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        }
        
        .status-warning { 
            background: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
            animation: pulse-warning 2s infinite;
        }
        
        .status-error { 
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
            animation: pulse-error 1s infinite;
        }
        
        @keyframes pulse-warning {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes pulse-error {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .section-card {
            background: rgba(51, 65, 85, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid rgba(148, 163, 184, 0.1);
            padding-bottom: 12px;
        }
        
        .section-title svg {
            margin-right: 12px;
            color: #3b82f6;
        }
        
        .chart-container {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        
        .sentiment-bar {
            display: flex;
            height: 24px;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .sentiment-positive { background: #10b981; }
        .sentiment-neutral { background: #6b7280; }
        .sentiment-negative { background: #ef4444; }
    </style>
    @endpush
    
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">AskProAI Performance Center</h1>
                <p class="text-gray-400 mt-2">Real-time system monitoring and analytics</p>
            </div>
            <button wire:click="refreshData" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all hover:shadow-lg">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Data
                </span>
            </button>
        </div>
        
        <!-- Call Metrics Section -->
        <div class="section-card">
            <h2 class="section-title">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Call Analytics
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($callMetrics['calls_today'] ?? 0) }}</div>
                    <div class="metric-label">Calls Today</div>
                    <div class="metric-trend">
                        <span class="text-gray-400 text-xs">24h: {{ number_format($callMetrics['calls_24h'] ?? 0) }}</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ $callMetrics['avg_duration_today'] ?? 0 }}s</div>
                    <div class="metric-label">Avg Call Duration</div>
                    <div class="metric-trend">
                        <span class="text-gray-400 text-xs">Total: {{ number_format($callMetrics['total_duration_today'] ?? 0) }} min</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ $callMetrics['conversion_rate'] ?? 0 }}%</div>
                    <div class="metric-label">Conversion Rate</div>
                    <div class="metric-trend">
                        <span class="text-gray-400 text-xs">Calls → Appointments</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($callMetrics['calls_7days'] ?? 0) }}</div>
                    <div class="metric-label">7-Day Total</div>
                    <div class="metric-trend">
                        <span class="text-gray-400 text-xs">Last week calls</span>
                    </div>
                </div>
            </div>
            
            @if(isset($callMetrics['sentiment_distribution']))
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-300 mb-2">Call Sentiment Analysis</h3>
                <div class="sentiment-bar">
                    @php
                        $total = array_sum($callMetrics['sentiment_distribution']);
                        $positive = $total > 0 ? ($callMetrics['sentiment_distribution']['positive'] / $total * 100) : 0;
                        $neutral = $total > 0 ? ($callMetrics['sentiment_distribution']['neutral'] / $total * 100) : 0;
                        $negative = $total > 0 ? ($callMetrics['sentiment_distribution']['negative'] / $total * 100) : 0;
                    @endphp
                    <div class="sentiment-positive" style="width: {{ $positive }}%"></div>
                    <div class="sentiment-neutral" style="width: {{ $neutral }}%"></div>
                    <div class="sentiment-negative" style="width: {{ $negative }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-400">
                    <span>Positive: {{ $callMetrics['sentiment_distribution']['positive'] ?? 0 }}</span>
                    <span>Neutral: {{ $callMetrics['sentiment_distribution']['neutral'] ?? 0 }}</span>
                    <span>Negative: {{ $callMetrics['sentiment_distribution']['negative'] ?? 0 }}</span>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Appointment Metrics Section -->
        <div class="section-card">
            <h2 class="section-title">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Appointment Management
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($appointmentMetrics['appointments_today'] ?? 0) }}</div>
                    <div class="metric-label">Today's Appointments</div>
                    <div class="metric-trend">
                        <span class="text-gray-400 text-xs">Next 2h: {{ $appointmentMetrics['upcoming_next_2h'] ?? 0 }}</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($appointmentMetrics['completed_today'] ?? 0) }}</div>
                    <div class="metric-label">Completed</div>
                    <div class="metric-trend">
                        <span class="text-green-400 text-xs">✓ Successfully finished</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($appointmentMetrics['no_shows_today'] ?? 0) }}</div>
                    <div class="metric-label">No Shows</div>
                    <div class="metric-trend">
                        <span class="text-orange-400 text-xs">Customer didn't appear</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-value">{{ number_format($appointmentMetrics['cancelled_today'] ?? 0) }}</div>
                    <div class="metric-label">Cancelled</div>
                    <div class="metric-trend">
                        <span class="text-red-400 text-xs">Cancelled today</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Status Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Integration Health -->
            <div class="section-card">
                <h2 class="section-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Integration Status
                </h2>
                
                @foreach($integrationStatus as $serviceName => $service)
                    <div class="service-status">
                        <div class="status-indicator status-{{ $service['status'] }}"></div>
                        <div class="flex-1">
                            <div class="font-semibold text-white">{{ ucfirst($serviceName) }}</div>
                            <div class="text-sm text-gray-400">{{ $service['message'] }}</div>
                            @if(isset($service['details']))
                                <div class="text-xs text-gray-500 mt-1">
                                    @foreach($service['details'] as $key => $value)
                                        <span class="mr-3">{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">{{ $service['last_activity'] }}</div>
                    </div>
                @endforeach
            </div>
            
            <!-- System Health Metrics -->
            <div class="section-card">
                <h2 class="section-title">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    System Health
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-800 rounded">
                        <span class="text-gray-300">Queue Jobs</span>
                        <span class="font-mono text-white {{ $systemHealth['queue_size'] > 100 ? 'text-yellow-400' : '' }}">
                            {{ number_format($systemHealth['queue_size'] ?? 0) }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-800 rounded">
                        <span class="text-gray-300">Failed Jobs (24h)</span>
                        <span class="font-mono text-white {{ $systemHealth['failed_jobs'] > 0 ? 'text-red-400' : '' }}">
                            {{ number_format($systemHealth['failed_jobs'] ?? 0) }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-800 rounded">
                        <span class="text-gray-300">Horizon Status</span>
                        <span class="font-mono text-white">{{ ucfirst($systemHealth['horizon_status'] ?? 'unknown') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-800 rounded">
                        <span class="text-gray-300">Active Companies</span>
                        <span class="font-mono text-white">{{ number_format($systemHealth['active_companies'] ?? 0) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-800 rounded">
                        <span class="text-gray-300">Trial Companies</span>
                        <span class="font-mono text-white">{{ number_format($systemHealth['trial_companies'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Metrics -->
        <div class="section-card">
            <h2 class="section-title">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Performance Metrics
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-800 rounded-lg p-4">
                    <div class="text-gray-400 text-sm mb-2">CPU Load Average</div>
                    <div class="text-2xl font-bold text-white">{{ number_format($performanceMetrics['cpu_usage'] ?? 0, 2) }}</div>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-4">
                    <div class="text-gray-400 text-sm mb-2">Memory Usage</div>
                    <div class="text-2xl font-bold text-white">{{ $performanceMetrics['memory_usage'] ?? 0 }} MB</div>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-4">
                    <div class="text-gray-400 text-sm mb-2">Disk Usage</div>
                    <div class="text-2xl font-bold text-white">{{ $performanceMetrics['disk_usage']['percentage'] ?? 0 }}%</div>
                    <div class="text-xs text-gray-500">
                        {{ $performanceMetrics['disk_usage']['used_gb'] ?? 0 }}GB / {{ $performanceMetrics['disk_usage']['total_gb'] ?? 0 }}GB
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Feed -->
        <div class="section-card">
            <h2 class="section-title">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Recent Activity Feed
            </h2>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($realtimeActivities as $activity)
                    <div class="flex items-start p-3 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                            <x-dynamic-component :component="$activity['icon']" class="w-5 h-5 text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-white">{{ $activity['title'] }}</div>
                            <div class="text-sm text-gray-400">{{ $activity['subtitle'] }}</div>
                            @if(isset($activity['status']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                                    {{ $activity['status'] == 'converted' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $activity['status'])) }}
                                </span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 ml-3">{{ $activity['time'] }}</div>
                    </div>
                @empty
                    <div class="text-gray-500 text-center py-8">No recent activity to display</div>
                @endforelse
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Auto-refresh every 60 seconds
        setInterval(() => {
            @this.refreshData();
        }, 60000);
    </script>
    @endpush
</x-filament-panels::page>