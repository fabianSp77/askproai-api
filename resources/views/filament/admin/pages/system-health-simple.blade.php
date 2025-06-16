<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
            <h3 class="text-lg font-semibold mb-2">System Health</h3>
            <p class="text-4xl font-bold text-green-500">{{ $health }}%</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
            <h3 class="text-lg font-semibold mb-2">Active Calls</h3>
            <p class="text-4xl font-bold text-blue-500">{{ $calls }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
            <h3 class="text-lg font-semibold mb-2">Queue Size</h3>
            <p class="text-4xl font-bold text-yellow-500">{{ $queue }}</p>
        </div>
    </div>
</x-filament-panels::page>