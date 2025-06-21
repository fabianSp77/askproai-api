<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            @if($nextAppointment)
                {{-- Has appointment --}}
                <div class="text-center">
                    {{-- Timer/Status --}}
                    <div class="mb-4">
                        @if($isNow)
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-success-100 text-success-700 rounded-full">
                                <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium">Läuft gerade</span>
                            </div>
                        @else
                            <p class="text-3xl font-bold text-primary-600">{{ $timeUntil }}</p>
                            <p class="text-sm text-gray-500 mt-1">bis zum nächsten Termin</p>
                        @endif
                    </div>

                    {{-- Appointment Details --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-center gap-2 text-gray-900 dark:text-white">
                            <x-heroicon-o-clock class="w-5 h-5 text-gray-400" />
                            <span class="font-medium">
                                {{ $nextAppointment->starts_at->format('H:i') }} - {{ $nextAppointment->ends_at->format('H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <x-heroicon-o-user class="w-5 h-5 text-gray-400" />
                            <span class="text-sm">
                                {{ $nextAppointment->customer->first_name }} {{ $nextAppointment->customer->last_name }}
                            </span>
                        </div>

                        @if($nextAppointment->service)
                            <div class="flex items-center justify-center gap-2">
                                <x-heroicon-o-briefcase class="w-5 h-5 text-gray-400" />
                                <span class="text-sm">{{ $nextAppointment->service->name }}</span>
                            </div>
                        @endif

                        @if($nextAppointment->branch)
                            <div class="flex items-center justify-center gap-2">
                                <x-heroicon-o-building-office class="w-5 h-5 text-gray-400" />
                                <span class="text-sm">{{ $nextAppointment->branch->name }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center justify-center gap-2">
                        @if($nextAppointment->customer->phone)
                            <x-filament::button
                                size="sm"
                                color="gray"
                                icon="heroicon-o-phone"
                                :href="'tel:' . $nextAppointment->customer->phone"
                            >
                                Anrufen
                            </x-filament::button>
                        @endif
                        <x-filament::button
                            size="sm"
                            icon="heroicon-o-eye"
                            :url="'/admin/appointments/' . $nextAppointment->id"
                        >
                            Details
                        </x-filament::button>
                    </div>
                </div>
            @else
                {{-- No appointments --}}
                <div class="text-center py-4">
                    <x-heroicon-o-calendar-days class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        Keine weiteren Termine
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Dein Kalender ist frei
                    </p>
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget>