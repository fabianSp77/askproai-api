<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-4">System Health Monitor (Debug)</h1>
            
            @if(isset($systemMetrics))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold">{{ $systemMetrics['overall_health'] ?? 'N/A' }}%</div>
                        <div class="text-sm text-gray-500">Overall Health</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold">{{ $systemMetrics['active_calls'] ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">Active Calls</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold">{{ $systemMetrics['queue_size'] ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">Queue Size</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold">{{ $systemMetrics['response_time'] ?? 'N/A' }}ms</div>
                        <div class="text-sm text-gray-500">Response Time</div>
                    </div>
                </div>
            @else
                <p>No system metrics available</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>