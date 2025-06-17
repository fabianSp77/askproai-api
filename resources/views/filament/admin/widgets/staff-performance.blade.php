<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mitarbeiter-Performance
        </x-slot>
        
        <x-slot name="description">
            Leistungsübersicht für {{ now()->monthName }} {{ now()->year }}
        </x-slot>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Mitarbeiter-Tabelle -->
            <div class="lg:col-span-2">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Mitarbeiter</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Mitarbeiter</th>
                                <th class="text-center py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Termine</th>
                                <th class="text-center py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Abschlussrate</th>
                                <th class="text-center py-2 px-3 font-medium text-gray-700 dark:text-gray-300">No-Show Rate</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Umsatz</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->getStaffPerformance() as $staff)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $staff['name'] }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="font-medium">{{ $staff['total_appointments'] }}</span>
                                            <span class="text-xs text-gray-500">({{ $staff['completed'] }} ✓)</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="flex items-center justify-center">
                                            <div class="w-20">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $staff['completion_rate'] }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-medium">{{ $staff['completion_rate'] }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="text-sm {{ $staff['no_show_rate'] > 10 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                            {{ $staff['no_show_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ number_format($staff['revenue'], 2, ',', '.') }} €
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ⌀ {{ number_format($staff['avg_price'], 2, ',', '.') }} €
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Services -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Beliebte Services</h3>
                <div class="space-y-3">
                    @foreach($this->getTopServices() as $index => $service)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                    <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                        {{ $service['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $service['count'] }} Buchungen
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                    {{ number_format($service['revenue'], 2, ',', '.') }} €
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>