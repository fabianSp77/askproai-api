<div class="fi-wi-widget">
    <div class="fi-wi-widget-content bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-lg {{ $overallStatus === 'operational' ? 'bg-green-100 dark:bg-green-900/30' : ($overallStatus === 'degraded' ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30') }}">
                        <x-heroicon-o-server-stack class="w-6 h-6 {{ $overallStatus === 'operational' ? 'text-green-600 dark:text-green-400' : ($overallStatus === 'degraded' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">System Health</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                            Status: 
                            <span class="font-medium {{ $overallStatus === 'operational' ? 'text-green-600 dark:text-green-400' : ($overallStatus === 'degraded' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $overallStatus }}
                            </span>
                        </p>
                    </div>
                </div>
                
                {{-- Performance Metrics --}}
                <div class="flex items-center space-x-6 text-sm">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($performanceMetrics['requests_per_hour'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Requests/hr</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold {{ ($performanceMetrics['error_rate'] ?? 0) < 1 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $performanceMetrics['error_rate'] ?? 0 }}%
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Error Rate</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Services Grid --}}
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($services as $key => $service)
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $service['name'] }}</h3>
                                <div class="flex items-center mt-1 space-x-2">
                                    <div class="w-2 h-2 rounded-full {{ $service['status'] === 'operational' ? 'bg-green-500' : ($service['status'] === 'degraded' ? 'bg-amber-500' : ($service['status'] === 'unknown' ? 'bg-gray-500' : 'bg-red-500')) }}"></div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 capitalize">{{ $service['status'] }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-500">
                                {{ $service['uptime'] }}% uptime
                            </span>
                        </div>

                        {{-- Metrics --}}
                        <div class="space-y-2">
                            @if($service['response_time'] > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Response Time</span>
                                    <span class="font-medium {{ $service['response_time'] < 100 ? 'text-green-600 dark:text-green-400' : ($service['response_time'] < 500 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                        {{ $service['response_time'] }}ms
                                    </span>
                                </div>
                            @endif

                            @if(isset($service['details']))
                                @foreach($service['details'] as $detailKey => $detailValue)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $detailKey) }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $detailValue }}</span>
                                    </div>
                                @endforeach
                            @endif

                            @if(isset($service['error']))
                                <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {{ $service['error'] }}
                                </div>
                            @endif
                        </div>

                        {{-- Last Check --}}
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                Last checked {{ $service['last_check']->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Response Time Chart --}}
        @if(count($performanceMetrics['avg_response_times'] ?? []) > 0)
            <div class="px-6 pb-6">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Average Response Times by Service</h3>
                    <div class="space-y-2">
                        @foreach($performanceMetrics['avg_response_times'] as $metric)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $metric->service }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ round($metric->avg_ms) }}ms</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(($metric->avg_ms / 1000) * 100, 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>