<x-filament-panels::page>
    <div class="space-y-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-2">Debug Information</h2>
            
            <div class="space-y-2">
                <p><strong>User:</strong> {{ auth()->user()->email ?? 'Not logged in' }}</p>
                <p><strong>PHP Version:</strong> {{ PHP_VERSION }}</p>
                <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
                <p><strong>Current Time:</strong> {{ now() }}</p>
            </div>
        </div>
        
        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <p class="text-green-800 dark:text-green-200">
                If you can see this page, Filament is working correctly!
            </p>
        </div>
    </div>
</x-filament-panels::page>