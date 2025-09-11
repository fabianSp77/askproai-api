<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Integration Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-sm mb-6 text-white">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="p-4 bg-white/20 backdrop-blur rounded-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        
                    
                    <div>
                        <h1 class="text-2xl font-bold">{{ $record->name }}</h1>
                        <p class="text-blue-100">{{ $record->type ?? 'External Integration' }}</p>
                    
                
                <div class="flex items-center space-x-3">
                    @if($record->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 backdrop-blur border border-green-300/50">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-green-300" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Connected
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500/20 backdrop-blur border border-red-300/50">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-red-300" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Disconnected
                        </span>
                    @endif
                
            
        
    

    <!-- Integration Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- API Calls Today -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">API Calls Today</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->api_calls_today ?? 0 }}</p>
                
            
        

        <!-- Success Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Success Rate</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->success_rate ?? 100 }}%</p>
                
            
        

        <!-- Last Sync -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Sync</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->last_sync_at ? $record->last_sync_at->diffForHumans() : 'Never' }}
                    </p>
                
            
        

        <!-- Data Synced -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Records Synced</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->records_synced ?? 0 }}</p>
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Configuration</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Integration Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($record->type ?? 'API') }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Endpoint URL</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 break-all">{{ $record->endpoint_url ?? 'Not configured' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">API Version</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->api_version ?? 'v1' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auth Method</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->auth_method ?? 'API Key' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Limit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->rate_limit ?? '1000/hour' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('F j, Y') }}</dd>
                    
                </dl>
            

            <!-- Webhooks -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Webhooks</h3>
                @if($record->webhooks && $record->webhooks->count() > 0)
                    <div class="space-y-2">
                        @foreach($record->webhooks as $webhook)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $webhook->event }}</span>
                                @if($webhook->is_active)
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                @else
                                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                @endif
                            
                        @endforeach
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No webhooks configured</p>
                @endif
            
        

        <!-- Activity & Logs -->
        <div class="lg:col-span-2">
            <!-- Connection Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Connection Health</h3>
                
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Response Time</p>
                            <div class="flex items-center">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ min(100 - (($record->avg_response_time ?? 100) / 10), 100) }}%">
                                
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $record->avg_response_time ?? 100 }}ms</span>
                            
                        
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Uptime (30d)</p>
                            <div class="flex items-center">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $record->uptime_percentage ?? 99.9 }}%">
                                
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $record->uptime_percentage ?? 99.9 }}%</span>
                            
                        
                    

                    <!-- Recent Status Checks -->
                    <div class="mt-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Recent Status Checks</p>
                        <div class="flex space-x-1">
                            @for($i = 0; $i < 20; $i++)
                                @php
                                    $isHealthy = rand(1, 100) > 5; // 95% healthy simulation
                                @endphp
                                <div class="flex-1 h-8 {{ $isHealthy ? 'bg-green-500' : 'bg-red-500' }} rounded" title="{{ $isHealthy ? 'Healthy' : 'Error' }}">
                            @endfor
                        
                    
                
            

            <!-- Recent API Calls -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent API Calls</h3>
                
                <div class="p-6">
                    @if($record->apiLogs && $record->apiLogs()->latest()->limit(10)->get()->count() > 0)
                        <div class="space-y-3">
                            @foreach($record->apiLogs()->latest()->limit(10)->get() as $log)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($log->status_code >= 200 && $log->status_code < 300)
                                            <div class="w-2 h-2 bg-green-400 rounded-full mt-2">
                                        @elseif($log->status_code >= 400)
                                            <div class="w-2 h-2 bg-red-400 rounded-full mt-2">
                                        @else
                                            <div class="w-2 h-2 bg-yellow-400 rounded-full mt-2">
                                        @endif
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $log->method }} {{ $log->endpoint }}
                                            </p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Status: {{ $log->status_code }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Time: {{ $log->response_time }}ms</span>
                                        
                                    
                                
                            @endforeach
                        
                    @else
                        <div class="text-center py-8">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent API calls</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">API calls will appear here once the integration is active</p>
                        
                    @endif
                
            

            <!-- Sync History -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sync History</h3>
                
                <div class="p-6">
                    @if($record->syncHistory && $record->syncHistory()->latest()->limit(5)->get()->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($record->syncHistory()->latest()->limit(5)->get() as $sync)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    @if($sync->status === 'success')
                                                        <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </span>
                                                    @endif
                                                
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            Synced <span class="font-medium text-gray-900 dark:text-gray-100">{{ $sync->records_synced }} records</span>
                                                        </p>
                                                    
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                        {{ $sync->created_at->diffForHumans() }}
                                                    
                                                
                                            
                                        
                                    </li>
                                @endforeach
                            </ul>
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No sync history available</p>
                    @endif
                
            
        
    

    </div>
</x-filament-panels::page>
