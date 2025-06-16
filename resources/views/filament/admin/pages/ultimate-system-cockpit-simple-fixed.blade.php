<x-filament-panels::page>
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
        
        /* Company Grid */
        .company-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .company-cell {
            padding: 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .company-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
    
    <div class="space-y-6">
        <!-- Header with Overall Health -->
        <div class="glass-card rounded-xl p-8 text-center">
            <h1 class="text-4xl font-bold mb-4">AskProAI System Health</h1>
            <div class="relative inline-block">
                <div class="text-8xl font-bold pulse-animation
                     @if($systemMetrics['overall_health'] >= 90) neon-green
                     @elseif($systemMetrics['overall_health'] >= 70) neon-yellow
                     @else neon-red
                     @endif">
                    {{ $systemMetrics['overall_health'] }}%
                </div>
                <div class="text-sm opacity-70 mt-2">Overall System Health</div>
            </div>
        </div>
        
        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="glass-card rounded-lg p-4 text-center">
                <div class="text-3xl font-bold">{{ $systemMetrics['active_calls'] }}</div>
                <div class="text-sm opacity-70">Active Calls</div>
            </div>
            
            <div class="glass-card rounded-lg p-4 text-center">
                <div class="text-3xl font-bold">{{ $systemMetrics['queue_size'] }}</div>
                <div class="text-sm opacity-70">Queue Size</div>
            </div>
            
            <div class="glass-card rounded-lg p-4 text-center">
                <div class="text-3xl font-bold">{{ number_format($systemMetrics['error_rate'] * 100, 1) }}%</div>
                <div class="text-sm opacity-70">Error Rate</div>
            </div>
            
            <div class="glass-card rounded-lg p-4 text-center">
                <div class="text-3xl font-bold">{{ $systemMetrics['response_time'] }}ms</div>
                <div class="text-sm opacity-70">Response Time</div>
            </div>
        </div>
        
        <!-- Service Health Status -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-4">Service Health</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($serviceHealth as $service => $health)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-800/50">
                        <span class="capitalize">{{ str_replace('_', ' ', $service) }}</span>
                        <div class="flex items-center">
                            <div class="health-bar w-24 mr-2">
                                <div class="health-bar-fill
                                     @if($health >= 90) bg-green-500
                                     @elseif($health >= 70) bg-yellow-500
                                     @else bg-red-500
                                     @endif"
                                     style="width: {{ $health }}%">
                                </div>
                            </div>
                            <span class="text-sm font-bold">{{ $health }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Company Overview -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-4">Company Health Monitor</h2>
            <div class="company-grid">
                @foreach($companyMetrics as $company)
                    <div class="company-cell glass-card">
                        <h3 class="font-semibold text-lg mb-2">{{ $company['name'] }}</h3>
                        <div class="text-3xl font-bold mb-2
                             @if($company['health'] >= 90) text-green-500
                             @elseif($company['health'] >= 70) text-yellow-500
                             @else text-red-500
                             @endif">
                            {{ $company['health'] }}%
                        </div>
                        <div class="text-xs space-y-1">
                            <div>Calls Today: {{ $company['calls_today'] }}</div>
                            <div>Appointments: {{ $company['appointments_today'] }}</div>
                            <div>Staff: {{ $company['active_staff'] }}</div>
                            <div>Branches: {{ $company['branch_count'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- System Information -->
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-2xl font-bold mb-4">System Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm opacity-70">Uptime:</span>
                    <span class="font-semibold">{{ $systemMetrics['uptime'] }}</span>
                </div>
                <div>
                    <span class="text-sm opacity-70">Database Health:</span>
                    <span class="font-semibold">{{ $systemMetrics['database_health'] }}%</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>