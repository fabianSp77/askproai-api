<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Basic System Status</h2>
            <p>This is a basic page to test if Filament pages are working.</p>
            
            <div class="mt-4">
                <p class="text-sm text-gray-600">PHP Version: {{ phpversion() }}</p>
                <p class="text-sm text-gray-600">Laravel Version: {{ app()->version() }}</p>
                <p class="text-sm text-gray-600">Current Time: {{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>