<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Overview Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($services as $service => $status)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">{{ $status['name'] }}</h3>
                        <div class="flex items-center space-x-2">
                            @if($status['state'] === 'closed')
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                            @elseif($status['state'] === 'half_open')
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                                </span>
                            @else
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">State:</span>
                            <span class="text-sm font-medium capitalize 
                                @if($status['state'] === 'closed') text-green-600
                                @elseif($status['state'] === 'half_open') text-yellow-600
                                @else text-red-600
                                @endif">
                                {{ str_replace('_', ' ', $status['state']) }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Health Score:</span>
                            <span class="text-sm font-medium">{{ $status['health_score'] }}%</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Failures:</span>
                            <span class="text-sm font-medium">{{ $status['failures'] }}/{{ $status['config']['failure_threshold'] ?? 5 }}</span>
                        </div>
                        
                        @if($status['last_failure'])
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Last Failure:</span>
                                <span class="text-sm font-medium">{{ \Carbon\Carbon::createFromTimestamp($status['last_failure'])->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mt-4 flex space-x-2">
                        @if($status['state'] !== 'closed')
                            <x-filament::button
                                size="sm"
                                color="warning"
                                wire:click="resetServiceBreaker('{{ $service }}')"
                            >
                                Reset
                            </x-filament::button>
                        @endif
                        
                        @if($status['state'] === 'closed')
                            <x-filament::button
                                size="sm"
                                color="danger"
                                outlined
                                wire:click="forceOpenBreaker('{{ $service }}')"
                            >
                                Force Open
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Metrics Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Last Hour Metrics</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Service
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Calls
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Successful
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Success Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach(['calcom', 'retell', 'stripe'] as $service)
                                @php
                                    $metric = $metrics[$service] ?? ['total' => 0, 'success' => 0, 'success_rate' => 100];
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        {{ $services[$service]['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        {{ $metric['total'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        {{ $metric['success'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="h-2 rounded-full
                                                    @if($metric['success_rate'] >= 90) bg-green-500
                                                    @elseif($metric['success_rate'] >= 70) bg-yellow-500
                                                    @else bg-red-500
                                                    @endif"
                                                    style="width: {{ $metric['success_rate'] }}%">
                                                </div>
                                            </div>
                                            <span>{{ $metric['success_rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        {{-- Configuration Details --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Circuit Breaker Configuration</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($services as $service => $status)
                        <div class="border rounded p-4">
                            <h4 class="font-medium mb-2">{{ $status['name'] }}</h4>
                            <dl class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Failure Threshold:</dt>
                                    <dd class="font-mono">{{ $status['config']['failure_threshold'] ?? 5 }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Success Threshold:</dt>
                                    <dd class="font-mono">{{ $status['config']['success_threshold'] ?? 2 }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Timeout:</dt>
                                    <dd class="font-mono">{{ $status['config']['timeout'] ?? 60 }}s</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Half-Open Requests:</dt>
                                    <dd class="font-mono">{{ $status['config']['half_open_requests'] ?? 3 }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>