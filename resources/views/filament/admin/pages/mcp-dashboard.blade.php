<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with auto-refresh --}}
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">MCP System Monitor</h2>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">Last update: {{ $systemHealth['last_check'] ?? 'Never' }}</span>
                <x-filament::button 
                    wire:click="refreshData"
                    size="sm"
                    wire:loading.attr="disabled"
                >
                    <x-filament::loading-indicator wire:loading wire:target="refreshData" class="h-4 w-4 mr-2" />
                    Refresh
                </x-filament::button>
            </div>
        </div>

        {{-- System Health Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">System Health</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Overall Status --}}
                <div class="text-center">
                    <div class="text-3xl font-bold 
                        @if($systemHealth['status'] === 'healthy') text-green-600
                        @elseif($systemHealth['status'] === 'degraded') text-yellow-600
                        @else text-red-600
                        @endif">
                        @if($systemHealth['status'] === 'healthy')
                            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto" />
                        @elseif($systemHealth['status'] === 'degraded')
                            <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto" />
                        @else
                            <x-heroicon-o-x-circle class="w-12 h-12 mx-auto" />
                        @endif
                    </div>
                    <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ ucfirst($systemHealth['status'] ?? 'Unknown') }}
                    </p>
                </div>

                {{-- Service Status Grid --}}
                @foreach($systemHealth['services'] ?? [] as $service => $status)
                    <div class="text-center">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($service) }}</div>
                        <div class="mt-1">
                            @if($status === 'healthy')
                                <span class="text-green-600">✓ Healthy</span>
                            @else
                                <span class="text-red-600">✗ Unhealthy</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Performance Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requests</h4>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $performanceMetrics['total_requests'] ?? '0' }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Rate</h4>
                <p class="mt-2 text-2xl font-bold 
                    @if(($performanceMetrics['error_rate'] ?? 0) > 5) text-red-600
                    @elseif(($performanceMetrics['error_rate'] ?? 0) > 2) text-yellow-600
                    @else text-green-600
                    @endif">
                    {{ $performanceMetrics['error_rate'] ?? '0' }}%
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Latency</h4>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $performanceMetrics['avg_latency'] ?? '0' }}ms
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">P99 Latency</h4>
                <p class="mt-2 text-2xl font-bold 
                    @if(($performanceMetrics['p99_latency'] ?? 0) > 1000) text-red-600
                    @elseif(($performanceMetrics['p99_latency'] ?? 0) > 500) text-yellow-600
                    @else text-green-600
                    @endif">
                    {{ $performanceMetrics['p99_latency'] ?? '0' }}ms
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed Jobs</h4>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $queueStatus['failed_jobs'] ?? '0' }}
                </p>
            </div>
        </div>

        {{-- Service Metrics Table --}}
        @if(count($serviceMetrics) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Service Metrics (Last Hour)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min/Max</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($serviceMetrics as $metric)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ ucfirst($metric['service']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $metric['requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($metric['success_rate'] >= 99) bg-green-100 text-green-800
                                    @elseif($metric['success_rate'] >= 95) bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $metric['success_rate'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $metric['avg_duration'] }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $metric['min_duration'] }}ms / {{ $metric['max_duration'] }}ms
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Connection Pool and Queue Status --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Connection Pool Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Connection Pools</h3>
                @foreach($connectionPoolStats as $pool => $stats)
                    @if(is_array($stats) && isset($stats['total']))
                    <div class="mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium">{{ ucfirst($pool) }}</span>
                            <span class="text-gray-500">{{ $stats['active'] }}/{{ $stats['total'] }} active</span>
                        </div>
                        <div class="mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['utilization'] }}%"></div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Queue Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Queue Status</h3>
                @foreach($queueStatus['queues'] ?? [] as $queue => $info)
                <div class="mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ ucfirst($queue) }}</span>
                        <span class="
                            @if($info['status'] === 'high') text-red-600
                            @elseif($info['status'] === 'medium') text-yellow-600
                            @else text-green-600
                            @endif">
                            {{ $info['size'] }} jobs
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Active Operations --}}
        @if(count($activeOperations) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Active Operations</h3>
            <div class="space-y-2">
                @foreach($activeOperations as $op)
                <div class="flex justify-between items-center text-sm">
                    <span class="font-mono text-gray-600">{{ $op['id'] }}</span>
                    <span class="font-medium">{{ $op['service'] }}::{{ $op['operation'] }}</span>
                    <span class="text-gray-500">{{ $op['duration'] }}ms</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent Errors --}}
        @if(count($recentErrors) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 text-red-600">Recent Errors</h3>
            <div class="space-y-3">
                @foreach($recentErrors as $error)
                <div class="border-l-4 border-red-400 pl-4">
                    <div class="flex justify-between">
                        <span class="font-medium text-sm">{{ $error['service'] }}::{{ $error['operation'] }}</span>
                        <span class="text-xs text-gray-500">{{ $error['time'] }}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $error['error'] }}</p>
                    @if($error['tenant_id'])
                        <span class="text-xs text-gray-500">Tenant: {{ $error['tenant_id'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Auto-refresh script --}}
    <script>
        // Auto-refresh every 30 seconds
        setInterval(() => {
            @this.call('refreshData');
        }, 30000);
    </script>
</x-filament-panels::page>