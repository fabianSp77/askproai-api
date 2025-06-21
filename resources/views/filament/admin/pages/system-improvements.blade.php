<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Performance Score -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Performance Score</p>
                    <p class="text-2xl font-bold {{ $performanceScore >= 80 ? 'text-green-600' : ($performanceScore >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $performanceScore }}%
                    </p>
                </div>
                <x-heroicon-o-chart-bar class="w-8 h-8 text-gray-400" />
            </div>
        </div>

        <!-- UI/UX Score -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">UI/UX Score</p>
                    <p class="text-2xl font-bold {{ $uiuxScore >= 80 ? 'text-green-600' : ($uiuxScore >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $uiuxScore }}%
                    </p>
                </div>
                <x-heroicon-o-paint-brush class="w-8 h-8 text-gray-400" />
            </div>
        </div>

        <!-- Discovered MCPs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Discovered MCPs</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $mcpCount }}</p>
                </div>
                <x-heroicon-o-puzzle-piece class="w-8 h-8 text-gray-400" />
            </div>
        </div>

        <!-- Active Issues -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Issues</p>
                    <p class="text-2xl font-bold {{ $bottleneckCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $bottleneckCount }}
                    </p>
                </div>
                <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-gray-400" />
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mb-6 flex gap-3">
        <x-filament::button wire:click="runDiscovery" icon="heroicon-o-magnifying-glass">
            Discover MCPs
        </x-filament::button>
        <x-filament::button wire:click="runAnalysis" icon="heroicon-o-chart-bar">
            Run Analysis
        </x-filament::button>
        <x-filament::button wire:click="refreshData" color="gray" icon="heroicon-o-arrow-path">
            Refresh
        </x-filament::button>
    </div>

    <!-- Recommendations -->
    @if($hasRecommendations)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Top Recommendations</h3>
            <div class="space-y-4">
                @foreach(array_slice($latestAnalysis['recommendations'] ?? [], 0, 5) as $rec)
                <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-shrink-0">
                        @if($rec['priority'] === 'high')
                        <x-heroicon-o-exclamation-circle class="w-6 h-6 text-red-500" />
                        @elseif($rec['priority'] === 'medium')
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-500" />
                        @else
                        <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500" />
                        @endif
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-medium">{{ $rec['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $rec['description'] }}</p>
                        @if(isset($rec['impact']))
                        <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                            Impact: {{ $rec['impact'] }}
                        </p>
                        @endif
                    </div>
                    @if(isset($rec['actionable']) && $rec['actionable'])
                    <x-filament::button 
                        wire:click="applyOptimization('{{ $rec['id'] ?? '' }}')" 
                        size="sm"
                        color="success"
                    >
                        Apply
                    </x-filament::button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Active Optimizations -->
    @if(count($activeOptimizations) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Active Optimizations</h3>
            <div class="space-y-3">
                @foreach($activeOptimizations as $opt)
                <div class="flex items-center gap-4">
                    <div class="flex-grow">
                        <p class="font-medium">{{ $opt['type'] }}</p>
                        @if($opt['status'] === 'in_progress')
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $opt['progress'] }}%"></div>
                        </div>
                        @elseif($opt['status'] === 'completed')
                        <p class="text-sm text-green-600">Completed - {{ $opt['impact'] ?? 'Success' }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        @if($opt['status'] === 'in_progress')
                        <x-heroicon-o-arrow-path class="w-5 h-5 text-blue-500 animate-spin" />
                        @else
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Resource Usage -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- MCP Discovery Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">MCP Discovery Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total Discovered</span>
                        <span class="font-medium">{{ $mcpCatalog['total_discovered'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">High Relevance</span>
                        <span class="font-medium text-green-600">{{ $mcpCatalog['high_relevance'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Last Check</span>
                        <span class="font-medium">{{ $mcpCatalog['last_check'] ?? 'Never' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">System Metrics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">CPU Usage</span>
                        <span class="font-medium">{{ $performanceMetrics['cpu_usage'] ?? 'N/A' }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Memory Usage</span>
                        <span class="font-medium">{{ $performanceMetrics['memory_usage'] ?? 'N/A' }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Response Time</span>
                        <span class="font-medium">{{ $performanceMetrics['avg_response_time'] ?? 'N/A' }}ms</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        Livewire.on('data-refreshed', () => {
            // Could add animations or other UI updates
        });
    </script>
    @endpush
</x-filament-panels::page>