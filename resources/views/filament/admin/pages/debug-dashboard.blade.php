<x-filament-panels::page>
    <div class="space-y-6">
        
        @if(session('message'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-800 dark:text-green-200">{{ session('message') }}</span>
                </div>
            </div>
        @endif
        
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="flex space-x-4">
                <button wire:click="clearCache" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Clear Cache
                </button>
                <button wire:click="clearLogs" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    Clear Logs
                </button>
                <button wire:click="runOptimizations" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Run Optimizations
                </button>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- PHP & Laravel Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">System Information</h3>
                <dl class="space-y-2">
                    @foreach($systemInfo as $key => $value)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}:</dt>
                            <dd class="text-sm font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            
            <!-- Performance Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Performance</h3>
                <dl class="space-y-2">
                    @foreach($performanceInfo as $key => $value)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}:</dt>
                            <dd class="text-sm font-medium">
                                @if($key === 'load_average' && is_array($value))
                                    {{ implode(' / ', $value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            
        </div>
        
        <!-- Database Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Database Information</h3>
            
            @if(isset($databaseInfo['error']))
                <p class="text-red-600">Error: {{ $databaseInfo['error'] }}</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Connection</p>
                        <p class="font-medium">{{ $databaseInfo['connection'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Database</p>
                        <p class="font-medium">{{ $databaseInfo['database'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Version</p>
                        <p class="font-medium">{{ $databaseInfo['version'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tables</p>
                        <p class="font-medium">{{ $databaseInfo['tables_count'] }}</p>
                    </div>
                </div>
                
                <h4 class="font-medium mb-2">Largest Tables</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Table</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Size (MB)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rows</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($databaseInfo['largest_tables'] as $table)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $table->table_name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $table->size_mb }}</td>
                                    <td class="px-4 py-2 text-sm">{{ number_format($table->table_rows) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        
        <!-- Cache Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Cache Information</h3>
                @if(isset($cacheInfo['error']))
                    <p class="text-red-600">Error: {{ $cacheInfo['error'] }}</p>
                @else
                    <dl class="space-y-2">
                        @foreach($cacheInfo as $key => $value)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}:</dt>
                                <dd class="text-sm font-medium">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Queue Information</h3>
                @if(isset($queueInfo['error']))
                    <p class="text-red-600">Error: {{ $queueInfo['error'] }}</p>
                @else
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Driver:</dt>
                            <dd class="text-sm font-medium">{{ $queueInfo['driver'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Horizon Status:</dt>
                            <dd class="text-sm font-medium">{{ $queueInfo['horizon_status'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Failed Jobs:</dt>
                            <dd class="text-sm font-medium">{{ $queueInfo['failed_jobs'] }}</dd>
                        </div>
                        @if(isset($queueInfo['queues']) && !isset($queueInfo['queues']['error']))
                            <div class="mt-2 pt-2 border-t">
                                <p class="text-sm font-medium mb-1">Queue Sizes:</p>
                                @foreach($queueInfo['queues'] as $queue => $size)
                                    <div class="flex justify-between pl-4">
                                        <dt class="text-sm text-gray-600">{{ ucfirst($queue) }}:</dt>
                                        <dd class="text-sm">{{ $size }}</dd>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </dl>
                @endif
            </div>
            
        </div>
        
        <!-- Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Configuration</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($configInfo as $key => $value)
                    @if($key !== 'services')
                        <div>
                            <p class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                            <p class="font-medium text-sm">{{ $value }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
            
            @if(isset($configInfo['services']))
                <div class="mt-4 pt-4 border-t">
                    <p class="font-medium mb-2">Service Configurations</p>
                    <div class="grid grid-cols-3 gap-4">
                        @foreach($configInfo['services'] as $service => $configured)
                            <div class="flex items-center">
                                @if($configured)
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                                <span class="text-sm">{{ ucwords(str_replace('_', ' ', $service)) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
    </div>
</x-filament-panels::page>