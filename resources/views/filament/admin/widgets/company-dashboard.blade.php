<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-office class="w-5 h-5 text-primary-500" />
                <span>Unternehmens-Intelligence Dashboard</span>
            </div>
        </x-slot>
        
        @php
            $data = $this->getDashboardData();
        @endphp
        
        <div class="space-y-6">
            <!-- Overview Stats -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 Übersicht</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                            {{ number_format($data['overview']['total_companies']) }}
                        </div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">
                            Unternehmen
                        </div>
                        <div class="text-xs text-blue-500 dark:text-blue-500 mt-1">
                            {{ $data['overview']['active_companies'] }} aktiv
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                            {{ number_format($data['overview']['total_branches']) }}
                        </div>
                        <div class="text-sm text-purple-600 dark:text-purple-400">
                            Filialen
                        </div>
                        <div class="text-xs text-purple-500 dark:text-purple-500 mt-1">
                            Ø {{ number_format($data['growthMetrics']['avg_branches_per_company'], 1) }} pro Unternehmen
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                            {{ number_format($data['overview']['total_staff']) }}
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-400">
                            Mitarbeiter
                        </div>
                        <div class="text-xs text-green-500 dark:text-green-500 mt-1">
                            Ø {{ number_format($data['growthMetrics']['avg_staff_per_company'], 1) }} pro Unternehmen
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                            {{ number_format($data['overview']['total_services']) }}
                        </div>
                        <div class="text-sm text-yellow-600 dark:text-yellow-400">
                            Services
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                        <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                            {{ number_format($data['overview']['total_appointments']) }}
                        </div>
                        <div class="text-sm text-orange-600 dark:text-orange-400">
                            Termine
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 rounded-lg p-4 border border-pink-200 dark:border-pink-800">
                        <div class="text-2xl font-bold {{ $data['growthMetrics']['company_growth'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $data['growthMetrics']['company_growth'] > 0 ? '+' : '' }}{{ $data['growthMetrics']['company_growth'] }}%
                        </div>
                        <div class="text-sm text-pink-600 dark:text-pink-400">
                            Wachstum
                        </div>
                        <div class="text-xs text-pink-500 dark:text-pink-500 mt-1">
                            vs. letzter Monat
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Companies & Integration Status -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Companies -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🏆 Top Unternehmen</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['topCompanies'] as $company)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br 
                                                @if($company['health_score'] >= 80) from-green-400 to-green-600
                                                @elseif($company['health_score'] >= 60) from-yellow-400 to-yellow-600
                                                @else from-red-400 to-red-600
                                                @endif
                                                flex items-center justify-center text-white font-bold">
                                                {{ $company['health_score'] }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $company['name'] }}
                                                </p>
                                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                    <span>{{ $company['branches'] }} Filialen</span>
                                                    <span>{{ $company['staff'] }} Mitarbeiter</span>
                                                    <span>{{ $company['appointments'] }} Termine</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            €{{ number_format($company['revenue'], 2, ',', '.') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Umsatz
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Integration Status -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🔗 Integration Status</h3>
                    <div class="space-y-4">
                        @foreach($data['integrationStatus'] as $key => $integration)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-{{ $integration['color'] }}-100 dark:bg-{{ $integration['color'] }}-900/20 flex items-center justify-center">
                                            <x-dynamic-component 
                                                :component="'heroicon-o-' . $integration['icon']" 
                                                class="w-5 h-5 text-{{ $integration['color'] }}-600 dark:text-{{ $integration['color'] }}-400" 
                                            />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $integration['connected'] }} von {{ $data['overview']['total_companies'] }} verbunden
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-{{ $integration['color'] }}-600 dark:text-{{ $integration['color'] }}-400">
                                            {{ $integration['percentage'] }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-{{ $integration['color'] }}-600 h-2 rounded-full transition-all duration-500" 
                                         style="width: {{ $integration['percentage'] }}%">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Growth Metrics -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📈 Wachstumsmetriken</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            +{{ $data['growthMetrics']['new_companies_today'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Neue heute</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            +{{ $data['growthMetrics']['new_companies_this_month'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Diesen Monat</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold {{ $data['growthMetrics']['company_growth'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $data['growthMetrics']['company_growth'] > 0 ? '+' : '' }}{{ $data['growthMetrics']['company_growth'] }}%
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Unternehmenswachstum</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold {{ $data['growthMetrics']['appointment_growth'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $data['growthMetrics']['appointment_growth'] > 0 ? '+' : '' }}{{ $data['growthMetrics']['appointment_growth'] }}%
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Terminwachstum</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>