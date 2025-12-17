{{--
    Cancellation Banner Component
    Displays prominent warning when a call has cancelled appointments
    Shows: cancellation details, link to cancellation call, policy status
--}}

@if ($show ?? false)
<div class="w-full rounded-lg border-2 border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 p-4 mb-4">
    <div class="flex items-start gap-3">
        {{-- Warning Icon --}}
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        {{-- Content --}}
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                @if (($banner_type ?? null) === 'booking_call')
                    {{-- This call's appointments were cancelled --}}
                    <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200">
                        {{ $count === 1 ? 'Termin dieses Anrufs wurde storniert' : $count . ' Termine dieses Anrufs wurden storniert' }}
                    </h3>
                @elseif (($banner_type ?? null) === 'cancellation_call')
                    {{-- This call performed cancellations --}}
                    <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200">
                        {{ $count === 1 ? 'In diesem Anruf wurde ein Termin storniert' : 'In diesem Anruf wurden ' . $count . ' Termine storniert' }}
                    </h3>
                @else
                    {{-- Fallback --}}
                    <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200">
                        {{ $count === 1 ? 'Termin wurde storniert' : $count . ' Termine wurden storniert' }}
                    </h3>
                @endif
            </div>

            {{-- Appointments List --}}
            @foreach ($appointments as $index => $appointment)
                @if ($index > 0)
                    <div class="my-3 border-t border-orange-200 dark:border-orange-700"></div>
                @endif

                <div class="space-y-2 text-sm">
                    {{-- Service & Appointment Time --}}
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium text-orange-900 dark:text-orange-100">
                                {{ $appointment['service_name'] }}
                            </span>
                        </div>
                        <div class="text-orange-700 dark:text-orange-300">
                            @if (($banner_type ?? null) === 'cancellation_call')
                                war geplant für: {{ $appointment['appointment_time'] }}
                            @else
                                geplant für: {{ $appointment['appointment_time'] }}
                            @endif
                        </div>
                    </div>

                    {{-- Cancellation Details --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-orange-700 dark:text-orange-300">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Storniert am: <strong>{{ $appointment['cancelled_at'] }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Storniert von: <strong>{{ $appointment['cancelled_by'] }}</strong></span>
                        </div>
                    </div>

                    {{-- Reason --}}
                    @if ($appointment['reason'])
                        <div class="flex items-start gap-2 text-orange-700 dark:text-orange-300">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <span>Grund: <strong>{{ $appointment['reason'] }}</strong></span>
                        </div>
                    @endif

                    {{-- Policy Status & Fee --}}
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        @if ($appointment['within_policy'])
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium">Innerhalb Richtlinien</span>
                                @if ($appointment['hours_notice'])
                                    <span class="text-xs">({{ number_format($appointment['hours_notice'], 1) }}h Vorlauf)</span>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-medium">Außerhalb Richtlinien</span>
                            </div>
                        @endif

                        @if ($appointment['fee'] > 0)
                            <div class="flex items-center gap-2 text-orange-700 dark:text-orange-300 font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Stornogebühr: {{ number_format($appointment['fee'], 2) }} €</span>
                            </div>
                        @endif
                    </div>

                    {{-- Related Calls --}}
                    <div class="flex flex-wrap gap-2 mt-3 pt-2 border-t border-orange-200 dark:border-orange-700">
                        @if ($appointment['booking_call_id'])
                            <a href="{{ \App\Filament\Resources\CallResource::getUrl('view', ['record' => $appointment['booking_call_id']]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-200 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Buchungsanruf #{{ $appointment['booking_call_id'] }}
                            </a>
                        @endif

                        @if ($appointment['cancellation_call_id'])
                            <a href="{{ \App\Filament\Resources\CallResource::getUrl('view', ['record' => $appointment['cancellation_call_id']]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-800 dark:text-orange-200 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Stornierungsanruf #{{ $appointment['cancellation_call_id'] }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
