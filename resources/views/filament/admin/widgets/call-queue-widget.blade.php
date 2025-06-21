<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-6">
            {{-- Header with Stats --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Anruf-Übersicht
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                        <p class="text-xs text-gray-500">Anrufe heute</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-success-600">{{ $stats['answered'] }}</p>
                        <p class="text-xs text-gray-500">Beantwortet</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary-600">{{ $stats['answer_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Annahmerate</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-warning-600">{{ $stats['appointments'] }}</p>
                        <p class="text-xs text-gray-500">Termine gebucht</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-info-600">{{ $stats['conversion_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Konversionsrate</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-600">{{ $stats['avg_duration'] }}</p>
                        <p class="text-xs text-gray-500">Ø Dauer</p>
                    </div>
                </div>
            </div>

            {{-- Active Calls --}}
            @if(count($activeCalls) > 0)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Aktive Anrufe ({{ count($activeCalls) }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($activeCalls as $call)
                            <div class="flex items-center justify-between p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $call['customer_name'] }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $call['phone'] }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono">{{ $call['duration'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $call['agent'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recent Calls --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    Letzte Anrufe
                </h3>
                
                @if($recentCalls->isEmpty())
                    <div class="text-center py-8">
                        <x-heroicon-o-phone-x-mark class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500">Noch keine Anrufe heute</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left">
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Kunde</th>
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Telefon</th>
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Filiale</th>
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Dauer</th>
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="pb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Zeit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentCalls as $call)
                                    <tr>
                                        <td class="py-2 text-sm text-gray-900 dark:text-white">
                                            {{ $call['customer_name'] }}
                                        </td>
                                        <td class="py-2 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $call['phone'] }}
                                        </td>
                                        <td class="py-2 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $call['branch'] }}
                                        </td>
                                        <td class="py-2 text-sm font-mono text-gray-600 dark:text-gray-400">
                                            {{ $call['duration'] }}
                                        </td>
                                        <td class="py-2">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-{{ $call['status']['color'] }}-100 text-{{ $call['status']['color'] }}-700 dark:bg-{{ $call['status']['color'] }}-900/20 dark:text-{{ $call['status']['color'] }}-400">
                                                {{ $call['status']['label'] }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-sm text-gray-500">
                                            {{ $call['time'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>