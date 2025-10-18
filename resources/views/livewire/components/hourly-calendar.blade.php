{{-- Hourly Calendar Component
    Displays a professional hourly time grid for appointment booking
    Supports both desktop (grid) and mobile (accordion) views

    Props Required:
    - $weekData: Array of availability by day
    - $weekMetadata: Week display info
    - $serviceName: Service being booked
    - $serviceDuration: Duration in minutes
    - $loading: Loading state
    - $error: Error message (if any)
    - $selectedSlot: Currently selected slot datetime

    Methods:
    - selectSlot(datetime, label): Livewire method to select slot
    - isSlotSelected(datetime): Livewire method to check if selected
    - getDayLabel(dayKey): Livewire method for day abbreviation
    - previousWeek(): Livewire method
    - nextWeek(): Livewire method
--}}

<div class="booking-section">
    {{-- Section Header --}}
    <div class="booking-section-title">
        ⏰ Verfügbare Termine
        @if($serviceName)
            <span class="booking-section-subtitle">
                {{ $serviceName }} • {{ $serviceDuration }} Min
            </span>
        @endif
    </div>

    {{-- Week Navigation --}}
    <div class="calendar-navigation">
        <button
            wire:click="previousWeek"
            type="button"
            class="calendar-nav-button"
            wire:loading.attr="disabled"
            aria-label="Vorherige Woche anzeigen">
            ← Vorherige Woche
        </button>

        <div class="text-center flex-1 py-2"
             aria-live="polite"
             aria-atomic="true"
             role="status">
            @if(isset($weekMetadata['start_date']) && isset($weekMetadata['end_date']))
                <div class="text-sm font-medium">{{ $weekMetadata['start_date'] }} - {{ $weekMetadata['end_date'] }}</div>
            @endif
        </div>

        <button
            wire:click="nextWeek"
            type="button"
            class="calendar-nav-button"
            wire:loading.attr="disabled"
            aria-label="Nächste Woche anzeigen">
            Nächste Woche →
        </button>
    </div>

    {{-- Live announcements for screen readers --}}
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
        @if($loading)
            Verfügbarkeiten werden geladen
        @elseif($error)
            Fehler beim Laden der Verfügbarkeiten: {{ $error }}
        @elseif($selectedSlot)
            Termin ausgewählt: {{ $selectedSlotLabel ?? $selectedSlot }}
        @endif
    </div>

    {{-- Loading State --}}
    @if($loading)
        <div class="empty-state" aria-hidden="true">
            <div class="spinner lg mb-3"></div>
            <div class="text-sm text-[var(--calendar-text-secondary)]">Lade Verfügbarkeiten...</div>
        </div>
    @endif

    {{-- Error State --}}
    @if($error)
        <div class="booking-alert alert-error mb-4" role="alert" aria-labelledby="calendar-error">
            <div class="alert-icon" aria-hidden="true">⚠️</div>
            <div class="alert-content">
                <div class="alert-title" id="calendar-error">Fehler beim Laden der Verfügbarkeiten</div>
                <div class="alert-message">{{ $error }}</div>
            </div>
        </div>
    @endif

    {{-- Calendar Content --}}
    @if(!$loading && !$error)
        @php
            $weekData = $this->weekData;
            $totalSlots = collect($weekData)->flatten(1)->count();
        @endphp

        @if($totalSlots === 0)
            {{-- No Availability State --}}
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <div class="empty-state-title">Keine verfügbaren Termine</div>
                <div class="empty-state-text">
                    Für diesen Service wurden keine Termine gefunden. Bitte versuchen Sie es später erneut oder wählen Sie einen anderen Service.
                </div>
            </div>
        @else
            {{-- DESKTOP: Hourly Grid (7 columns x time rows) --}}
            <div class="hourly-calendar hidden md:block"
                 role="grid"
                 aria-label="Verfügbare Termine für {{ $serviceName ?? 'Service' }}"
                 aria-describedby="calendar-instructions">
                {{-- Calendar Header --}}
                <div class="calendar-header" role="row">
                    <div class="font-semibold text-sm" role="columnheader">Zeit</div>
                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                        <div class="text-center" role="columnheader" aria-sort="none">
                            <div class="font-semibold text-sm">{{ $this->getDayLabel($dayKey) }}</div>
                            @if(isset($weekMetadata['days'][$dayKey]))
                                <div class="text-xs text-[var(--calendar-text-secondary)]">
                                    {{ $weekMetadata['days'][$dayKey] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Time Slots Grid --}}
                <div class="time-slots-container"
                     role="presentation">
                    @php
                        // Generate hourly slots from 07:00 to 19:00
                        $timeSlots = [];
                        for ($hour = 7; $hour <= 19; $hour++) {
                            foreach ([0, 30] as $minute) {
                                if ($hour === 19 && $minute === 30) continue;
                                $timeSlots[] = sprintf('%02d:%02d', $hour, $minute);
                            }
                        }
                    @endphp

                    @foreach($timeSlots as $timeLabel)
                        <div class="text-xs font-medium text-[var(--calendar-text-secondary)] py-3 px-2 border-b border-[var(--calendar-border)]">
                            {{ $timeLabel }}
                        </div>

                        @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                            @php
                                $daySlots = $weekData[$dayKey] ?? [];
                                $slotForTime = collect($daySlots)->first(function($slot) use ($timeLabel) {
                                    return $slot['time'] === $timeLabel;
                                });
                            @endphp

                            <div class="border-b border-[var(--calendar-border)]" role="gridcell">
                                @if($slotForTime)
                                    <button
                                        wire:click="selectSlot('{{ $slotForTime['full_datetime'] }}', '{{ $slotForTime['day_name'] }} um {{ $slotForTime['time'] }}')"
                                        type="button"
                                        class="time-slot available w-full {{ $this->isSlotSelected($slotForTime['full_datetime']) ? 'selected' : '' }}"
                                        wire:loading.attr="disabled"
                                        aria-label="Termin {{ $slotForTime['day_name'] }} {{ $slotForTime['date'] }} um {{ $slotForTime['time'] }} Uhr. {{ $this->isSlotSelected($slotForTime['full_datetime']) ? 'Bereits ausgewählt' : 'Klicken um auszuwählen' }}"
                                        aria-pressed="{{ $this->isSlotSelected($slotForTime['full_datetime']) ? 'true' : 'false' }}"
                                        tabindex="{{ $this->isSlotSelected($slotForTime['full_datetime']) ? '0' : '-1' }}">
                                        <span class="slot-time">{{ $slotForTime['time'] }}</span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            {{-- MOBILE: Accordion Day View --}}
            <div class="md:hidden space-y-2"
                 role="region"
                 aria-label="Verfügbare Termine - Mobile Ansicht">
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                    @php
                        $daySlots = $weekData[$dayKey] ?? [];
                        $slotCount = count($daySlots);
                    @endphp

                    <div class="border border-[var(--calendar-border)] rounded-lg overflow-hidden"
                         x-data="{ open: @js($loop->first && $slotCount > 0) }"
                         wire:key="mobile-day-{{ $dayKey }}"
                         role="region"
                         :aria-label="`Termine für ${@js($this->getDayLabel($dayKey))}`">

                        {{-- Day Header (Accordion Toggle) --}}
                        <button @click="open = !open"
                                type="button"
                                class="w-full px-4 py-3 flex items-center justify-between bg-[var(--calendar-surface)] hover:bg-[var(--calendar-hover)] transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[var(--calendar-primary)]"
                                :aria-expanded="open.toString()"
                                :aria-label="`${@js($this->getDayLabel($dayKey))}, ${@js($slotCount)} Termine verfügbar. Klicken zum Öffnen`"
                                :class="{ 'border-b border-[var(--calendar-border)]': open }">
                            <div class="text-left">
                                <div class="font-medium text-sm">{{ $this->getDayLabel($dayKey) }}</div>
                                @if(isset($weekMetadata['days'][$dayKey]))
                                    <div class="text-xs text-[var(--calendar-text-secondary)]">
                                        {{ $weekMetadata['days'][$dayKey] }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if($slotCount > 0)
                                    <span class="text-xs bg-[var(--calendar-available)] text-white px-2 py-1 rounded">
                                        {{ $slotCount }}
                                    </span>
                                @else
                                    <span class="text-xs text-[var(--calendar-text-secondary)]">Keine</span>
                                @endif
                                <svg class="w-5 h-5 transition-transform"
                                     :class="{ 'rotate-180': open }"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>

                        {{-- Day Slots Grid --}}
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="grid grid-cols-2 gap-2 p-3"
                             role="group"
                             :aria-label="`Verfügbare Zeitslots für ${@js($this->getDayLabel($dayKey))}`">
                            @if($slotCount > 0)
                                @foreach($daySlots as $slot)
                                    <button wire:click="selectSlot('{{ $slot['full_datetime'] }}', '{{ $slot['day_name'] }} um {{ $slot['time'] }}')"
                                            type="button"
                                            class="time-slot available py-2 {{ $this->isSlotSelected($slot['full_datetime']) ? 'selected' : '' }}"
                                            wire:loading.attr="disabled"
                                            wire:key="mobile-slot-{{ $slot['full_datetime'] }}"
                                            aria-label="Termin um {{ $slot['time'] }} Uhr. {{ $this->isSlotSelected($slot['full_datetime']) ? 'Bereits ausgewählt' : 'Klicken um auszuwählen' }}"
                                            aria-pressed="{{ $this->isSlotSelected($slot['full_datetime']) ? 'true' : 'false' }}">
                                        {{ $slot['time'] }}
                                    </button>
                                @endforeach
                            @else
                                <div class="col-span-2 text-center py-4 text-sm text-[var(--calendar-text-secondary)]"
                                     role="status">
                                    Keine Termine verfügbar
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Selection Info --}}
            @if($selectedSlot)
                <div class="booking-alert alert-success mt-4" role="status" aria-label="Termin gewählt">
                    <div class="alert-icon" aria-hidden="true">✅</div>
                    <div class="alert-content">
                        <div class="alert-title">Termin gewählt</div>
                        <div class="alert-message"><strong>{{ $selectedSlotLabel ?? $selectedSlot }}</strong></div>
                    </div>
                </div>
            @endif

            {{-- Calendar Instructions (screen reader only) --}}
            <div id="calendar-instructions" class="sr-only">
                Dieses ist ein Buchungskalender. Klicken Sie auf einen verfügbaren Zeitslot, um ihn auszuwählen.
                Verwenden Sie die Pfeiltasten zur Navigation und die Eingabetaste zum Auswählen.
                Wählen Sie mehrere Slots aus, um mit einem zu buchen.
            </div>
        @endif
    @endif
</div>

{{-- Screen reader utility class --}}
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
