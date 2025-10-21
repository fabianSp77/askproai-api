<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-4">
            {{-- Calendar Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="navigateDate('prev')"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <x-heroicon-s-chevron-left class="w-5 h-5" />
                    </button>
                    <button
                        wire:click="goToToday"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                    >
                        Heute
                    </button>
                    <button
                        wire:click="navigateDate('next')"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <x-heroicon-s-chevron-right class="w-5 h-5" />
                    </button>
                </div>

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($selectedDate)->locale('de')->isoFormat('MMMM YYYY') }}
                </h2>

                <div class="flex gap-1">
                    <button
                        wire:click="switchView('day')"
                        @class([
                            'px-3 py-1 text-sm font-medium rounded-md',
                            'bg-primary-500 text-white' => $viewMode === 'day',
                            'text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600' => $viewMode !== 'day',
                        ])
                    >
                        Tag
                    </button>
                    <button
                        wire:click="switchView('week')"
                        @class([
                            'px-3 py-1 text-sm font-medium rounded-md',
                            'bg-primary-500 text-white' => $viewMode === 'week',
                            'text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600' => $viewMode !== 'week',
                        ])
                    >
                        Woche
                    </button>
                    <button
                        wire:click="switchView('month')"
                        @class([
                            'px-3 py-1 text-sm font-medium rounded-md',
                            'bg-primary-500 text-white' => $viewMode === 'month',
                            'text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600' => $viewMode !== 'month',
                        ])
                    >
                        Monat
                    </button>
                </div>
            </div>

            {{-- Calendar View --}}
            @if($viewMode === 'week')
                {{-- Week View --}}
                <div class="overflow-x-auto">
                    <div class="min-w-[800px]">
                        {{-- Days Header --}}
                        <div class="grid grid-cols-8 gap-px bg-gray-200 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 p-2 text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                Zeit
                            </div>
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $date = \Carbon\Carbon::parse($selectedDate)->startOfWeek()->addDays($i);
                                @endphp
                                <div class="bg-gray-50 dark:bg-gray-800 p-2 text-center">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $date->locale('de')->dayName }}
                                    </div>
                                    <div @class([
                                        'text-sm font-medium',
                                        'text-primary-600 dark:text-primary-400' => $date->isToday(),
                                        'text-gray-900 dark:text-white' => !$date->isToday(),
                                    ])>
                                        {{ $date->format('d.m.') }}
                                    </div>
                                </div>
                            @endfor
                        </div>

                        {{-- Time Slots --}}
                        <div class="grid grid-cols-8 gap-px bg-gray-200 dark:bg-gray-700">
                            @foreach($timeSlots as $time)
                                <div class="bg-gray-50 dark:bg-gray-800 p-2 text-center text-xs text-gray-500 dark:text-gray-400">
                                    {{ $time }}
                                </div>
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $currentDate = \Carbon\Carbon::parse($selectedDate)->startOfWeek()->addDays($i);
                                        $slotAppointments = $appointments->filter(function($apt) use ($currentDate, $time) {
                                            $aptDate = \Carbon\Carbon::parse($apt['start']);
                                            $slotTime = \Carbon\Carbon::parse($currentDate->format('Y-m-d') . ' ' . $time);
                                            return $aptDate->format('Y-m-d') === $currentDate->format('Y-m-d')
                                                && $aptDate->format('H:i') <= $time
                                                && \Carbon\Carbon::parse($apt['end'])->format('H:i') > $time;
                                        });
                                    @endphp
                                    <div class="bg-white dark:bg-gray-900 p-1 min-h-[50px] relative">
                                        @foreach($slotAppointments as $apt)
                                            <div
                                                class="text-xs p-1 rounded mb-1 cursor-pointer hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $apt['color'] }}20; border-left: 3px solid {{ $apt['color'] }};"
                                                wire:click="$emit('openModal', 'appointment-details', {{ json_encode(['appointmentId' => $apt['id']]) }})"
                                                title="{{ $apt['customer_name'] }} - {{ $apt['service_name'] }}"
                                            >
                                                <div class="font-medium truncate">{{ $apt['customer_name'] }}</div>
                                                <div class="text-gray-600 dark:text-gray-400 truncate">{{ $apt['service_name'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endfor
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif($viewMode === 'day')
                {{-- Day View --}}
                <div class="space-y-2">
                    @forelse($appointments->where('start', '>=', $selectedDate . ' 00:00:00')->where('start', '<=', $selectedDate . ' 23:59:59') as $apt)
                        <div
                            class="p-3 rounded-lg border cursor-pointer hover:shadow-md transition-shadow"
                            style="border-color: {{ $apt['color'] }}; background-color: {{ $apt['color'] }}10;"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($apt['start'])->format('H:i') }} - {{ \Carbon\Carbon::parse($apt['end'])->format('H:i') }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $apt['customer_name'] }} - {{ $apt['service_name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        Mitarbeiter: {{ $apt['staff_name'] }}
                                    </div>
                                </div>
                                <div class="text-sm font-medium">
                                    €{{ number_format($apt['price'], 2) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Keine Termine für diesen Tag
                        </div>
                    @endforelse
                </div>
            @else
                {{-- Month View (Simplified) --}}
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Monatsansicht wird geladen...
                </div>
            @endif

            {{-- Legend --}}
            <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Ausstehend</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Bestätigt</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">In Bearbeitung</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-gray-500"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Abgeschlossen</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Storniert</span>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>