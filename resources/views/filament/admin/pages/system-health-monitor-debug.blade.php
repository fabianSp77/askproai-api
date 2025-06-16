<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $debugInfo }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">System Data</h2>
            <pre class="text-xs overflow-x-auto">{{ json_encode($systemData, JSON_PRETTY_PRINT) }}</pre>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Test Links</h2>
            <div class="space-y-2">
                <a href="{{ url('/admin/ultimate-system-cockpit') }}" class="text-blue-500 hover:underline">
                    Ultimate System Cockpit
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>