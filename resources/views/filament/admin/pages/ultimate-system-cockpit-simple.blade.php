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
    </style>
    @endpush
    
    <div class="space-y-6" wire:poll.5s="loadMetrics">
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
            
            <!-- Real-time Stats -->
            <div class="grid grid-cols-4 gap-4 mt-8">
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green">{{ $realtimeStats['calls_per_minute'] }}</div>
                    <div class="text-xs opacity-70">Calls/Min</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green">{{ $realtimeStats['appointments_per_hour'] }}</div>
                    <div class="text-xs opacity-70">Appointments/Hour</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold neon-green">{{ $realtimeStats['new_customers_today'] }}</div>
                    <div class="text-xs opacity-70">New Customers</div>
                </div>
                <div class="text-center">
                    <div class="text-sm font-bold">{{ $realtimeStats['peak_hour'] }}</div>
                    <div class="text-xs opacity-70">Peak Hour</div>
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
                @foreach($serviceHealth as $service => $health)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="status-dot {{ $health >= 90 ? 'status-healthy' : ($health >= 70 ? 'status-warning' : 'status-critical') }}"></span>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $service)) }}</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-48">
                                <div class="health-bar">
                                    <div class="health-bar-fill" 
                                         style="width: {{ $health }}%; background: {{ $health >= 90 ? '#00ff41' : ($health >= 70 ? '#ffff00' : '#ff0040') }}">
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm font-mono">{{ $health }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- System Metrics Grid -->
        <div class="metric-grid">
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-clock class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Response Time</span>
                </div>
                <div class="text-3xl font-bold">{{ $systemMetrics['response_time'] }}ms</div>
                <div class="text-xs opacity-50 mt-1">Average</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Error Rate</span>
                </div>
                <div class="text-3xl font-bold">{{ number_format($systemMetrics['error_rate'] * 100, 2) }}%</div>
                <div class="text-xs opacity-50 mt-1">Last Hour</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-queue-list class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Queue Size</span>
                </div>
                <div class="text-3xl font-bold">{{ number_format($systemMetrics['queue_size']) }}</div>
                <div class="text-xs opacity-50 mt-1">Pending Jobs</div>
            </div>
            
            <div class="glass-card rounded-xl p-6 metric-card">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-arrow-trending-up class="w-5 h-5 opacity-50" />
                    <span class="text-xs opacity-70">Uptime</span>
                </div>
                <div class="text-2xl font-bold">{{ $systemMetrics['uptime'] }}</div>
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
                @foreach($companyMetrics as $company)
                    <div class="glass-card rounded-lg p-4 company-cell
                         @if($company['health'] >= 85) border-green-500
                         @elseif($company['health'] >= 60) border-yellow-500
                         @else border-red-500
                         @endif"
                         style="border-width: 2px;">
                        <h3 class="font-semibold text-sm mb-2 truncate w-full text-center">{{ $company['name'] }}</h3>
                        <div class="text-3xl font-bold mb-2
                             @if($company['health'] >= 85) neon-green
                             @elseif($company['health'] >= 60) neon-yellow
                             @else neon-red
                             @endif">
                            {{ $company['health'] }}%
                        </div>
                        <div class="text-xs space-y-1 w-full">
                            <div class="flex justify-between">
                                <span class="opacity-70">Calls:</span>
                                <span class="font-mono">{{ $company['calls_today'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="opacity-70">Appts:</span>
                                <span class="font-mono">{{ $company['appointments_today'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Manual Refresh Button -->
        <div class="fixed bottom-6 right-6">
            <button wire:click="refresh"
                    wire:loading.class="animate-spin"
                    class="glass-card rounded-full p-4 hover:scale-110 transition-transform">
                <x-heroicon-o-arrow-path class="w-6 h-6" />
            </button>
        </div>
    </div>
</x-filament-panels::page>