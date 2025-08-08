<!-- Customer Journey Analytics Section -->
<div class="space-y-6">
    @if(!empty($data['customer_segments']))
        <!-- Customer Engagement Segments -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Customer Engagement Segments</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @php
                    $segments = $data['customer_segments'];
                    $totalCustomers = array_sum($segments);
                @endphp
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($segments['single_touch'] ?? 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Single Touch</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $totalCustomers > 0 ? round(($segments['single_touch'] ?? 0) / $totalCustomers * 100, 1) : 0 }}% of customers
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $totalCustomers > 0 ? min(100, ($segments['single_touch'] ?? 0) / $totalCustomers * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($segments['low_engagement'] ?? 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Low Engagement</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">2-3 interactions</div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $totalCustomers > 0 ? min(100, ($segments['low_engagement'] ?? 0) / $totalCustomers * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($segments['medium_engagement'] ?? 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Medium Engagement</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">4-7 interactions</div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $totalCustomers > 0 ? min(100, ($segments['medium_engagement'] ?? 0) / $totalCustomers * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($segments['high_engagement'] ?? 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">High Engagement</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">8+ interactions</div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $totalCustomers > 0 ? min(100, ($segments['high_engagement'] ?? 0) / $totalCustomers * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Journey Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Avg Interactions</h3>
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $data['avg_interactions_per_customer'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">per customer</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Time to Convert</h3>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $data['avg_time_to_conversion'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">hours average</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Customer LTV</h3>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">€{{ number_format($data['customer_lifetime_value'] ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">lifetime value</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Churn Risk Analysis -->
        @if(!empty($data['churn_risk_indicators']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="h-5 w-5 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    Churn Risk Indicators
                </h3>
                <div class="space-y-3">
                    @foreach($data['churn_risk_indicators'] as $indicator)
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-red-900 dark:text-red-100">{{ $indicator['description'] ?? 'High risk indicator' }}</div>
                                <div class="text-sm text-red-700 dark:text-red-300">{{ $indicator['details'] ?? 'Customer behavior indicates high churn risk' }}</div>
                            </div>
                            <div class="ml-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ $indicator['risk_level'] ?? 'High Risk' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Sentiment Journey Trends -->
        @if(!empty($data['sentiment_journey_trends']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sentiment Journey Analysis</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach(['positive', 'neutral', 'negative'] as $sentiment)
                        @php
                            $count = $data['sentiment_journey_trends'][$sentiment] ?? 0;
                            $color = $sentiment === 'positive' ? 'green' : ($sentiment === 'neutral' ? 'gray' : 'red');
                            $emoji = $sentiment === 'positive' ? '😊' : ($sentiment === 'neutral' ? '😐' : '🙁');
                        @endphp
                        <div class="text-center p-4 border border-{{ $color }}-200 dark:border-{{ $color }}-700 rounded-lg">
                            <div class="text-2xl mb-2">{{ $emoji }}</div>
                            <div class="text-2xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $count }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ $sentiment }} Journeys</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Customer Journey Optimization -->
        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-lg p-6">
            <h3 class="text-lg font-medium text-teal-900 dark:text-teal-100 mb-4 flex items-center">
                <svg class="h-5 w-5 text-teal-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Journey Optimization Insights
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">🎯 Key Findings</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <li>• {{ round((($segments['single_touch'] ?? 0) / max(1, $totalCustomers)) * 100, 1) }}% of customers require only one touchpoint to convert</li>
                        <li>• High-engagement customers show {{ $data['avg_interactions_per_customer'] ?? 0 }}x higher retention rates</li>
                        <li>• Average time to conversion is {{ $data['avg_time_to_conversion'] ?? 0 }} hours</li>
                        <li>• Customer lifetime value averages €{{ number_format($data['customer_lifetime_value'] ?? 0) }}</li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">💡 Actionable Recommendations</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        @if(($segments['single_touch'] ?? 0) > ($segments['high_engagement'] ?? 0))
                            <li>• Focus on first-call resolution to capitalize on single-touch preference</li>
                        @endif
                        @if(($data['avg_time_to_conversion'] ?? 0) > 24)
                            <li>• Implement follow-up automation to reduce time to conversion</li>
                        @endif
                        <li>• Create personalized nurturing sequences for medium-engagement customers</li>
                        <li>• Develop retention programs for high-value customer segments</li>
                    </ul>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-lg font-medium">No customer journey data available</p>
                <p class="text-sm mt-2">Customer journey analytics will appear here once more interaction data is collected.</p>
            </div>
        </div>
    @endif
</div>
