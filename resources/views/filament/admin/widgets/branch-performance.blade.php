<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-500" />
                <span>Filial-Performance Dashboard</span>
            </div>
        </x-slot>
        
        @php
            $data = $this->getPerformanceData();
        @endphp
        
        <div class="space-y-6">
            <!-- Overview Metrics -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 Übersicht</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                        <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                            {{ number_format($data['overview']['total_branches']) }}
                        </div>
                        <div class="text-sm text-indigo-600 dark:text-indigo-400">
                            Filialen gesamt
                        </div>
                        <div class="text-xs text-indigo-500 dark:text-indigo-500 mt-1">
                            {{ $data['overview']['active_branches'] }} aktiv
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                            {{ number_format($data['overview']['avg_staff_per_branch'], 1) }}
                        </div>
                        <div class="text-sm text-purple-600 dark:text-purple-400">
                            Ø Mitarbeiter
                        </div>
                        <div class="text-xs text-purple-500 dark:text-purple-500 mt-1">
                            pro Filiale
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                            {{ number_format($data['overview']['avg_services_per_branch'], 1) }}
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-400">
                            Ø Services
                        </div>
                        <div class="text-xs text-green-500 dark:text-green-500 mt-1">
                            pro Filiale
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                        <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                            {{ number_format($data['overview']['total_appointments_today']) }}
                        </div>
                        <div class="text-sm text-orange-600 dark:text-orange-400">
                            Termine heute
                        </div>
                        <div class="text-xs text-orange-500 dark:text-orange-500 mt-1">
                            alle Filialen
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800 col-span-2">
                        <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                            €{{ number_format($data['overview']['total_revenue_this_month'], 2, ',', '.') }}
                        </div>
                        <div class="text-sm text-emerald-600 dark:text-emerald-400">
                            Umsatz diesen Monat
                        </div>
                        <div class="text-xs text-emerald-500 dark:text-emerald-500 mt-1">
                            alle Filialen kombiniert
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Performing Branches -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🏆 Top Performing Filialen</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Filiale
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Performance
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Termine
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Kunden
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Abschlussrate
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Umsatz
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['topBranches'] as $index => $branch)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $branch['name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $branch['company'] }} • {{ $branch['staff_count'] }} Mitarbeiter • {{ $branch['service_count'] }} Services
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full
                                            @if($branch['performance_score'] >= 80) bg-gradient-to-br from-green-400 to-green-600
                                            @elseif($branch['performance_score'] >= 60) bg-gradient-to-br from-yellow-400 to-yellow-600
                                            @elseif($branch['performance_score'] >= 40) bg-gradient-to-br from-orange-400 to-orange-600
                                            @else bg-gradient-to-br from-red-400 to-red-600
                                            @endif text-white font-bold text-lg">
                                            {{ $branch['performance_score'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($branch['appointments']) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($branch['unique_customers']) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center">
                                            <span class="text-sm font-semibold 
                                                @if($branch['completion_rate'] >= 90) text-green-600 dark:text-green-400
                                                @elseif($branch['completion_rate'] >= 70) text-yellow-600 dark:text-yellow-400
                                                @else text-red-600 dark:text-red-400
                                                @endif">
                                                {{ $branch['completion_rate'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            €{{ number_format($branch['revenue'], 2, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Utilization & Growth -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Utilization Metrics -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">⏰ Auslastung</h3>
                    <div class="space-y-3">
                        @foreach($data['utilization'] as $util)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $util['branch'] }}
                                    </span>
                                    <span class="text-sm font-semibold text-{{ $util['color'] }}-600 dark:text-{{ $util['color'] }}-400">
                                        {{ $util['utilization_rate'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-{{ $util['color'] }}-600 h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $util['utilization_rate'] }}%">
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $util['booked_slots'] }} gebucht</span>
                                    <span>{{ $util['available_slots'] }} verfügbar</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Growth Comparison -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📈 Wachstumsvergleich</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['comparison'] as $comp)
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $comp['branch'] }}
                                        </p>
                                        <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span>Diesen Monat: {{ $comp['this_month'] }}</span>
                                            <span>Letzten Monat: {{ $comp['last_month'] }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($comp['trend'] === 'up')
                                            <x-heroicon-m-arrow-trending-up class="w-5 h-5 text-green-500" />
                                        @elseif($comp['trend'] === 'down')
                                            <x-heroicon-m-arrow-trending-down class="w-5 h-5 text-red-500" />
                                        @else
                                            <x-heroicon-m-minus class="w-5 h-5 text-gray-500" />
                                        @endif
                                        <span class="text-lg font-semibold 
                                            @if($comp['growth'] > 0) text-green-600 dark:text-green-400
                                            @elseif($comp['growth'] < 0) text-red-600 dark:text-red-400
                                            @else text-gray-600 dark:text-gray-400
                                            @endif">
                                            {{ $comp['growth'] > 0 ? '+' : '' }}{{ $comp['growth'] }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>