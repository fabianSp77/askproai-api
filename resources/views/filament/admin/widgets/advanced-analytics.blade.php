<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center space-x-2">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Advanced Analytics Overview</span>
            </div>
        </x-slot>
        
        <div class="space-y-6">
            <!-- Real-time KPIs Bar -->
            @if(!empty($this->getViewData()['realtime_kpis']))
                @php $kpis = $this->getViewData()['realtime_kpis'] @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $kpis['calls_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Calls Today</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $kpis['conversions_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Conversions</div>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $kpis['conversion_rate_today'] ?? 0 }}%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Conv. Rate</div>
                    </div>
                    <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $kpis['avg_sentiment_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Sentiment</div>
                    </div>
                    <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $kpis['active_calls'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Active</div>
                    </div>
                    <div class="text-center p-3 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
                        <div class="text-lg font-bold text-teal-600 dark:text-teal-400">{{ $kpis['calls_last_hour'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Last Hour</div>
                    </div>
                </div>
            @endif
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Performance Overview -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Performance Trends (30 Days)</h3>
                    @if(!empty($this->getViewData()['trend_indicators']))
                        @php $trends = $this->getViewData()['trend_indicators'] @endphp
                        <div class="grid grid-cols-2 gap-3">
                            @foreach(['calls_trend' => 'Calls', 'conversions_trend' => 'Conversions', 'sentiment_trend' => 'Sentiment', 'efficiency_trend' => 'Efficiency'] as $key => $label)
                                @php 
                                    $trend = $trends[$key] ?? ['direction' => 'neutral', 'percentage' => 0];
                                    $color = $trend['direction'] === 'up' ? 'green' : ($trend['direction'] === 'down' ? 'red' : 'gray');
                                    $icon = $trend['direction'] === 'up' ? '↗' : ($trend['direction'] === 'down' ? '↘' : '→');
                                @endphp
                                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</div>
                                        <div class="text-xs font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400 flex items-center">
                                            <span class="mr-1">{{ $icon }}</span>
                                            {{ abs($trend['percentage']) }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <!-- Quick Insights -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">AI Insights</h3>
                    @if(!empty($this->getViewData()['quick_insights']))
                        <div class="space-y-3">
                            @foreach($this->getViewData()['quick_insights'] as $insight)
                                @php
                                    $bgColor = $insight['type'] === 'success' ? 'green' : ($insight['type'] === 'warning' ? 'yellow' : 'blue');
                                @endphp
                                <div class="p-3 bg-{{ $bgColor }}-50 dark:bg-{{ $bgColor }}-900/20 border border-{{ $bgColor }}-200 dark:border-{{ $bgColor }}-800 rounded-lg">
                                    <div class="flex items-start space-x-2">
                                        <span class="text-lg">{{ $insight['icon'] }}</span>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-{{ $bgColor }}-900 dark:text-{{ $bgColor }}-100">{{ $insight['title'] }}</div>
                                            <div class="text-xs text-{{ $bgColor }}-700 dark:text-{{ $bgColor }}-300 mt-1">{{ $insight['message'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Top Performers & Conversion Funnel -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Agent Performers -->
                @if(!empty($this->getViewData()['agent_performance']))
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                            <svg class="h-4 w-4 text-yellow-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Top AI Agents
                        </h3>
                        <div class="space-y-2">
                            @foreach($this->getViewData()['agent_performance'] as $index => $agent)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-400' : 'bg-amber-600') }} flex items-center justify-center text-white text-xs font-bold">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $agent['agent_name'] ?? 'Unknown' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Score: {{ $agent['performance_score'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-green-600 dark:text-green-400">{{ $agent['conversion_rate'] ?? 0 }}%</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $agent['conversions'] ?? 0 }} conv.</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Conversion Funnel Summary -->
                @if(!empty($this->getViewData()['conversion_funnel']))
                    @php $funnel = $this->getViewData()['conversion_funnel'] @endphp
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                            <svg class="h-4 w-4 text-blue-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Conversion Funnel
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-sm text-gray-900 dark:text-white">Total Calls</span>
                                <span class="text-sm font-medium">{{ number_format($funnel['total_calls'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-sm text-gray-900 dark:text-white">Appointments</span>
                                <span class="text-sm font-medium">{{ number_format($funnel['appointments_completed'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <span class="text-sm text-gray-900 dark:text-white">Overall Rate</span>
                                <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ $funnel['overall_conversion'] ?? 0 }}%</span>
                            </div>
                            @if(!empty($funnel['biggest_dropoff']))
                                @php $dropoff = $funnel['biggest_dropoff'] @endphp
                                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                    <div class="text-xs font-medium text-red-800 dark:text-red-200">⚠️ Biggest Drop-off</div>
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                        {{ $dropoff['from_stage'] }} → {{ $dropoff['to_stage'] }}: {{ $dropoff['drop_off_rate'] }}%
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Anomalies Alert -->
            @if(!empty($this->getViewData()['anomalies']))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-yellow-400 mr-2 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Anomalies Detected</h4>
                            <div class="mt-2">
                                @foreach(array_slice($this->getViewData()['anomalies'], 0, 2) as $anomaly)
                                    <div class="text-xs text-yellow-700 dark:text-yellow-300 mb-1">
                                        • {{ $anomaly['description'] ?? 'Performance anomaly detected' }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Quick Actions -->
            <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Last updated: {{ now()->format('H:i') }} • Auto-refresh: 30s
                </div>
                <div class="flex space-x-2">
                    <a href="{{ \App\Filament\Admin\Pages\AdvancedCallAnalytics::getUrl() }}" 
                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800">
                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        View Full Analytics
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
