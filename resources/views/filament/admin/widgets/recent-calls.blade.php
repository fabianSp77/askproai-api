<div class="fi-wi-widget">
    <div class="fi-wi-widget-content bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Letzte Anrufe (24h)</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Übersicht der abgeschlossenen Gespräche</p>
                </div>
                
                {{-- Call Stats --}}
                <div class="flex items-center space-x-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $callStats['total_calls'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Gesamt</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $callStats['appointments_booked'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Termine</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold {{ $callStats['conversion_rate'] >= 30 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $callStats['conversion_rate'] }}%
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Konversion</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $callStats['avg_duration'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Ø Dauer</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Calls Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Zeit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telefon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filiale</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dauer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Termin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentCalls as $call)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $call['time'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                {{ $call['phone'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $call['branch'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                {{ $call['duration'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $call['status'] === 'Abgeschlossen' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                    {{ $call['status'] === 'Verpasst' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $call['status'] === 'Fehlgeschlagen' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $call['status'] === 'Abgebrochen' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' : '' }}
                                ">
                                    {{ $call['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($call['appointment_booked'])
                                    <x-heroicon-m-check-circle class="w-5 h-5 text-green-500 mx-auto" />
                                @else
                                    <x-heroicon-m-x-circle class="w-5 h-5 text-gray-300 dark:text-gray-600 mx-auto" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <x-heroicon-o-phone-x-mark class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Keine Anrufe in den letzten 24 Stunden</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer with missed calls alert --}}
        @if($callStats['missed_calls'] > 0)
            <div class="px-6 pb-4">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                    <div class="flex items-center">
                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
                        <span class="text-sm text-red-800 dark:text-red-200">
                            {{ $callStats['missed_calls'] }} verpasste Anrufe in den letzten 24 Stunden
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>