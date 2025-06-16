<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chart-pie class="w-5 h-5 text-primary-500" />
                <span>Customer Intelligence Dashboard</span>
            </div>
        </x-slot>
        
        @php
            $insights = $this->getInsights();
        @endphp
        
        <div class="space-y-6">
            <!-- Customer Segments -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Kundensegmente</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($insights['segments'] as $segment)
                        <div class="relative bg-{{ $segment['color'] }}-50 dark:bg-{{ $segment['color'] }}-900/20 rounded-lg p-4 border border-{{ $segment['color'] }}-200 dark:border-{{ $segment['color'] }}-800">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-lg font-semibold text-{{ $segment['color'] }}-900 dark:text-{{ $segment['color'] }}-100">
                                        {{ $segment['label'] }}
                                    </p>
                                    <p class="text-2xl font-bold text-{{ $segment['color'] }}-700 dark:text-{{ $segment['color'] }}-300 mt-1">
                                        {{ number_format($segment['count']) }}
                                    </p>
                                    <p class="text-sm text-{{ $segment['color'] }}-600 dark:text-{{ $segment['color'] }}-400">
                                        {{ $segment['percentage'] }}% • {{ $segment['description'] }}
                                    </p>
                                </div>
                                <div class="text-3xl opacity-20">
                                    {{ substr($segment['label'], 0, 2) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Top Customers & Risk Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Customers -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🏆 Top Kunden</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($insights['topCustomers'] as $customer)
                            <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                            <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                                {{ substr($customer['name'], 0, 2) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $customer['name'] }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $customer['appointments'] }} Termine • Zuletzt {{ $customer['last_seen'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-green-600 dark:text-green-400">
                                            €{{ number_format($customer['revenue'], 2, ',', '.') }}
                                        </p>
                                        <div class="flex gap-1 mt-1">
                                            @foreach($customer['tags'] as $tag)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $tag['color'] }}-100 text-{{ $tag['color'] }}-800 dark:bg-{{ $tag['color'] }}-900 dark:text-{{ $tag['color'] }}-200">
                                                    {{ $tag['label'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Risk Customers -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">⚠️ Risiko-Analyse</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($insights['riskCustomers'] as $customer)
                            <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $customer['name'] }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $customer['no_shows'] }} No-Shows • {{ $customer['cancellations'] }} Absagen
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $customer['risk_level']['color'] }}-100 text-{{ $customer['risk_level']['color'] }}-800 dark:bg-{{ $customer['risk_level']['color'] }}-900 dark:text-{{ $customer['risk_level']['color'] }}-200">
                                        Risiko: {{ $customer['risk_level']['level'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-2 text-green-500" />
                                Keine Risiko-Kunden identifiziert
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            
            <!-- Growth Metrics -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 Wachstumsmetriken</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            +{{ $insights['growthMetrics']['new_today'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Heute neu</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            +{{ $insights['growthMetrics']['new_this_month'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Diesen Monat</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold {{ $insights['growthMetrics']['growth_rate'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $insights['growthMetrics']['growth_rate'] > 0 ? '+' : '' }}{{ $insights['growthMetrics']['growth_rate'] }}%
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Wachstumsrate</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $insights['growthMetrics']['churn_rate'] }}%
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Churn Rate</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            €{{ number_format($insights['growthMetrics']['lifetime_value'], 0) }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Ø Lifetime Value</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>