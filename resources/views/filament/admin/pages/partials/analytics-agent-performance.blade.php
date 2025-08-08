<!-- Agent Performance Analytics Section -->
<div class="space-y-6">
    @if(!empty($data['agent_rankings']))
        <!-- Performance Distribution -->
        @if(!empty($data['performance_distribution']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Agent Performance Distribution</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $data['performance_distribution']['excellent'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Excellent (80+)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $data['performance_distribution']['good'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Good (60-79)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $data['performance_distribution']['average'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Average (40-59)</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $data['performance_distribution']['needs_improvement'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Needs Work (<40)</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Top Performer Highlight -->
        @if(!empty($data['top_performer']))
            @php $topAgent = $data['top_performer'] @endphp
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">🏆 Top Performing Agent</h3>
                        <p class="text-2xl font-bold text-green-800 dark:text-green-200 mt-1">{{ $topAgent['agent_name'] }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $topAgent['performance_score'] }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300">Performance Score</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div class="text-center">
                        <div class="text-lg font-semibold text-green-800 dark:text-green-200">{{ $topAgent['conversion_rate'] }}%</div>
                        <div class="text-xs text-green-600 dark:text-green-400">Conversion Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-green-800 dark:text-green-200">{{ $topAgent['avg_sentiment_score'] }}/5</div>
                        <div class="text-xs text-green-600 dark:text-green-400">Avg Sentiment</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-green-800 dark:text-green-200">{{ $topAgent['conversions'] }}</div>
                        <div class="text-xs text-green-600 dark:text-green-400">Total Conversions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-semibold text-green-800 dark:text-green-200">€{{ number_format($topAgent['cost_per_conversion'], 2) }}</div>
                        <div class="text-xs text-green-600 dark:text-green-400">Cost/Conversion</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Agent Performance Rankings -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Agent Performance Scorecards</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Comprehensive performance metrics for all AI agents</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Performance Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Calls</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conversions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conversion Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sentiment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cost/Conv</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Latency</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['agent_rankings'] as $index => $agent)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full {{ $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-400' : ($index === 2 ? 'bg-amber-600' : 'bg-blue-500')) }} flex items-center justify-center text-white text-sm font-bold">
                                                {{ $index + 1 }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $agent['agent_name'] }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ substr($agent['agent_id'], 0, 8) }}...</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @php
                                            $score = $agent['performance_score'];
                                            $color = $score >= 80 ? 'green' : ($score >= 60 ? 'blue' : ($score >= 40 ? 'yellow' : 'red'));
                                        @endphp
                                        <div class="text-sm font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $score }}</div>
                                        <div class="ml-2 w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-{{ $color }}-600 h-2 rounded-full" style="width: {{ min(100, $score) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ number_format($agent['total_calls']) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">{{ $agent['conversions'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $agent['conversion_rate'] >= 30 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($agent['conversion_rate'] >= 15 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                        {{ $agent['conversion_rate'] }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $agent['avg_duration_minutes'] }} min</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @php
                                            $sentiment = $agent['avg_sentiment_score'];
                                            $sentimentEmoji = $sentiment >= 4 ? '😊' : ($sentiment >= 3 ? '😐' : '🙁');
                                        @endphp
                                        <span class="mr-2">{{ $sentimentEmoji }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $sentiment }}/5</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">€{{ number_format($agent['cost_per_conversion'], 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm {{ $agent['avg_latency_ms'] <= 500 ? 'text-green-600 dark:text-green-400' : ($agent['avg_latency_ms'] <= 1000 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">{{ $agent['avg_latency_ms'] }}ms</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Improvement Opportunities -->
        @if(!empty($data['improvement_opportunities']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    Improvement Opportunities
                </h3>
                <div class="space-y-4">
                    @foreach($data['improvement_opportunities'] as $opportunity)
                        <div class="border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ $opportunity['agent_name'] }}</h4>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        <strong>{{ $opportunity['issue'] }}:</strong> {{ $opportunity['current_value'] }}
                                    </p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
                                        💡 <em>{{ $opportunity['recommendation'] }}</em>
                                    </p>
                                </div>
                                <div class="ml-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Action Required
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <p class="text-lg font-medium">No agent performance data available</p>
                <p class="text-sm mt-2">Agent performance metrics will appear here once call data is processed.</p>
            </div>
        </div>
    @endif
</div>
