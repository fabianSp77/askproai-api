<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s="refreshData">
        {{-- System Status Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold">MCP Control Center</h2>
                    <p class="text-blue-100 mt-1">Real-time system monitoring and control</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">System Status</div>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-3 h-3 rounded-full animate-pulse {{ $systemStatus['overall'] === 'healthy' ? 'bg-green-400' : 'bg-red-400' }}"></div>
                        <span class="text-xl font-semibold uppercase">{{ $systemStatus['overall'] ?? 'Unknown' }}</span>
                    </div>
                    <div class="text-xs text-blue-200 mt-1">Last check: {{ $systemStatus['lastCheck'] ?? 'Never' }}</div>
                </div>
            </div>
        </div>
        
        {{-- Live Metrics Bar --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($liveMetrics['totalRequests']) }}</p>
                    </div>
                    <x-heroicon-o-arrow-trending-up class="w-8 h-8 text-green-500" />
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Success Rate</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $liveMetrics['successRate'] }}%</p>
                    </div>
                    <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Avg Response</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $liveMetrics['avgResponseTime'] }}ms</p>
                    </div>
                    <x-heroicon-o-clock class="w-8 h-8 text-blue-500" />
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active Connections</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $liveMetrics['activeConnections'] }}</p>
                    </div>
                    <x-heroicon-o-signal class="w-8 h-8 text-purple-500" />
                </div>
            </div>
        </div>
        
        {{-- Service Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($serviceCards as $serviceKey => $service)
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                    {{-- Status Indicator --}}
                    <div class="absolute top-4 right-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($service['status']) }}</span>
                            <div class="w-3 h-3 rounded-full {{ $service['status'] === 'healthy' ? 'bg-green-500' : 'bg-red-500' }} animate-pulse"></div>
                        </div>
                    </div>
                    
                    {{-- Card Header --}}
                    <div class="p-6 pb-4">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-{{ $service['color'] }}-100 dark:bg-{{ $service['color'] }}-900/20 rounded-xl">
                                <x-dynamic-component :component="$service['icon']" class="w-8 h-8 text-{{ $service['color'] }}-600 dark:text-{{ $service['color'] }}-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $service['title'] }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $serviceKey }} service</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Stats Grid --}}
                    <div class="px-6 pb-4">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            @foreach($service['stats'] as $label => $value)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Quick Actions --}}
                    <div class="px-6 pb-6 pt-2">
                        <div class="flex gap-2">
                            <button 
                                wire:click="executeQuickAction('{{ $serviceKey }}', 'health')"
                                class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                Health Check
                            </button>
                            <button 
                                wire:click="executeQuickAction('{{ $serviceKey }}', 'stats')"
                                class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                Get Stats
                            </button>
                            <button 
                                wire:click="executeQuickAction('{{ $serviceKey }}', 'test')"
                                class="flex-1 px-3 py-2 bg-{{ $service['color'] }}-100 dark:bg-{{ $service['color'] }}-900/20 hover:bg-{{ $service['color'] }}-200 dark:hover:bg-{{ $service['color'] }}-900/40 rounded-lg text-xs font-medium text-{{ $service['color'] }}-700 dark:text-{{ $service['color'] }}-400 transition-colors"
                            >
                                Test
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Recent Operations & Quick Response --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent Operations --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Operations</h3>
                <div class="space-y-2">
                    @forelse($recentOperations as $operation)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full {{ $operation['status'] === 'success' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $operation['service'] }}::{{ $operation['operation'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $operation['time'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $operation['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                {{ ucfirst($operation['status']) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent operations</p>
                    @endforelse
                </div>
            </div>
            
            {{-- Quick Response --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Response</h3>
                @if(!empty($quickResponse))
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Service:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $quickResponse['service'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Action:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $quickResponse['action'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Status:</span>
                            <span class="text-sm font-medium {{ $quickResponse['success'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $quickResponse['success'] ? 'Success' : 'Failed' }}
                            </span>
                        </div>
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg overflow-x-auto">
                            <pre class="text-xs text-gray-800 dark:text-gray-200">{{ json_encode($quickResponse['data'] ?? $quickResponse['error'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Click any quick action button to see the response here</p>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Auto-refresh script --}}
    <script>
        // Visual feedback for updates
        document.addEventListener('livewire:load', function () {
            Livewire.on('dataRefreshed', () => {
                // Add subtle animation on refresh
                document.querySelectorAll('.animate-pulse').forEach(el => {
                    el.style.animation = 'none';
                    setTimeout(() => el.style.animation = '', 10);
                });
            });
        });
    </script>
</x-filament-panels::page>