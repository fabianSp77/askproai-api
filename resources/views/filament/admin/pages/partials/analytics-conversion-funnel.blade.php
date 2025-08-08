<!-- Conversion Funnel Analytics Section -->
<div class="space-y-6">
    @if(!empty($data['stages']))
        <!-- Funnel Visualization -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Conversion Funnel Analysis</h3>
            
            <!-- Funnel Stages -->
            <div class="relative">
                @php
                    $stages = $data['stages'];
                    $conversionRates = $data['conversion_rates'] ?? [];
                    $maxValue = max(array_values($stages));
                    $stageNames = array_keys($stages);
                @endphp
                
                @foreach($stages as $stageName => $stageValue)
                    @php
                        $isFirst = $loop->first;
                        $isLast = $loop->last;
                        $widthPercent = $maxValue > 0 ? ($stageValue / $maxValue) * 100 : 0;
                        $conversionRate = $conversionRates[$stageName] ?? 0;
                        $index = $loop->index;
                    @endphp
                    
                    <div class="relative mb-6" data-stage="{{ $index }}">
                        <!-- Stage Bar -->
                        <div class="relative">
                            <div class="h-12 bg-gradient-to-r from-blue-{{ 600 - ($index * 50) }} to-blue-{{ 700 - ($index * 50) }} rounded-lg shadow-md transition-all duration-300 hover:shadow-lg" style="width: {{ $widthPercent }}%">
                                <div class="absolute inset-0 flex items-center justify-between px-4">
                                    <div class="text-white font-medium">{{ $stageName }}</div>
                                    <div class="text-white text-sm">
                                        <span class="font-bold">{{ number_format($stageValue) }}</span>
                                        @if(!$isFirst)
                                            <span class="ml-2 opacity-90">({{ $conversionRate }}%)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Connecting Arrow -->
                        @if(!$isLast)
                            <div class="absolute top-12 left-1/2 transform -translate-x-1/2 mt-2">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Conversion Rates Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($conversionRates as $stageName => $rate)
                @if($stageName !== 'Total Calls')
                    @php
                        $rateColor = $rate >= 70 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-{{ $rateColor }}-600 dark:text-{{ $rateColor }}-400">{{ $rate }}%</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $stageName }}</div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                                <div class="bg-{{ $rateColor }}-600 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $rate) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Drop-off Analysis -->
        @if(!empty($data['drop_off_analysis']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Drop-off Analysis</h3>
                <div class="space-y-4">
                    @foreach($data['drop_off_analysis'] as $dropOff)
                        @php
                            $dropOffSeverity = $dropOff['drop_off_rate'] >= 30 ? 'high' : ($dropOff['drop_off_rate'] >= 15 ? 'medium' : 'low');
                            $severityColor = $dropOffSeverity === 'high' ? 'red' : ($dropOffSeverity === 'medium' ? 'yellow' : 'green');
                        @endphp
                        <div class="border border-{{ $severityColor }}-200 dark:border-{{ $severityColor }}-800 bg-{{ $severityColor }}-50 dark:bg-{{ $severityColor }}-900/20 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="text-sm font-medium text-{{ $severityColor }}-900 dark:text-{{ $severityColor }}-100">
                                        {{ $dropOff['from_stage'] }} → {{ $dropOff['to_stage'] }}
                                    </h4>
                                    <p class="text-sm text-{{ $severityColor }}-700 dark:text-{{ $severityColor }}-300 mt-1">
                                        Lost {{ number_format($dropOff['drop_off_count']) }} potential conversions
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-{{ $severityColor }}-600 dark:text-{{ $severityColor }}-400">{{ $dropOff['drop_off_rate'] }}%</div>
                                    <div class="text-xs text-{{ $severityColor }}-500 dark:text-{{ $severityColor }}-400">Drop-off Rate</div>
                                </div>
                            </div>
                            
                            <!-- Recommendations based on drop-off stage -->
                            <div class="mt-3 p-3 bg-{{ $severityColor }}-100 dark:bg-{{ $severityColor }}-900/30 rounded">
                                <div class="text-xs font-medium text-{{ $severityColor }}-800 dark:text-{{ $severityColor }}-200 mb-1">💡 Optimization Recommendation:</div>
                                <div class="text-xs text-{{ $severityColor }}-700 dark:text-{{ $severityColor }}-300">
                                    @if(str_contains($dropOff['from_stage'], 'Total Calls'))
                                        Improve call answer rates by optimizing agent availability and reducing wait times.
                                    @elseif(str_contains($dropOff['from_stage'], 'Answered'))
                                        Focus on better qualifying leads and improving initial conversation flow.
                                    @elseif(str_contains($dropOff['from_stage'], 'Requested'))
                                        Streamline appointment scheduling process and reduce booking friction.
                                    @else
                                        Review follow-up processes and appointment confirmation workflows.
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Benchmark Comparison -->
        @if(!empty($data['benchmark_comparison']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Industry Benchmark Comparison</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Your Performance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Industry Benchmark</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Variance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['benchmark_comparison'] as $stage => $comparison)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $stage }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $comparison['actual'] }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $comparison['benchmark'] }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $comparison['variance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $comparison['variance'] > 0 ? '+' : '' }}{{ $comparison['variance'] }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($comparison['performance'] === 'above')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                👍 Above Benchmark
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                👎 Below Benchmark
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Funnel Optimization Insights -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6">
            <h3 class="text-lg font-medium text-purple-900 dark:text-purple-100 mb-4 flex items-center">
                <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                Optimization Insights
            </h3>
            
            @php
                $totalCalls = $stages['Total Calls'] ?? 0;
                $finalConversions = $stages['Appointment Completed'] ?? 0;
                $overallConversionRate = $totalCalls > 0 ? ($finalConversions / $totalCalls) * 100 : 0;
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Overall Efficiency</h4>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ round($overallConversionRate, 1) }}%</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">End-to-end conversion rate</div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Biggest Opportunity</h4>
                    @php
                        $biggestDropOff = collect($data['drop_off_analysis'] ?? [])->sortByDesc('drop_off_count')->first();
                    @endphp
                    @if($biggestDropOff)
                        <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $biggestDropOff['from_stage'] }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($biggestDropOff['drop_off_count']) }} potential conversions lost</div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No significant drop-offs detected</div>
                    @endif
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Revenue Impact</h4>
                    @php
                        $potentialRevenue = ($biggestDropOff['drop_off_count'] ?? 0) * 150; // Assuming €150 average appointment value
                    @endphp
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">€{{ number_format($potentialRevenue) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Potential monthly revenue if optimized</div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <p class="text-lg font-medium">No conversion funnel data available</p>
                <p class="text-sm mt-2">Funnel analysis will appear here once sufficient call data is processed.</p>
            </div>
        </div>
    @endif
</div>
