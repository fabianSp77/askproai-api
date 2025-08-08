<!-- Predictive Analytics Section -->
<div class="space-y-6">
    <!-- Forecasting Overview -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-medium text-indigo-900 dark:text-indigo-100 flex items-center">
                    <svg class="h-6 w-6 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Predictive Analytics & Forecasting
                </h3>
                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-1">AI-powered predictions based on historical patterns and trends</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">CONFIDENCE LEVEL</div>
                <div class="text-2xl font-bold text-indigo-800 dark:text-indigo-200">85%</div>
            </div>
        </div>
    </div>

    <!-- Call Volume Forecast -->
    @if(!empty($data['call_volume_forecast']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4" />
                </svg>
                Call Volume Forecast (Next 7 Days)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <!-- Forecast Chart Placeholder -->
                    <div id="callVolumeForecastChart" class="h-48">
                        <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-sm">Call volume forecast chart</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($data['call_volume_forecast'] ?? [] as $index => $forecast)
                        <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ $forecast['date'] ?? 'Day ' . ($index + 1) }}</div>
                                <div class="text-xs text-blue-700 dark:text-blue-300">{{ $forecast['day_of_week'] ?? '' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $forecast['predicted_calls'] ?? 0 }}</div>
                                <div class="text-xs text-blue-500 dark:text-blue-400">±{{ $forecast['confidence_interval'] ?? 0 }}</div>
                            </div>
                        </div>
                    @endforeach
                    @if(empty($data['call_volume_forecast']))
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <p class="text-sm">Generating call volume predictions...</p>
                            <p class="text-xs mt-1">Requires at least 14 days of historical data</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Conversion Trends Prediction -->
    @if(!empty($data['conversion_trends']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                Conversion Rate Trends
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['next_week', 'next_month', 'next_quarter'] as $period)
                    @php
                        $trend = $data['conversion_trends'][$period] ?? [];
                        $trendDirection = ($trend['predicted_rate'] ?? 0) - ($trend['current_rate'] ?? 0);
                        $trendColor = $trendDirection > 0 ? 'green' : ($trendDirection < 0 ? 'red' : 'gray');
                        $trendIcon = $trendDirection > 0 ? '↗️' : ($trendDirection < 0 ? '↘️' : '➡️');
                    @endphp
                    <div class="border border-{{ $trendColor }}-200 dark:border-{{ $trendColor }}-700 bg-{{ $trendColor }}-50 dark:bg-{{ $trendColor }}-900/20 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl mb-2">{{ $trendIcon }}</div>
                            <div class="text-lg font-bold text-{{ $trendColor }}-600 dark:text-{{ $trendColor }}-400">{{ $trend['predicted_rate'] ?? 0 }}%</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $period) }}</div>
                            <div class="text-xs text-{{ $trendColor }}-500 dark:text-{{ $trendColor }}-400 mt-1">
                                {{ $trendDirection > 0 ? '+' : '' }}{{ round($trendDirection, 1) }}% change
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Peak Times Prediction -->
    @if(!empty($data['peak_times_prediction']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-orange-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Peak Times Prediction
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Predicted Daily Peak Hours</h4>
                    <div class="space-y-2">
                        @foreach($data['peak_times_prediction']['daily_peaks'] ?? [] as $day => $peaks)
                            <div class="flex justify-between items-center p-2 bg-orange-50 dark:bg-orange-900/20 rounded">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $day }}</span>
                                <span class="text-sm font-medium text-orange-600 dark:text-orange-400">{{ implode(', ', $peaks) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Expected Call Volume</h4>
                    @foreach($data['peak_times_prediction']['volume_forecast'] ?? [] as $timeSlot)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $timeSlot['time_range'] ?? '' }}</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-orange-600 h-2 rounded-full" style="width: {{ min(100, ($timeSlot['volume_percentage'] ?? 0)) }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $timeSlot['expected_calls'] ?? 0 }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Agent Workload Forecast -->
    @if(!empty($data['agent_workload_forecast']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Agent Workload Forecast
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Load</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Predicted Load</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Utilization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['agent_workload_forecast'] ?? [] as $agent)
                            @php
                                $utilization = $agent['predicted_utilization'] ?? 0;
                                $statusColor = $utilization >= 90 ? 'red' : ($utilization >= 70 ? 'yellow' : 'green');
                                $status = $utilization >= 90 ? 'Overloaded' : ($utilization >= 70 ? 'Busy' : 'Available');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $agent['agent_name'] ?? 'Unknown Agent' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $agent['current_calls_per_hour'] ?? 0 }} calls/hour
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $agent['predicted_calls_per_hour'] ?? 0 }} calls/hour
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                            <div class="bg-{{ $statusColor }}-600 h-2 rounded-full" style="width: {{ min(100, $utilization) }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $utilization }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-200">
                                        {{ $status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Revenue Projections -->
    @if(!empty($data['revenue_projections']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                Revenue Projections
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['monthly', 'quarterly', 'yearly'] as $period)
                    @php
                        $projection = $data['revenue_projections'][$period] ?? [];
                        $confidence = $projection['confidence_level'] ?? 0;
                        $confidenceColor = $confidence >= 80 ? 'green' : ($confidence >= 60 ? 'yellow' : 'red');
                    @endphp
                    <div class="border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase mb-1">{{ $period }}</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">€{{ number_format($projection['projected_revenue'] ?? 0) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $projection['growth_rate'] ?? 0 > 0 ? '+' : '' }}{{ $projection['growth_rate'] ?? 0 }}% growth
                            </div>
                            <div class="mt-2">
                                <div class="text-xs text-{{ $confidenceColor }}-600 dark:text-{{ $confidenceColor }}-400 font-medium">{{ $confidence }}% confidence</div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1 mt-1">
                                    <div class="bg-{{ $confidenceColor }}-600 h-1 rounded-full" style="width: {{ $confidence }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Seasonal Patterns -->
    @if(!empty($data['seasonal_patterns']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Seasonal Patterns & Trends
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Monthly Trends</h4>
                    <div class="space-y-2">
                        @foreach($data['seasonal_patterns']['monthly_trends'] ?? [] as $month => $trend)
                            @php
                                $trendValue = $trend['percentage_change'] ?? 0;
                                $trendColor = $trendValue > 0 ? 'green' : ($trendValue < 0 ? 'red' : 'gray');
                            @endphp
                            <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $month }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-{{ $trendColor }}-600 dark:text-{{ $trendColor }}-400">
                                        {{ $trendValue > 0 ? '+' : '' }}{{ $trendValue }}%
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $trend['expected_calls'] ?? 0 }} calls</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Key Insights</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        @foreach($data['seasonal_patterns']['insights'] ?? [] as $insight)
                            <li class="flex items-start">
                                <svg class="h-4 w-4 text-indigo-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $insight }}
                            </li>
                        @endforeach
                        @if(empty($data['seasonal_patterns']['insights']))
                            <li class="text-gray-500 dark:text-gray-400">Seasonal insights will appear as more data is collected over time.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Predictive Actions Panel -->
    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-cyan-900 dark:text-cyan-100 mb-4 flex items-center">
            <svg class="h-5 w-5 text-cyan-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            AI-Recommended Actions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">🎯 Immediate Actions (Next 7 Days)</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Scale agent capacity during predicted peak hours (2-4 PM)</li>
                    <li>• Implement proactive follow-up for high-value prospects</li>
                    <li>• Optimize conversation scripts for low-performing agents</li>
                </ul>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">📈 Strategic Actions (Next 30 Days)</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Develop retention campaigns for high-churn risk segments</li>
                    <li>• Launch seasonal promotions based on historical patterns</li>
                    <li>• Invest in agent training for sentiment improvement</li>
                </ul>
            </div>
        </div>
    </div>
</div>
