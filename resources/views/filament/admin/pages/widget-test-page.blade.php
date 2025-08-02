<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Widget Test Page</h2>
            <p class="text-gray-600 dark:text-gray-400">This page is designed to test if widgets are rendering correctly.</p>
            
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Expected Widgets:</h3>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-200">
                    <li>CallLiveStatusWidget - Live call status indicator</li>
                    <li>GlobalFilterWidget - Global filters</li>
                    <li>CallKpiWidget - Call KPI statistics</li>
                    <li>CallAnalyticsWidget - Call analytics</li>
                </ul>
            </div>
            
            <div class="mt-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Debug Info:</h3>
                <pre class="bg-gray-100 dark:bg-gray-900 rounded p-4 overflow-x-auto text-sm">{{ json_encode([
                    'user' => auth()->user()->email ?? 'Not authenticated',
                    'company' => auth()->user()->company->name ?? 'No company',
                    'widgets_count' => count($this->getHeaderWidgets()),
                    'timestamp' => now()->toDateTimeString()
                ], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</x-filament-panels::page>