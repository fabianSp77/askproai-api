<x-filament-panels::page>
    <div class="monitoring-dashboard-container space-y-6">
        {{-- Auto Refresh Toggle --}}
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg shadow monitoring-card">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-medium">Auto-Refresh</h3>
                <x-filament::input.wrapper>
                    <x-filament::input.checkbox
                        wire:model.live="autoRefresh"
                        wire:click="$dispatch('toggle-auto-refresh')"
                    />
                </x-filament::input.wrapper>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Alle {{ $refreshInterval }}s aktualisieren
                </span>
            </div>
            <div class="text-sm text-gray-500">
                Letzte Aktualisierung: {{ now()->format('H:i:s') }}
            </div>
        </div>

        {{-- System Overview Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 monitoring-grid-xl gap-4">
            {{-- Database Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 monitoring-metric-card" 
                 wire:loading.class="monitoring-loading">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Database</h3>
                    <div class="w-3 h-3 rounded-full {{ $systemMetrics['database']['status'] === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Verbindungen:</dt>
                        <dd class="font-medium">{{ $systemMetrics['database']['connections'] ?? 0 }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Aktive Queries:</dt>
                        <dd class="font-medium">{{ $systemMetrics['database']['active_queries'] ?? 0 }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Redis Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Redis Cache</h3>
                    <div class="w-3 h-3 rounded-full {{ $systemMetrics['redis']['status'] === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Memory:</dt>
                        <dd class="font-medium">{{ $systemMetrics['redis']['memory'] ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Clients:</dt>
                        <dd class="font-medium">{{ $systemMetrics['redis']['connections'] ?? 0 }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Server Load --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Server Load</h3>
                    @php
                        $load = $systemMetrics['server']['load_average']['1m'] ?? 0;
                        $loadClass = $load > 4 ? 'bg-red-500' : ($load > 2 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <div class="w-3 h-3 rounded-full {{ $loadClass }}"></div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">1m / 5m / 15m:</dt>
                        <dd class="font-medium text-xs">
                            {{ $systemMetrics['server']['load_average']['1m'] }} /
                            {{ $systemMetrics['server']['load_average']['5m'] }} /
                            {{ $systemMetrics['server']['load_average']['15m'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Disk:</dt>
                        <dd class="font-medium">{{ $systemMetrics['server']['disk_usage'] ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Memory:</dt>
                        <dd class="font-medium">{{ $systemMetrics['server']['memory_usage'] ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Queue Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Queue System</h3>
                    <div class="w-3 h-3 rounded-full {{ $queueStatus['horizon']['status'] === 'running' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Horizon:</dt>
                        <dd class="font-medium">{{ ucfirst($queueStatus['horizon']['status']) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Failed Jobs:</dt>
                        <dd class="font-medium {{ $queueStatus['failed_jobs'] > 0 ? 'text-red-600' : '' }}">
                            {{ $queueStatus['failed_jobs'] }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Rate:</dt>
                        <dd class="font-medium">{{ $queueStatus['processing_rate'] }}/min</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- API Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold">External API Status</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($apiStatus as $key => $api)
                        @if($key !== 'circuit_breakers')
                            <div class="border rounded-lg p-4 
                                {{ $api['status'] === 'online' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 
                                   ($api['status'] === 'offline' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 
                                    'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20') }}">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-medium">{{ $api['name'] }}</h3>
                                    <span class="px-2 py-1 text-xs rounded-full font-medium
                                        {{ $api['status'] === 'online' ? 'bg-green-500 text-white' : 
                                           ($api['status'] === 'offline' ? 'bg-red-500 text-white' : 
                                            'bg-yellow-500 text-white') }}">
                                        {{ ucfirst($api['status']) }}
                                    </span>
                                </div>
                                @if($api['response_time'])
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Response: {{ $api['response_time'] }}ms
                                    </p>
                                @endif
                                @if(isset($api['error']))
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                        {{ Str::limit($api['error'], 50) }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Circuit Breakers --}}
                @if(isset($apiStatus['circuit_breakers']))
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="font-medium mb-3">Circuit Breakers</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($apiStatus['circuit_breakers'] as $service => $state)
                                <span class="px-3 py-1 text-sm rounded-full
                                    {{ $state === 'closed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ ucfirst($service) }}: {{ ucfirst($state) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Real-time Statistics --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold">Real-time Statistics</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 monitoring-grid-xl gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 monitoring-stat-value">{{ $realtimeStats['active_calls'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 monitoring-stat-label">Aktive Anrufe</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">{{ $realtimeStats['today_appointments'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Termine heute</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ $realtimeStats['completed_today'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Abgeschlossen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600">{{ $realtimeStats['active_companies'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Aktive Firmen</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-pink-600">{{ $realtimeStats['active_phones'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Aktive Nummern</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $realtimeStats['recent_webhooks'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Webhooks (5m)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Queue Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Queue Sizes --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold">Queue Sizes</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($queueStatus['queues'] as $queue => $size)
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">{{ ucfirst($queue) }}</span>
                                    <span class="text-sm {{ $size > 100 ? 'text-red-600' : 'text-gray-600' }}">
                                        {{ $size }} jobs
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $size > 100 ? 'bg-red-600' : 'bg-blue-600' }}"
                                         style="width: {{ min($size, 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Performance Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold">Performance Metrics</h2>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cache Hit Rate</dt>
                            <dd class="mt-1">
                                <div class="flex items-center">
                                    <div class="flex-1 bg-gray-200 rounded-full h-4 mr-3">
                                        <div class="h-4 rounded-full bg-green-600" 
                                             style="width: {{ $performanceMetrics['cache_hit_rate'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ $performanceMetrics['cache_hit_rate'] }}%</span>
                                </div>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Slow Queries (24h)</dt>
                            <dd class="mt-1 text-2xl font-semibold {{ $performanceMetrics['slow_queries'] > 10 ? 'text-red-600' : '' }}">
                                {{ $performanceMetrics['slow_queries'] }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Error Logs --}}
        @if(count($errorLogs) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold">Recent Errors</h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($errorLogs->take(5) as $log)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">
                                            {{ \Carbon\Carbon::parse($log['created_at'])->format('H:i:s') }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 text-xs rounded-full
                                                {{ $log['level'] === 'error' ? 'bg-red-100 text-red-800' : 
                                                   ($log['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                                                    'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($log['level']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $log['message'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Auto-refresh Script --}}
    @if($autoRefresh)
        <script>
            let refreshInterval;
            
            document.addEventListener('livewire:init', () => {
                refreshInterval = setInterval(() => {
                    @this.refresh();
                }, {{ $refreshInterval * 1000 }});
            });
            
            document.addEventListener('livewire:navigating', () => {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            });
        </script>
    @endif
</x-filament-panels::page>