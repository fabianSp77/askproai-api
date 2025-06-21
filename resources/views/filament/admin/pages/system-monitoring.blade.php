<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {{-- System Health Cards --}}
        @foreach($systemHealth as $service => $health)
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">{{ ucfirst($service) }}</h3>
                        <p class="text-sm text-gray-500">{{ $health['message'] }}</p>
                        @if(isset($health['response_time']))
                            <p class="text-xs text-gray-400">Response: {{ $health['response_time'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center">
                        @if($health['status'] === 'healthy')
                            <x-heroicon-o-check-circle class="w-8 h-8 text-success-500" />
                        @elseif($health['status'] === 'warning')
                            <x-heroicon-o-exclamation-circle class="w-8 h-8 text-warning-500" />
                        @else
                            <x-heroicon-o-x-circle class="w-8 h-8 text-danger-500" />
                        @endif
                    </div>
                </div>
            </x-filament::card>
        @endforeach
    </div>

    {{-- Performance Metrics --}}
    <x-filament::card class="mt-6">
        <h2 class="text-xl font-bold mb-4">Performance Metrics</h2>
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div class="text-center">
                <p class="text-2xl font-bold">{{ $performanceMetrics['avg_response_time'] }}ms</p>
                <p class="text-sm text-gray-500">Avg Response Time</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold">{{ $performanceMetrics['cache_hit_rate'] }}%</p>
                <p class="text-sm text-gray-500">Cache Hit Rate</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold">{{ number_format($performanceMetrics['db_queries']) }}</p>
                <p class="text-sm text-gray-500">DB Queries</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold">{{ $performanceMetrics['db_slow_queries'] }}</p>
                <p class="text-sm text-gray-500">Slow Queries</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold">{{ $performanceMetrics['db_connections'] }}</p>
                <p class="text-sm text-gray-500">DB Connections</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold">{{ number_format($performanceMetrics['webhooks_processed']) }}</p>
                <p class="text-sm text-gray-500">Webhooks/Hour</p>
            </div>
        </div>
    </x-filament::card>

    {{-- Queue Status --}}
    <x-filament::card class="mt-6">
        <h2 class="text-xl font-bold mb-4">Queue Status</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Queue</th>
                        <th class="text-center py-2">Pending</th>
                        <th class="text-center py-2">Processing</th>
                        <th class="text-center py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($queueStatus as $queue)
                        <tr class="border-b">
                            <td class="py-2">{{ $queue['name'] }}</td>
                            <td class="text-center py-2">{{ $queue['pending'] }}</td>
                            <td class="text-center py-2">{{ $queue['processing'] }}</td>
                            <td class="text-center py-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    @if($queue['status'] === 'healthy') bg-success-100 text-success-800
                                    @elseif($queue['status'] === 'warning') bg-warning-100 text-warning-800
                                    @else bg-danger-100 text-danger-800
                                    @endif">
                                    {{ ucfirst($queue['status']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>

    {{-- Recent Errors --}}
    @if(count($recentErrors) > 0)
        <x-filament::card class="mt-6">
            <h2 class="text-xl font-bold mb-4">Recent Errors (Last 24h)</h2>
            <div class="space-y-2">
                @foreach($recentErrors as $error)
                    <div class="p-3 bg-danger-50 rounded-lg">
                        <p class="text-sm font-medium text-danger-800">{{ $error['message'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $error['time'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::card>
    @endif
</x-filament-panels::page>