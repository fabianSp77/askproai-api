{{-- Booking Summary Component
    Displays and confirms booking details

    Props:
    - $branchName, $serviceName, $staffName, $selectedSlotLabel
    - $serviceDuration
    - $isComplete

    Methods:
    - confirmBooking(): Confirm the booking
    - editSection(section): Edit a specific section
--}}

<div class="booking-section">
    <div class="booking-section-title">
        📋 Buchungsübersicht
    </div>
    <div class="booking-section-subtitle">
        Überprüfen Sie Ihre Auswahl vor der Bestätigung
    </div>

    {{-- Summary Card --}}
    <div class="booking-summary">

        {{-- Branch Summary --}}
        <div class="summary-item">
            <div class="flex-1">
                <div class="summary-label">🏢 Filiale</div>
                <div class="summary-value">
                    {{ $branchName ?? '—' }}
                </div>
            </div>
            @if(!empty($branchName))
                <button wire:click="editSection('branch')" type="button"
                        class="text-xs text-[var(--calendar-primary)] hover:underline ml-2">
                    Ändern
                </button>
            @endif
        </div>

        {{-- Service Summary --}}
        <div class="summary-item">
            <div class="flex-1">
                <div class="summary-label">🎯 Service</div>
                <div class="summary-value">
                    {{ $serviceName ?? '—' }}
                    @if(!empty($serviceName))
                        <span class="text-xs text-[var(--calendar-text-secondary)]">
                            ({{ $serviceDuration }} Min)
                        </span>
                    @endif
                </div>
            </div>
            @if(!empty($serviceName))
                <button wire:click="editSection('service')" type="button"
                        class="text-xs text-[var(--calendar-primary)] hover:underline ml-2">
                    Ändern
                </button>
            @endif
        </div>

        {{-- Staff Summary --}}
        <div class="summary-item">
            <div class="flex-1">
                <div class="summary-label">👥 Mitarbeiter</div>
                <div class="summary-value">
                    {{ $staffName ?? '—' }}
                </div>
            </div>
            @if(!empty($staffName))
                <button wire:click="editSection('staff')" type="button"
                        class="text-xs text-[var(--calendar-primary)] hover:underline ml-2">
                    Ändern
                </button>
            @endif
        </div>

        {{-- Time Summary --}}
        <div class="summary-item">
            <div class="flex-1">
                <div class="summary-label">⏰ Termin</div>
                <div class="summary-value">
                    {{ $selectedSlotLabel ?? '—' }}
                </div>
            </div>
            @if(!empty($selectedSlotLabel))
                <button wire:click="editSection('time')" type="button"
                        class="text-xs text-[var(--calendar-primary)] hover:underline ml-2">
                    Ändern
                </button>
            @endif
        </div>
    </div>

    {{-- Status Indicators --}}
    <div class="space-y-2 mt-4">
        @php
            $checks = [
                ['label' => 'Filiale ausgewählt', 'done' => !empty($branchName)],
                ['label' => 'Service ausgewählt', 'done' => !empty($serviceName)],
                ['label' => 'Mitarbeiter ausgewählt', 'done' => !empty($staffName)],
                ['label' => 'Termin ausgewählt', 'done' => !empty($selectedSlot)],
            ];
        @endphp

        @foreach($checks as $check)
            <div class="flex items-center gap-2 text-sm">
                @if($check['done'])
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                    <span class="text-[var(--calendar-text)]">{{ $check['label'] }}</span>
                @else
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    <span class="text-[var(--calendar-text-secondary)]">{{ $check['label'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Action Buttons --}}
    <div class="mt-6 space-y-2">
        @if($isComplete)
            <button wire:click="confirmBooking()"
                    type="button"
                    class="w-full px-4 py-3 rounded-lg bg-[var(--calendar-available)] text-white
                           font-semibold hover:opacity-90 transition-opacity
                           flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
                Buchung bestätigen
            </button>
            <div class="booking-alert alert-success">
                ✅ Bereit zur Bestätigung
            </div>
        @else
            <button disabled
                    type="button"
                    class="w-full px-4 py-3 rounded-lg bg-gray-300 dark:bg-gray-600
                           text-gray-500 dark:text-gray-400 font-semibold cursor-not-allowed
                           opacity-60">
                Buchung unvollständig
            </button>
            <div class="booking-alert alert-info">
                ℹ️ Bitte füllen Sie alle Felder aus
            </div>
        @endif
    </div>
</div>
