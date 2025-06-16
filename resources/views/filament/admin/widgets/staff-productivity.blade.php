<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user-group class="w-5 h-5 text-primary-500" />
                <span>Mitarbeiter-Produktivität & Performance</span>
            </div>
        </x-slot>
        
        @php
            $data = $this->getProductivityData();
        @endphp
        
        <div class="space-y-6">
            <!-- Overview Stats -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 Übersicht</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                            {{ number_format($data['overview']['total_staff']) }}
                        </div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">
                            Mitarbeiter
                        </div>
                        <div class="text-xs text-blue-500 dark:text-blue-500 mt-1">
                            {{ $data['overview']['active_staff'] }} aktiv
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                            {{ number_format($data['overview']['bookable_staff']) }}
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-400">
                            Buchbar
                        </div>
                        <div class="text-xs text-green-500 dark:text-green-500 mt-1">
                            online verfügbar
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                            {{ number_format($data['overview']['avg_appointments_per_staff'], 1) }}
                        </div>
                        <div class="text-sm text-purple-600 dark:text-purple-400">
                            Ø Termine
                        </div>
                        <div class="text-xs text-purple-500 dark:text-purple-500 mt-1">
                            pro Mitarbeiter
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                        <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                            {{ number_format($data['overview']['total_appointments_today']) }}
                        </div>
                        <div class="text-sm text-orange-600 dark:text-orange-400">
                            Termine heute
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 rounded-lg p-4 border border-pink-200 dark:border-pink-800 col-span-2">
                        <div class="text-2xl font-bold text-pink-700 dark:text-pink-300">
                            {{ $data['overview']['utilization_rate'] }}%
                        </div>
                        <div class="text-sm text-pink-600 dark:text-pink-400">
                            Auslastungsrate
                        </div>
                        <div class="w-full bg-pink-200 dark:bg-pink-800 rounded-full h-1.5 mt-2">
                            <div class="bg-pink-600 h-1.5 rounded-full" style="width: {{ $data['overview']['utilization_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Performers -->
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🏆 Top Performer des Monats</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Mitarbeiter
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Produktivität
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Termine
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Kunden
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Abschluss
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Bewertung
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Umsatz
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['topPerformers'] as $index => $performer)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($index === 0)
                                                <div class="text-2xl">🥇</div>
                                            @elseif($index === 1)
                                                <div class="text-2xl">🥈</div>
                                            @elseif($index === 2)
                                                <div class="text-2xl">🥉</div>
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm font-medium">
                                                    {{ $index + 1 }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $performer['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $performer['branch'] }} • {{ $performer['services'] }} Services
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                            @if($performer['productivity_score'] >= 80) bg-gradient-to-br from-green-400 to-green-600
                                            @elseif($performer['productivity_score'] >= 60) bg-gradient-to-br from-yellow-400 to-yellow-600
                                            @else bg-gradient-to-br from-red-400 to-red-600
                                            @endif text-white font-bold text-sm">
                                            {{ $performer['productivity_score'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($performer['appointments']) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($performer['unique_customers']) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-semibold 
                                            @if($performer['completion_rate'] >= 90) text-green-600 dark:text-green-400
                                            @elseif($performer['completion_rate'] >= 70) text-yellow-600 dark:text-yellow-400
                                            @else text-red-600 dark:text-red-400
                                            @endif">
                                            {{ $performer['completion_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($performer['avg_rating'])
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                                                    {{ $performer['avg_rating'] }}
                                                </span>
                                                <x-heroicon-m-star class="w-4 h-4 text-yellow-500" />
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            €{{ number_format($performer['revenue'], 2, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Workload & Availability -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Workload Distribution -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📈 Arbeitsbelastung</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        @foreach($data['workload'] as $staff)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $staff['staff'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $staff['branch'] }} • Ø {{ $staff['avg_duration'] }} Min/Termin
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $staff['workload_level']['color'] }}-100 text-{{ $staff['workload_level']['color'] }}-800 dark:bg-{{ $staff['workload_level']['color'] }}-900 dark:text-{{ $staff['workload_level']['color'] }}-200">
                                        {{ $staff['workload_level']['level'] }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div class="text-center">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $staff['today'] }}</p>
                                        <p class="text-gray-500 dark:text-gray-400">Heute</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $staff['week'] }}</p>
                                        <p class="text-gray-500 dark:text-gray-400">Diese Woche</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $staff['month'] }}</p>
                                        <p class="text-gray-500 dark:text-gray-400">Diesen Monat</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Availability & Skills -->
                <div class="space-y-6">
                    <!-- Current Availability -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🟢 Aktuelle Verfügbarkeit</h3>
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                                        {{ $data['availability']['available_now'] }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        von {{ $data['availability']['total_bookable'] }} Mitarbeitern verfügbar
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ $data['availability']['availability_rate'] }}%
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Verfügbarkeitsrate
                                    </p>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                @foreach($data['availability']['by_day'] as $day)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $day['day'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $day['percentage'] }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 w-10 text-right">
                                                {{ $day['available'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Skills Coverage -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">🎯 Service-Abdeckung</h3>
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="space-y-3">
                                @foreach($data['skills'] as $skill)
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $skill['service'] }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $skill['staff_count'] }} Mitarbeiter ({{ $skill['coverage'] }}%)
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                                 style="width: {{ $skill['coverage'] }}%">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>