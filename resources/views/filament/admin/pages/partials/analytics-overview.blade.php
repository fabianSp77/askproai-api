<!-- Analytics Overview Section -->
<div class="space-y-6">
    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Calls -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Calls</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ number_format($data['total_calls'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center text-sm">
                        <span class="text-green-600 dark:text-green-400 font-medium">{{ $data['success_rate'] ?? 0 }}%</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-1">success rate</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Conversion Rate</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $data['conversion_rate'] ?? 0 }}%</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center text-sm">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">{{ number_format($data['successful_calls'] ?? 0) }}</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-1">appointments booked</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Duration -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Avg Duration</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $data['avg_call_duration_minutes'] ?? 0 }} min</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center text-sm">
                        <span class="text-purple-600 dark:text-purple-400 font-medium">{{ $data['avg_latency_ms'] ?? 0 }}ms</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-1">avg latency</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Sentiment -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @php
                            $sentiment = $data['avg_sentiment_score'] ?? 0;
                            $color = $sentiment >= 4 ? 'text-green-600' : ($sentiment >= 3 ? 'text-yellow-600' : 'text-red-600');
                        @endphp
                        <svg class="h-6 w-6 {{ $color }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($sentiment >= 4)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @elseif($sentiment >= 3)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @endif
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Avg Sentiment</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $data['avg_sentiment_score'] ?? 0 }}/5.0</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center text-sm">
                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $data['first_time_caller_rate'] ?? 0 }}%</span>
                        <span class="text-gray-500 dark:text-gray-400 ml-1">first-time callers</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Metrics Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer Retention -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Customer Behavior</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Retention Rate</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $data['customer_retention_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">No-show Rate</span>
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $data['no_show_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Reschedule Rate</span>
                    <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">{{ $data['reschedule_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        <!-- Cost Efficiency -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cost Efficiency</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Cost per Conversion</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">€{{ number_format($data['avg_cost_per_conversion'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Efficiency Score</span>
                    <span class="text-sm font-medium {{ ($data['cost_efficiency_score'] ?? 0) >= 70 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ $data['cost_efficiency_score'] ?? 0 }}/100</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $data['cost_efficiency_score'] ?? 0) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Performance Indicators -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Performance Health</h3>
            <div class="space-y-3">
                @php
                    $overallScore = (
                        ($data['success_rate'] ?? 0) * 0.3 +
                        ($data['conversion_rate'] ?? 0) * 0.4 +
                        (($data['avg_sentiment_score'] ?? 0) * 20) * 0.3
                    );
                    $healthColor = $overallScore >= 70 ? 'green' : ($overallScore >= 50 ? 'yellow' : 'red');
                @endphp
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Overall Health Score</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-{{ $healthColor }}-500"></div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ round($overallScore) }}/100</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-{{ $healthColor }}-600 h-2 rounded-full" style="width: {{ min(100, $overallScore) }}%"></div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @if($overallScore >= 70)
                        🟢 Excellent performance across all metrics
                    @elseif($overallScore >= 50)
                        🟡 Good performance with room for improvement
                    @else
                        🔴 Performance needs attention
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Chart Section -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performance Trends</h3>
            <div class="flex space-x-2">
                <button class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Calls</button>
                <button class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Conversions</button>
                <button class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Sentiment</button>
            </div>
        </div>
        
        <!-- Chart Container -->
        <div id="performanceTrendsChart" class="h-64">
            <!-- Chart will be rendered here via JavaScript -->
            <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-sm">Performance trends chart will be displayed here</p>
                </div>
            </div>
        </div>
    </div>
</div>
