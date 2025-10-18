{{-- Availability Loader Component
    Loads real-time availability from Cal.com API with comprehensive error handling
    Passes it to HourlyCalendar for display

    Accessibility & UX Features:
    - Loading spinners with aria-busy attribute
    - Error handling with retry mechanism
    - Live region announcements
    - Keyboard navigation for week switching

    Props:
    - $weekData: Loaded availability slots
    - $weekMetadata: Week display info
    - $loading: Loading state
    - $error: Error message
    - $serviceDuration: Service duration for context

    Methods:
    - loadAvailability(): Fetch from Cal.com
    - previousWeek(), nextWeek(), goToCurrentWeek(): Navigate weeks
--}}

<div class="booking-section" aria-busy="{{ $loading ? 'true' : 'false' }}">
    {{-- Section Header --}}
    <div class="booking-section-title" id="availability-title">
        ⏰ Verfügbare Termine (Live von Cal.com)
    </div>
    <div class="booking-section-subtitle">
        Echtzeitverfügbarkeit aus Ihrem Kalender
    </div>

    {{-- Live announcements for screen readers --}}
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
        @if($loading)
            Verfügbarkeiten werden geladen...
        @elseif($error)
            Fehler beim Laden der Verfügbarkeiten: {{ $error }}
        @elseif(isset($weekMetadata['start_date']))
            Verfügbarkeiten für {{ $weekMetadata['start_date'] }} bis {{ $weekMetadata['end_date'] }} geladen
        @endif
    </div>

    {{-- Error Alert --}}
    @if($error)
        <div class="booking-alert alert-error mb-4" role="alert" aria-labelledby="error-message">
            <div class="alert-icon" aria-hidden="true">⚠️</div>
            <div class="alert-content">
                <div class="alert-title" id="error-message">Fehler beim Laden der Verfügbarkeiten</div>
                <div class="alert-message">{{ $error }}</div>
                <div class="alert-action">
                    <button
                        wire:click="loadAvailability"
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200 rounded hover:opacity-80 transition-all"
                        aria-label="Verfügbarkeiten erneut versuchen zu laden">
                        🔄 Erneut versuchen
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Calendar Navigation with Loading State --}}
    <div class="calendar-navigation">
        <button
            wire:click="previousWeek"
            type="button"
            class="calendar-nav-button {{ $loading ? 'loading-disabled' : '' }}"
            wire:loading.attr="disabled"
            :disabled="$loading"
            aria-label="Zur vorherigen Woche navigieren"
            aria-describedby="current-week">
            ← Vorherige Woche
            <span class="spinner sm ml-2" wire:loading></span>
        </button>

        <div class="text-center flex-1 py-2"
             id="current-week"
             aria-live="polite"
             aria-atomic="true"
             role="status">
            @if($loading)
                <div class="flex items-center justify-center gap-2">
                    <span class="spinner sm"></span>
                    <span class="text-sm font-medium text-[var(--calendar-text-secondary)] animate-pulse">
                        Termine werden geladen...
                    </span>
                </div>
            @elseif(isset($weekMetadata['start_date']) && isset($weekMetadata['end_date']))
                <div class="text-sm font-medium">
                    {{ $weekMetadata['start_date'] }} - {{ $weekMetadata['end_date'] }}
                </div>
            @endif
        </div>

        <button
            wire:click="nextWeek"
            type="button"
            class="calendar-nav-button {{ $loading ? 'loading-disabled' : '' }}"
            wire:loading.attr="disabled"
            :disabled="$loading"
            aria-label="Zur nächsten Woche navigieren"
            aria-describedby="current-week">
            Nächste Woche →
            <span class="spinner sm ml-2" wire:loading></span>
        </button>
    </div>

    {{-- Rendering HourlyCalendar with loaded availability --}}
    @if($loading && empty($weekData))
        {{-- Loading skeleton state --}}
        <div class="space-y-3" aria-hidden="true">
            <div class="skeleton skeleton-slot"></div>
            <div class="skeleton skeleton-slot"></div>
            <div class="skeleton skeleton-slot"></div>
        </div>
    @else
        @include('livewire.components.hourly-calendar', [
            'weekData' => $weekData,
            'weekMetadata' => $weekMetadata,
            'serviceName' => $serviceName ?? null,
            'serviceDuration' => $serviceDuration,
            'loading' => $loading,
            'error' => $error,
            'selectedSlot' => $selectedSlot ?? null,
        ])
    @endif

    {{-- Cal.com Attribution --}}
    <div class="mt-4 text-xs text-[var(--calendar-text-secondary)] text-center">
        📅 Verfügbarkeiten werden live von Cal.com synchronisiert (60-Sekunden Cache)
    </div>
</div>

{{-- Screen reader utility --}}
<style>
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }
</style>
