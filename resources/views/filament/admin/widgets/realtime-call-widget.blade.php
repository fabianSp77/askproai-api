<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Active Calls Section -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Aktive Anrufe
                    </h3>
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                            </span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Live</span>
                        </div>
                    </div>
                </div>

                @if($activeCalls->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($activeCalls as $call)
                            <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                                @if($call['animated'])
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-20 -skew-x-12 animate-pulse"></div>
                                @endif
                                
                                <div class="relative flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-{{ $call['status_color'] }}-100 dark:bg-{{ $call['status_color'] }}-900">
                                            <x-heroicon-o-phone class="h-5 w-5 text-{{ $call['status_color'] }}-600 dark:text-{{ $call['status_color'] }}-400" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $call['customer_name'] }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $call['phone'] }} • {{ $call['agent'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $call['duration'] }}
                                            </span>
                                            @if($call['animated'])
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-{{ $call['status_color'] }}-600 dark:text-{{ $call['status_color'] }}-400">
                                            {{ $call['status'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-800">
                        <x-heroicon-o-phone-x-mark class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Keine aktiven Anrufe
                        </p>
                    </div>
                @endif
            </div>

            <!-- Statistics Row -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Anrufe heute</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalCallsToday }}</p>
                        </div>
                        <x-heroicon-o-phone-arrow-down-left class="h-8 w-8 text-gray-400" />
                    </div>
                </div>
                
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ø Dauer</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ gmdate('i:s', $avgCallDuration) }}
                            </p>
                        </div>
                        <x-heroicon-o-clock class="h-8 w-8 text-gray-400" />
                    </div>
                </div>
                
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Verpasst</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $missedCalls }}</p>
                        </div>
                        <x-heroicon-o-phone-x-mark class="h-8 w-8 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Recent Calls Section -->
            <div>
                <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Letzte Anrufe
                </h3>
                
                @if($recentCalls->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Zeit
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Kunde
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Telefon
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Dauer
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                @foreach($recentCalls as $call)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $call['time'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $call['customer_name'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $call['phone'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $call['duration'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                                            @if($call['appointment_created'])
                                                <span class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-200">
                                                    <x-heroicon-m-check class="mr-1 h-3 w-3" />
                                                    Termin erstellt
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                    Beendet
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Noch keine Anrufe heute
                        </p>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Loading overlay -->
        <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-900/50">
            <x-filament::loading-indicator class="h-8 w-8" />
        </div>
    </x-filament::section>
</x-filament-widgets::widget>