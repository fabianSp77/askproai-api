<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Header with stats -->
            <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Terminübersicht heute
                </h3>
                
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center space-x-2">
                        <span class="flex h-3 w-3 rounded-full bg-info-500"></span>
                        <span>Geplant ({{ $stats['upcoming'] }})</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="flex h-3 w-3 rounded-full bg-primary-500"></span>
                        <span>Läuft ({{ $stats['in_progress'] }})</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="flex h-3 w-3 rounded-full bg-success-500"></span>
                        <span>Abgeschlossen ({{ $stats['completed'] }})</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="flex h-3 w-3 rounded-full bg-danger-500"></span>
                        <span>Abgesagt ({{ $stats['cancelled'] }})</span>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="relative">
                <!-- Current time indicator -->
                @php
                    $currentTimePosition = (($currentHour - 8) * 100) + (($currentMinute / 60) * 100);
                    $maxPosition = (20 - 8) * 100;
                    $currentPosition = min(max($currentTimePosition, 0), $maxPosition);
                @endphp
                
                @if($currentHour >= 8 && $currentHour <= 20)
                    <div class="absolute top-0 z-20 h-full w-0.5 bg-danger-500" 
                         style="left: {{ ($currentPosition / $maxPosition) * 100 }}%">
                        <div class="absolute -top-1 -left-1.5 h-4 w-4 rounded-full bg-danger-500">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-danger-400 opacity-75"></span>
                        </div>
                        <div class="absolute -top-6 -left-8 whitespace-nowrap rounded bg-danger-500 px-2 py-1 text-xs text-white">
                            {{ sprintf('%02d:%02d', $currentHour, $currentMinute) }}
                        </div>
                    </div>
                @endif

                <!-- Timeline grid -->
                <div class="overflow-x-auto">
                    <div class="min-w-[800px]">
                        <!-- Time headers -->
                        <div class="grid grid-cols-13 border-b border-gray-200 pb-2 dark:border-gray-700">
                            @foreach($timeSlots as $slot)
                                <div class="text-center">
                                    <span class="text-xs font-medium {{ $slot['is_current'] ? 'text-danger-600 dark:text-danger-400' : ($slot['is_past'] ? 'text-gray-400' : 'text-gray-600 dark:text-gray-400') }}">
                                        {{ $slot['label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Appointments -->
                        <div class="relative mt-4 space-y-2">
                            @foreach($appointments as $appointment)
                                @php
                                    $startPosition = (($appointment['start_hour'] - 8) * 100) + (($appointment['start_minute'] / 60) * 100);
                                    $width = ($appointment['duration'] / 60) * 100;
                                    $leftPercentage = ($startPosition / $maxPosition) * 100;
                                    $widthPercentage = ($width / $maxPosition) * 100;
                                @endphp
                                
                                <div class="relative h-16">
                                    <div class="absolute top-0 h-full overflow-hidden rounded-lg border border-{{ $appointment['status_color'] }}-200 bg-{{ $appointment['status_color'] }}-50 p-2 shadow-sm transition-all hover:shadow-md hover:z-10 dark:border-{{ $appointment['status_color'] }}-700 dark:bg-{{ $appointment['status_color'] }}-900/20"
                                         style="left: {{ $leftPercentage }}%; width: {{ $widthPercentage }}%">
                                        <div class="flex h-full flex-col justify-between">
                                            <div>
                                                <p class="truncate text-xs font-semibold text-{{ $appointment['status_color'] }}-900 dark:text-{{ $appointment['status_color'] }}-100">
                                                    {{ $appointment['customer_name'] }}
                                                </p>
                                                <p class="truncate text-xs text-{{ $appointment['status_color'] }}-700 dark:text-{{ $appointment['status_color'] }}-300">
                                                    {{ $appointment['service_name'] }}
                                                </p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-{{ $appointment['status_color'] }}-600 dark:text-{{ $appointment['status_color'] }}-400">
                                                    {{ $appointment['start_time'] }} - {{ $appointment['end_time'] }}
                                                </span>
                                                @if($appointment['is_current'])
                                                    <span class="relative flex h-2 w-2">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-{{ $appointment['status_color'] }}-400 opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-{{ $appointment['status_color'] }}-500"></span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($appointments->isEmpty())
                                <div class="flex h-32 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
                                    <div class="text-center">
                                        <x-heroicon-o-calendar-days class="mx-auto h-12 w-12 text-gray-400" />
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            Keine Termine für heute geplant
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary stats -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Termine gesamt</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
                    <p class="text-2xl font-semibold text-success-600 dark:text-success-400">{{ $stats['completion_rate'] }}%</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Abschlussrate</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
                    <p class="text-2xl font-semibold text-info-600 dark:text-info-400">{{ $stats['upcoming'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Noch offen</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
                    <p class="text-2xl font-semibold text-primary-600 dark:text-primary-400">{{ $stats['in_progress'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Läuft gerade</p>
                </div>
            </div>
        </div>
        
        <!-- Loading overlay -->
        <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-900/50">
            <x-filament::loading-indicator class="h-6 w-6" />
        </div>
    </x-filament::section>
</x-filament-widgets::widget>