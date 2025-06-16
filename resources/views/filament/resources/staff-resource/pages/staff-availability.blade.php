<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-clock class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Arbeitsstunden (2 Wochen)</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['total_working_hours'] }}h</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-calendar-days class="h-8 w-8 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Termine</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['total_appointments'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-chart-bar class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Auslastung</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['utilization_rate'] }}%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-clock class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ø Termindauer</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['average_appointment_duration'] }} Min</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Availability Calendar --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Verfügbarkeitskalender</h3>
            
            <div class="overflow-x-auto">
                <div class="min-w-[800px]">
                    @foreach($availabilityData as $day)
                        <div class="border-b border-gray-200 dark:border-gray-700 py-4 {{ $day['date']->isToday() ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            <div class="flex items-start">
                                {{-- Day Header --}}
                                <div class="w-40 flex-shrink-0 pr-4">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $day['day_name'] }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $day['date']->format('d.m.Y') }}
                                    </p>
                                    @if(!$day['is_working_day'])
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">Kein Arbeitstag</p>
                                    @elseif($day['working_hours'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ Carbon\Carbon::parse($day['working_hours']->start_time)->format('H:i') }} - 
                                            {{ Carbon\Carbon::parse($day['working_hours']->end_time)->format('H:i') }}
                                        </p>
                                    @endif
                                </div>
                                
                                {{-- Time Slots --}}
                                <div class="flex-1">
                                    @if($day['is_working_day'] && count($day['slots']) > 0)
                                        <div class="grid grid-cols-8 md:grid-cols-12 lg:grid-cols-16 gap-1">
                                            @foreach($day['slots'] as $slot)
                                                <div class="relative group">
                                                    <div class="h-8 rounded text-xs flex items-center justify-center cursor-pointer transition-all
                                                        @if($slot['appointment'])
                                                            bg-blue-500 text-white
                                                        @elseif($slot['is_break'])
                                                            bg-gray-300 dark:bg-gray-600
                                                        @elseif($slot['is_past'])
                                                            bg-gray-100 dark:bg-gray-700
                                                        @elseif($slot['is_available'])
                                                            bg-green-100 dark:bg-green-900 hover:bg-green-200 dark:hover:bg-green-800
                                                        @else
                                                            bg-gray-200 dark:bg-gray-600
                                                        @endif
                                                    ">
                                                        {{ $slot['start']->format('H:i') }}
                                                    </div>
                                                    
                                                    {{-- Tooltip --}}
                                                    @if($slot['appointment'])
                                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                                                            <div class="bg-gray-900 text-white text-xs rounded py-2 px-3 whitespace-nowrap">
                                                                <p class="font-semibold">{{ $slot['appointment']->customer->name }}</p>
                                                                @if($slot['appointment']->service)
                                                                    <p>{{ $slot['appointment']->service->name }}</p>
                                                                @endif
                                                                <p>{{ $slot['appointment']->starts_at->format('H:i') }} - {{ $slot['appointment']->ends_at->format('H:i') }}</p>
                                                            </div>
                                                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                                <div class="border-4 border-transparent border-t-gray-900"></div>
                                                            </div>
                                                        </div>
                                                    @elseif($slot['is_break'])
                                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                                                            <div class="bg-gray-900 text-white text-xs rounded py-1 px-2">
                                                                Pause
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-gray-500 dark:text-gray-400 text-sm">
                                            @if(!$day['is_working_day'])
                                                Kein Arbeitstag
                                            @else
                                                Keine Arbeitszeiten definiert
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Legend --}}
            <div class="mt-4 flex flex-wrap gap-4 text-xs">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-100 dark:bg-green-900 rounded mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">Verfügbar</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">Gebucht</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">Pause</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-100 dark:bg-gray-700 rounded mr-2"></div>
                    <span class="text-gray-600 dark:text-gray-400">Vergangen</span>
                </div>
            </div>
        </div>

        {{-- Upcoming Appointments --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Anstehende Termine</h3>
            
            @php
                $upcomingAppointments = collect($availabilityData)
                    ->pluck('appointments')
                    ->flatten()
                    ->filter(fn($apt) => $apt->starts_at >= now())
                    ->sortBy('starts_at')
                    ->take(10);
            @endphp
            
            @if($upcomingAppointments->isNotEmpty())
                <div class="space-y-3">
                    @foreach($upcomingAppointments as $appointment)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $appointment->customer->name }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $appointment->service?->name ?? 'Kein Service' }} • 
                                    {{ $appointment->branch?->name ?? 'Keine Filiale' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $appointment->starts_at->format('d.m.Y') }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    Keine anstehenden Termine in den nächsten 2 Wochen.
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>