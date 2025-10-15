{{-- Appointment Week Picker - Livewire Component --}}
{{-- Service-specific week-at-a-glance view for appointment booking --}}

<style>
    /* Selected slot styling */
    .slot-button.slot-selected {
        background: linear-gradient(135deg, rgb(59 130 246), rgb(37 99 235)) !important;
        color: white !important;
        font-weight: 700 !important;
        border-color: rgb(29 78 216) !important;
        box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.4), 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        transform: scale(1.02) !important;
    }

    .slot-button.slot-selected span {
        color: white !important;
    }

    /* FIX for Issue #701: Explicit responsive control to handle Zoom 66.67% */
    /* These media queries work correctly regardless of browser zoom level */

    /* Mobile: < 768px - Show list, hide grid */
    @media (max-width: 767px) {
        .week-picker-desktop {
            display: none !important;
        }
        .week-picker-mobile {
            display: block !important;
        }
    }

    /* Desktop: >= 768px - Show grid, hide list */
    @media (min-width: 768px) {
        .week-picker-desktop {
            display: grid !important;
        }
        .week-picker-mobile {
            display: none !important;
        }
    }
</style>

<div class="appointment-week-picker w-full relative"
     x-data="{
         hoveredSlot: null,
         showMobileDay: null,
         selectSlot(datetime, button) {
             // Update hidden field
             const form = document.querySelector('form');
             const input = form?.querySelector('input[name=starts_at]');
             if (input) {
                 input.value = datetime;
                 input.dispatchEvent(new Event('input', { bubbles: true }));
                 input.dispatchEvent(new Event('change', { bubbles: true }));
             }

             // Update debug display (if visible)
             const debugSlot = document.getElementById('debug-slot');
             const slotStatus = document.getElementById('slot-status');
             if (debugSlot) debugSlot.textContent = datetime;
             if (slotStatus) {
                 slotStatus.textContent = '✅ SLOT GESETZT: ' + datetime;
                 slotStatus.className = 'text-xs text-green-700 dark:text-green-300 font-bold';
             }

             // Visual feedback
             document.querySelectorAll('.slot-button').forEach(b => b.classList.remove('slot-selected'));
             button.classList.add('slot-selected');

             console.log('✅ Slot selected:', datetime, 'Input:', !!input);
         }
     }">

    {{-- Service Info Header --}}
    @if($serviceName)
        <div class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-100">
                        📅 {{ $serviceName }}
                    </h3>
                    <p class="text-xs text-primary-700 dark:text-primary-300 mt-0.5">
                        Dauer: {{ $serviceDuration }} Minuten
                    </p>
                    {{-- DEBUG: Test Button --}}
                    @if($selectedSlot)
                        <p class="text-xs text-success-700 dark:text-success-300 mt-1 font-bold">
                            ✅ Selected: {{ $selectedSlot }}
                        </p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="$wire.selectSlot('2025-10-23T08:00:00+02:00')"
                        class="px-3 py-1.5 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600 transition"
                        title="DEBUG: Test Slot Selection">
                        🧪 Test
                    </button>
                    <button
                        type="button"
                        @click="$wire.refreshWeek()"
                        class="px-3 py-1.5 text-xs bg-white dark:bg-gray-800 text-primary-700 dark:text-primary-300 rounded hover:bg-primary-100 dark:hover:bg-primary-900/30 transition disabled:opacity-50"
                        title="Verfügbarkeiten aktualisieren">
                        🔄 Aktualisieren
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Error Display --}}
    @if($error)
        <div class="mb-4 p-4 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-danger-600 dark:text-danger-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-danger-900 dark:text-danger-100">Fehler beim Laden der Verfügbarkeiten</p>
                    <p class="text-xs text-danger-700 dark:text-danger-300 mt-1">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Week Navigation Bar --}}
    <div class="flex items-center justify-between mb-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
        {{-- Previous Week Button --}}
        <button
            type="button"
            @click="$wire.previousWeek()"
            class="px-4 py-2 text-sm font-medium bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
            title="Vorherige Woche">
            <span class="flex items-center gap-1">
                ◀ <span class="hidden sm:inline">Vorherige</span>
            </span>
        </button>

        {{-- Current Week Info --}}
        <div class="text-center px-2">
            @if($weekMetadata)
                <div class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">
                    KW {{ $weekMetadata['week_number'] }}: {{ $weekMetadata['start_date'] }} - {{ $weekMetadata['end_date'] }}
                </div>
                @if($weekMetadata['is_current_week'])
                    <div class="text-xs text-primary-600 dark:text-primary-400 font-medium mt-0.5">
                        ✓ Aktuelle Woche
                    </div>
                @elseif($weekMetadata['is_past'])
                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                        Vergangene Woche
                    </div>
                @elseif($weekOffset === 1)
                    <div class="text-xs text-primary-600 dark:text-primary-400 mt-0.5">
                        Nächste Woche
                    </div>
                @endif
            @endif
        </div>

        {{-- Next Week Button --}}
        <button
            type="button"
            @click="$wire.nextWeek()"
            class="px-4 py-2 text-sm font-medium bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
            title="Nächste Woche">
            <span class="flex items-center gap-1">
                <span class="hidden sm:inline">Nächste</span> ▶
            </span>
        </button>
    </div>

    {{-- Quick Navigation --}}
    @if($weekMetadata && !$weekMetadata['is_current_week'])
        <div class="flex gap-2 mb-4">
            <button
                type="button"
                @click="$wire.goToCurrentWeek()"
                class="w-full sm:w-auto px-4 py-2 text-sm font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-md hover:bg-primary-200 dark:hover:bg-primary-900/50 transition">
                📅 Zur aktuellen Woche springen
            </button>
        </div>
    @endif

    {{-- Selected Slot Display --}}
    @if($selectedSlot)
        <div class="mb-4 p-3 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-xs text-success-700 dark:text-success-300 font-medium">
                        Ausgewählter Termin:
                    </p>
                    <p class="text-sm font-semibold text-success-900 dark:text-success-100">
                        {{ \Carbon\Carbon::parse($selectedSlot)->format('d.m.Y H:i') }} Uhr
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Week Grid (7 Columns) - Desktop View --}}
    <div class="week-picker-desktop grid-cols-7 gap-2"
         wire:loading.class="opacity-50 pointer-events-none">

        @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
            <div class="flex flex-col min-h-[200px]">
                {{-- Day Header --}}
                <div class="text-center font-semibold text-sm mb-2 py-2 px-1 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="text-gray-900 dark:text-white">
                        {{ $this->getDayLabel($day) }}
                    </div>
                    @if(isset($weekMetadata['days'][$day]))
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                            {{ $weekMetadata['days'][$day] }}
                        </div>
                    @endif
                </div>

                {{-- Slots for this day --}}
                <div class="space-y-1 overflow-y-auto" style="max-height: 400px;">
                    @forelse($weekData[$day] ?? [] as $slot)
                        <button
                            type="button"
                            wire:click="selectSlot('{{ $slot['full_datetime'] }}')"
                            @click.prevent="selectSlot('{{ $slot['full_datetime'] }}', $el)"
                            class="slot-button w-full px-3 py-2 text-sm text-center rounded-lg transition-all duration-150 border-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md"
                            title="{{ $this->getFullDayName($day) }}, {{ $slot['date'] }} - {{ $slot['time'] }} Uhr">
                            <span class="block font-semibold text-base">{{ $slot['time'] }}</span>
                            @if($slot['is_morning'])
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">🌅 Morgen</span>
                            @elseif($slot['is_afternoon'])
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">☀️ Mittag</span>
                            @elseif($slot['is_evening'])
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">🌆 Abend</span>
                            @endif
                        </button>
                    @empty
                        <div class="text-xs text-gray-400 dark:text-gray-600 text-center py-4 italic">
                            Keine Slots
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Mobile View (Stacked Days) --}}
    <div class="week-picker-mobile space-y-3">
        @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                {{-- Day Header (Collapsible) --}}
                <button
                    @click="showMobileDay = showMobileDay === '{{ $day }}' ? null : '{{ $day }}'"
                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $this->getFullDayName($day) }}
                        </div>
                        @if(isset($weekMetadata['days'][$day]))
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $weekMetadata['days'][$day] }}
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                            {{ count($weekData[$day] ?? []) }} Slots
                        </span>
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                             :class="{ 'rotate-180': showMobileDay === '{{ $day }}' }"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </button>

                {{-- Day Slots (Collapsible Content) --}}
                <div x-show="showMobileDay === '{{ $day }}'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-y-95"
                     x-transition:enter-end="opacity-100 transform scale-y-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-y-100"
                     x-transition:leave-end="opacity-0 transform scale-y-95"
                     class="p-3 bg-white dark:bg-gray-900 space-y-2 origin-top">
                    @forelse($weekData[$day] ?? [] as $slot)
                        <button
                            type="button"
                            wire:click="selectSlot('{{ $slot['full_datetime'] }}')"
                            @click.prevent="selectSlot('{{ $slot['full_datetime'] }}', $el)"
                            class="slot-button w-full px-4 py-3 text-base text-left rounded-lg transition-all border-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold">{{ $slot['time'] }}</span>
                                    @if($slot['is_morning'])
                                        <span class="text-xs">🌅 Morgen</span>
                                    @elseif($slot['is_afternoon'])
                                        <span class="text-xs">☀️ Mittag</span>
                                    @elseif($slot['is_evening'])
                                        <span class="text-xs">🌆 Abend</span>
                                    @endif
                                </div>
                                @if($this->isSlotSelected($slot['full_datetime']))
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="text-sm text-gray-400 dark:text-gray-600 text-center py-6 italic">
                            Keine verfügbaren Slots für diesen Tag
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Empty Week Message --}}
    @if($this->isEmptyWeek && !$error)
        <div class="mt-6 p-6 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg text-center">
            <svg class="w-12 h-12 mx-auto text-warning-600 dark:text-warning-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-warning-900 dark:text-warning-100 mb-2">
                Keine verfügbaren Termine in dieser Woche
            </h3>
            <p class="text-sm text-warning-700 dark:text-warning-300 mb-4">
                Für den Service "{{ $serviceName }}" sind in KW {{ $weekMetadata['week_number'] ?? '?' }} keine freien Slots verfügbar.
            </p>
            <div class="flex gap-2 justify-center">
                <button
                    wire:click="nextWeek"
                    class="px-4 py-2 text-sm bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-300 rounded-md hover:bg-warning-200 dark:hover:bg-warning-900/50 transition">
                    Nächste Woche anzeigen ▶
                </button>
                <button
                    wire:click="refreshWeek"
                    class="px-4 py-2 text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-700">
                    🔄 Neu laden
                </button>
            </div>
        </div>
    @endif

    {{-- Stats Footer --}}
    @if(!$this->isEmptyWeek && !$error)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <div>
                    <span class="font-medium">{{ $this->totalSlots }}</span> verfügbare Slots in dieser Woche
                </div>
                @if($selectedSlot)
                    <div class="text-success-600 dark:text-success-400 font-medium">
                        ✓ Slot ausgewählt
                    </div>
                @else
                    <div class="text-warning-600 dark:text-warning-400">
                        Bitte wählen Sie einen Termin
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading wire:target="previousWeek,nextWeek,goToCurrentWeek,loadWeekData,refreshWeek"
         class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 flex items-center justify-center rounded-lg z-10">
        <div class="text-center">
            <svg class="animate-spin h-10 w-10 text-primary-600 dark:text-primary-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                Lade Verfügbarkeiten...
            </p>
        </div>
    </div>
</div>
