<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Requests</p>
                    <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
                </div>
                <x-heroicon-o-document-text class="w-8 h-8 text-gray-400" />
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Exports</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['exports'] }}</p>
                </div>
                <x-heroicon-o-arrow-down-tray class="w-8 h-8 text-blue-400" />
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Deletions</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['deletions'] }}</p>
                </div>
                <x-heroicon-o-trash class="w-8 h-8 text-red-400" />
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                </div>
                <x-heroicon-o-check-circle class="w-8 h-8 text-green-400" />
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
                </div>
                <x-heroicon-o-clock class="w-8 h-8 text-yellow-400" />
            </div>
        </div>
    </div>
    
    {{ $this->table }}
    
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">GDPR Compliance Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium mb-2">Data Export (Art. 15 GDPR)</h4>
                <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Customers can request a copy of their personal data</li>
                    <li>Export includes all stored information in JSON format</li>
                    <li>Download links expire after 7 days for security</li>
                    <li>Rate limited to 1 request per day per customer</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Data Deletion (Art. 17 GDPR)</h4>
                <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Customers can request deletion of their personal data</li>
                    <li>Requires email confirmation within 3 days</li>
                    <li>Data is anonymized rather than fully deleted</li>
                    <li>Legal retention requirements are respected</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <strong>Customer Portal URL:</strong> 
                <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ url('/privacy-tools') }}</code>
            </p>
        </div>
    </div>
</x-filament-panels::page>