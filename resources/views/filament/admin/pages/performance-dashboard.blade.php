<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Response Time --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Response Time</p>
                    <p class="text-2xl font-bold">{{ number_format($this->performanceMetrics['response_time_avg'], 0) }}ms</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </x-filament::card>
        
        {{-- Requests per Minute --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/Minute</p>
                    <p class="text-2xl font-bold">{{ number_format($this->performanceMetrics['requests_per_minute'], 0) }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-chart-bar class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </x-filament::card>
        
        {{-- Cache Hit Rate --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Hit Rate</p>
                    <p class="text-2xl font-bold">{{ number_format($this->performanceMetrics['cache_hit_rate'], 1) }}%</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-archive-box class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </x-filament::card>
        
        {{-- Slow Queries --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Slow Queries</p>
                    <p class="text-2xl font-bold">{{ $this->performanceMetrics['slow_queries_count'] }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </x-filament::card>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Cache Statistics --}}
        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">Cache Statistics</h3>
            <div class="space-y-3">
                @foreach($this->cacheStats as $type => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($type)) }}</span>
                        <span class="font-medium">{{ number_format($count) }} entries</span>
                    </div>
                @endforeach
            </div>
        </x-filament::card>
        
        {{-- Connection Pool Status --}}
        <x-filament::card>
            <h3 class="text-lg font-semibold mb-4">Connection Pool</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Active Connections</span>
                    <span class="font-medium">{{ $this->connectionPoolMetrics['connections']['active'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Idle Connections</span>
                    <span class="font-medium">{{ $this->connectionPoolMetrics['connections']['idle'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Usage</span>
                    <div class="flex items-center">
                        <span class="font-medium mr-2">{{ $this->connectionPoolMetrics['connections']['usage_percent'] ?? 0 }}%</span>
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $this->connectionPoolMetrics['connections']['usage_percent'] ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::card>
    </div>
    
    {{-- Database Tables --}}
    @if(!empty($this->databaseStats['largest_tables']))
        <x-filament::card class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Largest Database Tables</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2">Table</th>
                            <th class="text-right py-2">Rows</th>
                            <th class="text-right py-2">Size (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($this->databaseStats['largest_tables'], 0, 10) as $table)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">{{ $table->table_name }}</td>
                                <td class="text-right py-2">{{ number_format($table->table_rows) }}</td>
                                <td class="text-right py-2">{{ $table->size_mb }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    @endif
    
    {{-- Performance Tips --}}
    <x-filament::card class="mt-6">
        <h3 class="text-lg font-semibold mb-4">Performance Optimization Tips</h3>
        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li class="flex items-start">
                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 mt-0.5" />
                <span>Response compression is {{ $this->compressionEnabled ? 'enabled' : 'disabled' }}</span>
            </li>
            <li class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mr-2 mt-0.5" />
                <span>Run cache warming every 30 minutes for optimal performance</span>
            </li>
            <li class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mr-2 mt-0.5" />
                <span>Monitor slow queries regularly and create indexes as needed</span>
            </li>
            <li class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 mr-2 mt-0.5" />
                <span>Keep connection pool usage below 80% for best performance</span>
            </li>
        </ul>
    </x-filament::card>
</x-filament-panels::page>