<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Form -->
        {{ $this->form }}
        
        <!-- Loading Indicator -->
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="flex items-center space-x-3">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-400">Loading analytics data...</span>
                </div>
            </div>
        @endif
        
        <!-- Real-time KPIs Bar -->
        @if(!empty($realtimeMetrics))
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    Real-time KPIs (Today)
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $realtimeMetrics['calls_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Calls Today</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $realtimeMetrics['conversions_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Conversions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $realtimeMetrics['conversion_rate_today'] ?? 0 }}%</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Conversion Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $realtimeMetrics['avg_sentiment_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Avg Sentiment</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $realtimeMetrics['active_calls'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Active Calls</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $realtimeMetrics['calls_last_hour'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Last Hour</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ $realtimeMetrics['successful_calls_today'] ?? 0 }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Successful</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-gray-600 dark:text-gray-400 flex items-center justify-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            LIVE
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">System Status</div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Main Analytics Content -->
        @if(!empty($analyticsData))
            @if($viewMode === 'overview')
                @include('filament.admin.pages.partials.analytics-overview', ['data' => $analyticsData['overview'] ?? []])
            @endif
            
            @if($viewMode === 'agents')
                @include('filament.admin.pages.partials.analytics-agent-performance', ['data' => $analyticsData['agent_performance'] ?? []])
            @endif
            
            @if($viewMode === 'funnel')
                @include('filament.admin.pages.partials.analytics-conversion-funnel', ['data' => $analyticsData['conversion_funnel'] ?? []])
            @endif
            
            @if($viewMode === 'journey')
                @include('filament.admin.pages.partials.analytics-customer-journey', ['data' => $analyticsData['customer_journey'] ?? []])
            @endif
            
            @if($viewMode === 'predictions')
                @include('filament.admin.pages.partials.analytics-predictive-insights', ['data' => $analyticsData['predictive_insights'] ?? []])
            @endif
        @else
            @if(!$isLoading)
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-lg font-medium">No analytics data available</p>
                        <p class="text-sm mt-2">Adjust your filters or check back later when more call data is available.</p>
                    </div>
                </div>
            @endif
        @endif
        
        <!-- Anomaly Detection Alerts -->
        @if(!empty($analyticsData['anomaly_detection']['anomalies']))
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Anomalies Detected</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            @foreach($analyticsData['anomaly_detection']['anomalies'] as $anomaly)
                                <div class="mb-1">• {{ $anomaly['description'] ?? 'Anomaly detected' }}</div>
                            @endforeach
                        </div>
                        @if(!empty($analyticsData['anomaly_detection']['recommendations']))
                            <div class="mt-3">
                                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Recommendations:</h4>
                                <ul class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                    @foreach($analyticsData['anomaly_detection']['recommendations'] as $recommendation)
                                        <li>• {{ $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Actions Bar -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Last updated: {{ now()->format('Y-m-d H:i:s') }}
            </div>
            <div class="flex space-x-2">
                {{ $this->refreshAction }}
                {{ $this->exportAction }}
                {{ $this->scheduleAction }}
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Auto-refresh real-time metrics every 30 seconds
        setInterval(function() {
            if (document.querySelector('[data-real-time-metrics]')) {
                @this.call('loadAnalytics');
            }
        }, 30000);
        
        // Chart.js initialization for dynamic charts
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts here if needed
        });
    </script>
    @endpush
</x-filament-panels::page>
