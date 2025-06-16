<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">System Health Monitor (Minimal Test)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 rounded p-4">
                    <h3 class="font-semibold">Overall Health</h3>
                    <p class="text-3xl font-bold">{{ $systemMetrics['overall_health'] }}%</p>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded p-4">
                    <h3 class="font-semibold">Active Calls</h3>
                    <p class="text-3xl font-bold">{{ $systemMetrics['active_calls'] }}</p>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded p-4">
                    <h3 class="font-semibold">Queue Size</h3>
                    <p class="text-3xl font-bold">{{ $systemMetrics['queue_size'] }}</p>
                </div>
            </div>
            
            <div class="mt-6">
                <p class="text-sm text-gray-600">If you can see this page, the basic setup is working.</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>