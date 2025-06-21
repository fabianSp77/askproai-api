<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Service Filter
                    </label>
                    <select wire:model.live="selectedService" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">All Services</option>
                        @foreach($serviceHealth as $service => $health)
                            <option value="{{ $service }}">{{ ucfirst($service) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Time Range
                    </label>
                    <select wire:model.live="selectedTimeRange" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="5m">Last 5 minutes</option>
                        <option value="15m">Last 15 minutes</option>
                        <option value="30m">Last 30 minutes</option>
                        <option value="1h">Last hour</option>
                        <option value="6h">Last 6 hours</option>
                        <option value="24h">Last 24 hours</option>
                        <option value="7d">Last 7 days</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Active Alerts --}}
        @if(count($alerts) > 0)
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-sm border border-red-200 dark:border-red-700 p-4">
                <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Active Alerts ({{ count($alerts) }})
                </h3>
                <div class="space-y-2">
                    @foreach($alerts as $alert)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $alert['rule'] }} - {{ $alert['service'] }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $alert['message'] }}
                                    @if($alert['value'] && $alert['threshold'])
                                        ({{ round($alert['value'], 2) }} > {{ $alert['threshold'] }})
                                    @endif
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                {{ $alert['severity'] === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                {{ ucfirst($alert['severity']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- System Metrics Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Total Requests --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Requests</h4>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($metrics['total_requests'] ?? 0) }}
                </p>
            </div>

            {{-- Success Rate --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Success Rate</h4>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold {{ ($metrics['success_rate'] ?? 0) >= 95 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $metrics['success_rate'] ?? 0 }}%
                </p>
            </div>

            {{-- Avg Response Time --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Response</h4>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold {{ ($metrics['avg_response_time'] ?? 0) <= 500 ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $metrics['avg_response_time'] ?? 0 }}ms
                </p>
            </div>

            {{-- Cache Hit Rate --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Cache Hit Rate</h4>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold {{ ($metrics['cache_hit_rate'] ?? 0) >= 70 ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $metrics['cache_hit_rate'] ?? 0 }}%
                </p>
            </div>

            {{-- Active Circuits --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Open Circuits</h4>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <p class="text-2xl font-bold {{ count($metrics['active_circuits'] ?? []) === 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ count($metrics['active_circuits'] ?? []) }}
                </p>
            </div>
        </div>

        {{-- Service Health Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Service Health Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($serviceHealth as $service => $health)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($service) }}</h4>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($health['status'] === 'healthy') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($health['status'] === 'degraded') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($health['status']) }}
                            </span>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Uptime:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $health['uptime'] }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Error Rate:</span>
                                <span class="font-medium {{ $health['error_rate'] > 5 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $health['error_rate'] }}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Avg Response:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $health['avg_response_time'] }}ms</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Circuit:</span>
                                <span class="font-medium {{ $health['circuit_breaker'] === 'open' ? 'text-red-600' : 'text-green-600' }}">
                                    {{ ucfirst($health['circuit_breaker']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Metrics Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Operations</h3>
            </div>
            <div class="overflow-hidden">
                {{ $this->table }}
            </div>
        </div>
    </div>

    @if(config('mcp-monitoring.dashboard.enable_auto_refresh', true))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setInterval(function() {
                    Livewire.emit('refresh-metrics');
                }, {{ config('mcp-monitoring.dashboard.refresh_interval', 10) * 1000 }});
            });
        </script>
    @endif
</x-filament-panels::page>