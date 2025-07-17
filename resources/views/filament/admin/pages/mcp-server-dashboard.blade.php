<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-server class="h-8 w-8 text-blue-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Servers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quickStats['total_servers'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-check-circle class="h-8 w-8 text-green-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Active Servers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quickStats['active_servers'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-puzzle-piece class="h-8 w-8 text-purple-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Capabilities</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quickStats['total_capabilities'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-red-500" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Recent Errors</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $quickStats['recent_errors'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Internal MCP Servers --}}
        <div>
            <h2 class="text-lg font-semibold mb-4">Internal MCP Servers</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($internalServers as $server)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$server['icon']" 
                                        class="h-8 w-8 text-{{ $server['color'] }}-500" 
                                    />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $server['display_name'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">{{ $server['description'] }}</p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($server['status'] === 'active')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($server['status'] === 'error')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Error
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Unknown
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Capabilities --}}
                        @if(count($server['capabilities']) > 0)
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 mb-1">Capabilities:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($server['capabilities'], 0, 3) as $capability)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $capability }}
                                </span>
                                @endforeach
                                @if(count($server['capabilities']) > 3)
                                <span class="text-xs text-gray-500">+{{ count($server['capabilities']) - 3 }} more</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        {{-- Metrics --}}
                        @if(!empty($server['metrics']))
                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="text-gray-500">Requests</p>
                                <p class="font-medium">{{ number_format($server['metrics']['requests'] ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Errors</p>
                                <p class="font-medium text-red-600">{{ number_format($server['metrics']['errors'] ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Avg Time</p>
                                <p class="font-medium">{{ $server['metrics']['avg_duration'] ?? 0 }}ms</p>
                            </div>
                        </div>
                        @endif
                        
                        {{-- Actions --}}
                        <div class="mt-4 flex space-x-2">
                            <button 
                                wire:click="executeQuickAction('{{ $server['name'] }}', 'test')"
                                class="flex-1 inline-flex justify-center items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Test
                            </button>
                            <button 
                                wire:click="showServerDetails('{{ $server['name'] }}')"
                                class="flex-1 inline-flex justify-center items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Details
                            </button>
                            <button 
                                wire:click="executeQuickAction('{{ $server['name'] }}', 'discover')"
                                class="flex-1 inline-flex justify-center items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Discover
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- External MCP Servers --}}
        <div>
            <h2 class="text-lg font-semibold mb-4">External MCP Servers</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($externalServers as $server)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$server['icon']" 
                                        class="h-8 w-8 text-{{ $server['color'] }}-500" 
                                    />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $server['display_name'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">{{ $server['description'] }}</p>
                                    <p class="text-xs text-gray-400 mt-1">npm: {{ $server['npm_package'] }}</p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($server['status'] === 'active')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Running
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Stopped
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="mt-4">
                            @if($server['can_start'])
                            <button 
                                wire:click="startExternalServer('{{ $server['name'] }}')"
                                class="w-full inline-flex justify-center items-center px-3 py-1 border border-green-300 shadow-sm text-xs font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                Start Server
                            </button>
                            @else
                            <button 
                                wire:click="executeQuickAction('{{ $server['name'] }}', 'restart')"
                                class="w-full inline-flex justify-center items-center px-3 py-1 border border-yellow-300 shadow-sm text-xs font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                            >
                                Restart
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Integrations --}}
        <div>
            <h2 class="text-lg font-semibold mb-4">Active Integrations</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($integrations as $integration)
                    <li class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <x-dynamic-component 
                                    :component="$integration['icon']" 
                                    class="h-6 w-6 text-gray-400 mr-3" 
                                />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $integration['name'] }}
                                    </p>
                                    @if($integration['last_sync'])
                                    <p class="text-xs text-gray-500">
                                        Last sync: {{ $integration['last_sync'] }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($integration['status'] === 'active') bg-green-100 text-green-800
                                    @elseif($integration['status'] === 'idle') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($integration['status']) }}
                                </span>
                                @foreach($integration['actions'] as $action)
                                <button 
                                    wire:click="runIntegrationAction('{{ $integration['name'] }}', '{{ $action }}')"
                                    class="text-xs text-indigo-600 hover:text-indigo-900"
                                >
                                    {{ ucfirst($action) }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Recent Activities --}}
        @if(count($recentActivities) > 0)
        <div>
            <h2 class="text-lg font-semibold mb-4">Recent Activities</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentActivities as $activity)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ ucfirst($activity['server']) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $activity['action'] }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($activity['time'])->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($activity['success'])
                                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                                @else
                                    <x-heroicon-o-x-circle class="h-5 w-5 text-red-500" />
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Auto-refresh every 60 seconds --}}
    <script>
        setInterval(() => {
            @this.call('loadAllData');
        }, 60000);
    </script>
</x-filament-panels::page>