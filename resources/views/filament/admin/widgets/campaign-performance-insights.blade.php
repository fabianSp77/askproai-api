<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ static::$heading }}
        </x-slot>

        <div class="space-y-6">
            {{-- Overall Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Campaigns</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalCampaigns }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $activeCampaigns }} active</div>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total Calls Made</div>
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                        {{ number_format($overallMetrics['total_calls']) }}
                    </div>
                    <div class="text-xs text-blue-500 mt-1">Across all campaigns</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-sm text-green-600 dark:text-green-400">Successful Calls</div>
                    <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                        {{ number_format($overallMetrics['successful_calls']) }}
                    </div>
                    <div class="text-xs text-green-500 mt-1">
                        {{ $overallMetrics['total_calls'] > 0 
                            ? round(($overallMetrics['successful_calls'] / $overallMetrics['total_calls']) * 100) . '%' 
                            : '0%' 
                        }} success rate
                    </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="text-sm text-purple-600 dark:text-purple-400">Avg Success Rate</div>
                    <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                        {{ $overallMetrics['average_success_rate'] }}%
                    </div>
                    <div class="text-xs text-purple-500 mt-1">Per campaign</div>
                </div>
            </div>

            {{-- Insights --}}
            @if(count($insights) > 0)
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Insights & Recommendations</h3>
                    @foreach($insights as $insight)
                        <div class="flex items-start gap-3 p-3 rounded-lg 
                            @if($insight['type'] === 'success') bg-green-50 dark:bg-green-900/20
                            @elseif($insight['type'] === 'warning') bg-yellow-50 dark:bg-yellow-900/20
                            @else bg-blue-50 dark:bg-blue-900/20
                            @endif">
                            <x-dynamic-component 
                                :component="$insight['icon']" 
                                class="w-5 h-5 flex-shrink-0
                                    @if($insight['type'] === 'success') text-green-600 dark:text-green-400
                                    @elseif($insight['type'] === 'warning') text-yellow-600 dark:text-yellow-400
                                    @else text-blue-600 dark:text-blue-400
                                    @endif" 
                            />
                            <p class="text-sm 
                                @if($insight['type'] === 'success') text-green-700 dark:text-green-300
                                @elseif($insight['type'] === 'warning') text-yellow-700 dark:text-yellow-300
                                @else text-blue-700 dark:text-blue-300
                                @endif">
                                {{ $insight['message'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Top Performing Campaigns --}}
            @if($topCampaigns->count() > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Performing Campaigns</h3>
                    <div class="space-y-2">
                        @foreach($topCampaigns as $campaign)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium text-sm text-gray-900 dark:text-white">
                                        {{ $campaign['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $campaign['total_calls'] }} calls • {{ $campaign['completion_date'] }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="text-right">
                                        <div class="text-lg font-semibold 
                                            @if($campaign['success_rate'] >= 80) text-green-600 dark:text-green-400
                                            @elseif($campaign['success_rate'] >= 60) text-yellow-600 dark:text-yellow-400
                                            @else text-red-600 dark:text-red-400
                                            @endif">
                                            {{ $campaign['success_rate'] }}%
                                        </div>
                                        <div class="text-xs text-gray-500">success</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recent Activity --}}
            @if($recentActivity->count() > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Campaign Activity</h3>
                    <div class="space-y-2">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium text-sm text-gray-900 dark:text-white">
                                            {{ $activity['name'] }}
                                        </div>
                                        <x-filament::badge 
                                            :color="$activity['status'] === 'running' ? 'primary' : 'success'"
                                            size="sm"
                                        >
                                            {{ ucfirst($activity['status']) }}
                                        </x-filament::badge>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $activity['calls_made'] }}/{{ $activity['total_targets'] }} calls • {{ $activity['last_update'] }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-24">
                                        <div class="relative w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="absolute left-0 top-0 h-full bg-primary-600 rounded-full transition-all duration-500"
                                                 style="width: {{ $activity['progress'] }}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">
                                        {{ $activity['progress'] }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>