<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Meine Termine heute
                </h2>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-500">
                        {{ $completedCount }}/{{ $totalCount }} erledigt
                    </span>
                    <span class="text-primary-600 font-medium">
                        {{ $upcomingCount }} ausstehend
                    </span>
                </div>
            </div>

            {{-- Current/Next Appointment Highlight --}}
            @if($currentAppointment)
                <div class="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="w-6 h-6 text-primary-600" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-primary-900 dark:text-primary-100">
                                @if($currentAppointment->starts_at->isFuture())
                                    Nächster Termin in {{ $currentAppointment->starts_at->diffForHumans() }}
                                @else
                                    Aktueller Termin
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-primary-700 dark:text-primary-300">
                                {{ $currentAppointment->starts_at->format('H:i') }} - {{ $currentAppointment->ends_at->format('H:i') }}
                                | {{ $currentAppointment->customer->first_name }} {{ $currentAppointment->customer->last_name }}
                                @if($currentAppointment->service)
                                    | {{ $currentAppointment->service->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Appointments List --}}
            @if($appointments->count() > 0)
                <div class="space-y-2">
                    @foreach($appointments as $appointment)
                        <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            {{-- Time --}}
                            <div class="flex-shrink-0 text-center">
                                <p class="text-sm font-semibold">{{ $appointment->starts_at->format('H:i') }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->duration }} Min</p>
                            </div>

                            {{-- Status Indicator --}}
                            <div class="flex-shrink-0">
                                @if($appointment->status === 'completed')
                                    <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                                @elseif($appointment->status === 'confirmed')
                                    <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                                @elseif($appointment->status === 'no_show')
                                    <div class="w-3 h-3 bg-danger-500 rounded-full"></div>
                                @else
                                    <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                @endif
                            </div>

                            {{-- Customer Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $appointment->customer->first_name }} {{ $appointment->customer->last_name }}
                                </p>
                                <p class="text-xs text-gray-500 truncate">
                                    @if($appointment->service)
                                        {{ $appointment->service->name }}
                                    @endif
                                    @if($appointment->branch)
                                        • {{ $appointment->branch->name }}
                                    @endif
                                </p>
                            </div>

                            {{-- Price --}}
                            @if($appointment->price)
                                <div class="flex-shrink-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($appointment->price, 2, ',', '.') }} €
                                    </p>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="flex-shrink-0 flex items-center gap-1">
                                @if($appointment->customer->phone)
                                    <x-filament::icon-button
                                        icon="heroicon-o-phone"
                                        size="sm"
                                        color="gray"
                                        href="tel:{{ $appointment->customer->phone }}"
                                    />
                                @endif
                                <x-filament::icon-button
                                    icon="heroicon-o-eye"
                                    size="sm"
                                    color="gray"
                                    :url="route('filament.admin.resources.appointments.view', $appointment)"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-8">
                    <x-heroicon-o-calendar class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        Keine Termine heute
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Genieße deinen freien Tag!
                    </p>
                </div>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget>