<div class="space-y-6">
    <!-- Alerts Section -->
    @if(!empty($alerts))
        <div class="grid gap-4">
            @foreach($alerts as $alert)
                <div class="p-4 rounded-lg border-l-4 
                    @if($alert['type'] === 'error') border-red-500 bg-red-50 @endif
                    @if($alert['type'] === 'warning') border-yellow-500 bg-yellow-50 @endif
                    @if($alert['type'] === 'info') border-blue-500 bg-blue-50 @endif
                ">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($alert['type'] === 'error')
                                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                            @elseif($alert['type'] === 'warning')
                                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                            @else
                                <x-heroicon-s-information-circle class="h-5 w-5 text-blue-400" />
                            @endif
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium 
                                @if($alert['type'] === 'error') text-red-800 @endif
                                @if($alert['type'] === 'warning') text-yellow-800 @endif
                                @if($alert['type'] === 'info') text-blue-800 @endif
                            ">
                                {{ $alert['title'] }}
                            </h3>
                            <div class="mt-2 text-sm 
                                @if($alert['type'] === 'error') text-red-700 @endif
                                @if($alert['type'] === 'warning') text-yellow-700 @endif
                                @if($alert['type'] === 'info') text-blue-700 @endif
                            ">
                                {{ $alert['message'] }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Deployment Status -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($deploymentStatus['current'] && $deploymentStatus['current']['status'] === 'success')
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-400" />
                        @elseif($deploymentStatus['current'] && $deploymentStatus['current']['status'] === 'failed')
                            <x-heroicon-o-x-circle class="h-6 w-6 text-red-400" />
                        @elseif($deploymentStatus['in_progress'])
                            <x-heroicon-o-clock class="h-6 w-6 text-yellow-400 animate-spin" />
                        @else
                            <x-heroicon-o-question-mark-circle class="h-6 w-6 text-gray-400" />
                        @endif
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Deployment Status
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                @if($deploymentStatus['current'])
                                    {{ ucfirst($deploymentStatus['current']['status']) }}
                                @else
                                    No Deployment
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    @if($deploymentStatus['current'])
                        <span class="text-gray-500">ID:</span>
                        <span class="font-medium text-gray-900">{{ $deploymentStatus['current']['id'] }}</span>
                    @else
                        <span class="text-gray-500">Ready for deployment</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($systemHealth['overall_status'] === 'healthy')
                            <x-heroicon-o-heart class="h-6 w-6 text-green-400" />
                        @elseif($systemHealth['overall_status'] === 'unhealthy')
                            <x-heroicon-o-heart class="h-6 w-6 text-red-400" />
                        @else
                            <x-heroicon-o-heart class="h-6 w-6 text-yellow-400" />
                        @endif
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                System Health
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ ucfirst($systemHealth['overall_status']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    @if(isset($systemHealth['response_time']))
                        <span class="text-gray-500">Response:</span>
                        <span class="font-medium text-gray-900">{{ $systemHealth['response_time'] }}ms</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Feature Flags -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-flag class="h-6 w-6 text-blue-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Feature Flags
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $featureFlags['enabled'] }}/{{ $featureFlags['total'] }} Active
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    @if($featureFlags['partial_rollout'] > 0)
                        <span class="text-yellow-600">{{ $featureFlags['partial_rollout'] }} partial rollout</span>
                    @else
                        <span class="text-gray-500">All flags fully rolled out</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Performance -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-chart-bar class="h-6 w-6 text-purple-400" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Performance
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $performanceMetrics['response_time'] }}ms avg
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-gray-500">Memory:</span>
                    <span class="font-medium text-gray-900">{{ $performanceMetrics['memory_usage']['percentage'] }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- System Health Details -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    System Health Details
                </h3>
                @if(isset($systemHealth['checks']))
                    <div class="space-y-3">
                        @foreach($systemHealth['checks'] as $check)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    @if($check['status'] === 'ok')
                                        <x-heroicon-s-check-circle class="h-4 w-4 text-green-400 mr-2" />
                                    @elseif($check['status'] === 'warning')
                                        <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-yellow-400 mr-2" />
                                    @else
                                        <x-heroicon-s-x-circle class="h-4 w-4 text-red-400 mr-2" />
                                    @endif
                                    <span class="text-sm font-medium">{{ $check['name'] }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $check['duration'] ?? 0 }}ms
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif(isset($systemHealth['error']))
                    <div class="text-red-600 text-sm">
                        Error: {{ $systemHealth['error'] }}
                    </div>
                @else
                    <div class="text-gray-500 text-sm">
                        No health check data available
                    </div>
                @endif
            </div>
        </div>

        <!-- Feature Flags Details -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Active Feature Flags
                </h3>
                @if(isset($featureFlags['flags']) && !empty($featureFlags['flags']))
                    <div class="space-y-3">
                        @foreach($featureFlags['flags'] as $flag)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    @if($flag['enabled'])
                                        @if($flag['rollout_percentage'] == 100)
                                            <div class="h-2 w-2 bg-green-400 rounded-full mr-3"></div>
                                        @else
                                            <div class="h-2 w-2 bg-yellow-400 rounded-full mr-3"></div>
                                        @endif
                                    @else
                                        <div class="h-2 w-2 bg-gray-400 rounded-full mr-3"></div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium">{{ $flag['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $flag['key'] }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm">{{ $flag['rollout_percentage'] }}%</div>
                                    <div class="text-xs text-gray-500">{{ $flag['evaluations_last_hour'] }} evals</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-gray-500 text-sm">
                        No feature flags configured
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Performance Metrics
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['response_time'] }}</div>
                    <div class="text-sm text-gray-500">Response Time (ms)</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['memory_usage']['percentage'] }}%</div>
                    <div class="text-sm text-gray-500">Memory Usage</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['cpu_usage'] }}%</div>
                    <div class="text-sm text-gray-500">CPU Usage</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['database_connections'] }}</div>
                    <div class="text-sm text-gray-500">DB Connections</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['queue_size'] }}</div>
                    <div class="text-sm text-gray-500">Queue Size</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $performanceMetrics['cache_hit_rate'] }}%</div>
                    <div class="text-sm text-gray-500">Cache Hit Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Deployments -->
    @if(!empty($recentDeployments))
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Recent Deployments
                </h3>
                <div class="space-y-3">
                    @foreach($recentDeployments as $deployment)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div class="flex items-center">
                                @if($deployment['status'] === 'success')
                                    <x-heroicon-s-check-circle class="h-5 w-5 text-green-400 mr-3" />
                                @elseif($deployment['status'] === 'failed')
                                    <x-heroicon-s-x-circle class="h-5 w-5 text-red-400 mr-3" />
                                @elseif($deployment['status'] === 'in_progress')
                                    <x-heroicon-s-clock class="h-5 w-5 text-yellow-400 mr-3 animate-spin" />
                                @else
                                    <x-heroicon-s-question-mark-circle class="h-5 w-5 text-gray-400 mr-3" />
                                @endif
                                <div>
                                    <div class="text-sm font-medium">{{ $deployment['id'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::createFromTimestamp($deployment['timestamp'])->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm capitalize">{{ $deployment['status'] }}</div>
                                @if($deployment['duration'])
                                    <div class="text-xs text-gray-500">{{ $deployment['duration'] }}s</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Auto-refresh script -->
    <script>
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 30000);
    </script>
</div>