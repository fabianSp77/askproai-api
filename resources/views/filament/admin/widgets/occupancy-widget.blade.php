<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Auslastung
                </h2>
                @if($trend['direction'] !== 'stable')
                    <div class="flex items-center gap-1 text-sm">
                        @if($trend['direction'] === 'up')
                            <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-success-600" />
                            <span class="text-success-600">+{{ $trend['value'] }}%</span>
                        @else
                            <x-heroicon-o-arrow-trending-down class="w-4 h-4 text-danger-600" />
                            <span class="text-danger-600">-{{ $trend['value'] }}%</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Main Stats --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                {{-- Today --}}
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-20 h-20 transform -rotate-90">
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="6" fill="none"
                                class="text-gray-200 dark:text-gray-700" />
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="6" fill="none"
                                stroke-dasharray="{{ 2 * 3.14159 * 36 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 36 * (1 - $today_occupancy / 100) }}"
                                class="text-primary-600 transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold">{{ $today_occupancy }}%</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Heute</p>
                    <p class="text-xs text-gray-500">
                        {{ $available_slots_today }} von {{ $total_slots_today }} frei
                    </p>
                </div>

                {{-- Week --}}
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-20 h-20 transform -rotate-90">
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="6" fill="none"
                                class="text-gray-200 dark:text-gray-700" />
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="6" fill="none"
                                stroke-dasharray="{{ 2 * 3.14159 * 36 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 36 * (1 - $week_occupancy / 100) }}"
                                class="text-info-600 transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold">{{ $week_occupancy }}%</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Diese Woche</p>
                </div>
            </div>

            {{-- Peak Hours --}}
            @if(count($peak_hours) > 0)
                <div class="mb-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Stoßzeiten
                    </h3>
                    <div class="space-y-2">
                        @foreach($peak_hours as $peak)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ $peak['hour'] }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                        <div class="bg-warning-600 h-2 rounded-full"
                                            style="width: {{ $peak['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $peak['percentage'] }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Staff Utilization --}}
            @if(count($staff_utilization) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mitarbeiterauslastung heute
                    </h3>
                    <div class="space-y-2">
                        @foreach($staff_utilization as $staff)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $staff['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">{{ $staff['appointments'] }} Termine</span>
                                    <span class="text-sm font-medium {{ $staff['rate'] >= 80 ? 'text-danger-600' : ($staff['rate'] >= 60 ? 'text-warning-600' : 'text-success-600') }}">
                                        {{ $staff['rate'] }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget>