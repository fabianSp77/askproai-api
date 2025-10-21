<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Export Analytics Data</h2>
            <p class="text-gray-600 mb-6">Download comprehensive policy analytics reports in your preferred format.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-2">CSV Export</h3>
                    <p class="text-sm text-gray-600 mb-4">Download analytics data in CSV format for Excel or Google Sheets.</p>
                    <x-filament::button wire:click="exportToCsv" icon="heroicon-o-document-arrow-down">
                        Download CSV
                    </x-filament::button>
                </div>

                <div class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-2">JSON Export</h3>
                    <p class="text-sm text-gray-600 mb-4">Download raw data in JSON format for programmatic use.</p>
                    <x-filament::button wire:click="exportToJson" icon="heroicon-o-document-arrow-down" color="primary">
                        Download JSON
                    </x-filament::button>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Export Contents</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Summary metrics (last 30 days)</li>
                            <li>Violations by policy type</li>
                            <li>Daily violation trend</li>
                            <li>Top violating customers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
