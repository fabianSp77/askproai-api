<x-filament-panels::page>
    {{-- Removed aggressive wire:poll - only poll on demand --}}
    <div class="space-y-6" 
         x-data="{ 
            autoRefresh: false,
            refreshInterval: 30,
            refreshTimer: null,
            isRefreshing: false,
            startAutoRefresh() {
                if (this.autoRefresh && !this.refreshTimer) {
                    this.refreshTimer = setInterval(() => {
                        if (!this.isRefreshing) {
                            this.isRefreshing = true;
                            $wire.refreshData().then(() => {
                                this.isRefreshing = false;
                            });
                        }
                    }, this.refreshInterval * 1000);
                }
            },
            stopAutoRefresh() {
                if (this.refreshTimer) {
                    clearInterval(this.refreshTimer);
                    this.refreshTimer = null;
                }
            }
         }"
         x-init="$watch('autoRefresh', value => value ? startAutoRefresh() : stopAutoRefresh())"
         @refresh-data.window="$wire.refreshData()">
         
        {{-- System Status Header with Manual Refresh Control --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold">MCP Control Center</h2>
                    <p class="text-blue-100 mt-1">Real-time system monitoring and control</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">System Status</div>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-3 h-3 rounded-full" 
                             :class="{
                                'bg-green-400': '{{ $systemStatus['overall'] }}' === 'healthy',
                                'bg-red-400': '{{ $systemStatus['overall'] }}' !== 'healthy',
                                'animate-pulse': autoRefresh
                             }"></div>
                        <span class="text-xl font-semibold uppercase">{{ $systemStatus['overall'] ?? 'Unknown' }}</span>
                    </div>
                    <div class="text-xs text-blue-200 mt-1">Last check: {{ $systemStatus['lastCheck'] ?? 'Never' }}</div>
                    
                    {{-- Refresh Controls --}}
                    <div class="mt-3 flex items-center gap-2">
                        <button wire:click="refreshData" 
                                wire:loading.attr="disabled"
                                class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded text-sm transition">
                            <span wire:loading.remove>Refresh</span>
                            <span wire:loading>Loading...</span>
                        </button>
                        
                        <label class="flex items-center gap-1 text-sm">
                            <input type="checkbox" x-model="autoRefresh" class="rounded">
                            <span>Auto-refresh</span>
                        </label>
                        
                        <select x-model.number="refreshInterval" 
                                x-show="autoRefresh"
                                @change="stopAutoRefresh(); startAutoRefresh()"
                                class="px-2 py-1 bg-white/20 rounded text-sm">
                            <option value="10">10s</option>
                            <option value="30">30s</option>
                            <option value="60">1m</option>
                            <option value="300">5m</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Live Metrics Bar - Static rendering, no constant updates --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Requests', 'value' => number_format($liveMetrics['totalRequests']), 'icon' => 'arrow-trending-up', 'color' => 'green'],
                ['label' => 'Success Rate', 'value' => $liveMetrics['successRate'] . '%', 'icon' => 'check-circle', 'color' => 'green'],
                ['label' => 'Avg Response', 'value' => $liveMetrics['avgResponseTime'] . 'ms', 'icon' => 'clock', 'color' => 'blue'],
                ['label' => 'Active Servers', 'value' => count($servers), 'icon' => 'server-stack', 'color' => 'purple']
            ] as $metric)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metric['value'] }}</p>
                    </div>
                    <x-dynamic-component :component="'heroicon-o-' . $metric['icon']" 
                                       class="w-8 h-8 text-{{ $metric['color'] }}-500" />
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- MCP Servers Grid - Optimized rendering --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($servers as $serverName => $server)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                {{-- Server Header --}}
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $server['name'] }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $server['description'] }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $server['status'] === 'healthy' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                   ($server['status'] === 'degraded' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                {{ ucfirst($server['status']) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Server Metrics --}}
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Requests</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($server['stats']['total_requests']) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Success Rate</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $server['stats']['success_rate'] }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Avg Response</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $server['stats']['avg_response_time'] }}ms
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Last Error</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $server['stats']['last_error'] ?: 'None' }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Quick Actions --}}
                    <div class="flex gap-2">
                        <button wire:click="testServer('{{ $serverName }}')" 
                                wire:loading.attr="disabled"
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            Test
                        </button>
                        <button wire:click="viewServerLogs('{{ $serverName }}')"
                                class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                            Logs
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Recent Operations - Limited to last 10 --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Operations</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse(array_slice($recentOperations, 0, 10) as $operation)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $operation['timestamp'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $operation['server'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $operation['method'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $operation['duration'] }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $operation['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $operation['status'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No recent operations
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Performance monitoring for this page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[MCP Control Center] Page loaded - Manual refresh mode by default');
            
            // Log performance metrics after load
            setTimeout(() => {
                if (performance.memory) {
                    console.log('[Performance] Memory usage:', Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) + 'MB');
                }
                console.log('[Performance] DOM nodes:', document.getElementsByTagName('*').length);
            }, 1000);
        });
    </script>
</x-filament-panels::page>