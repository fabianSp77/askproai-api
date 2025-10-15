{{-- AppointmentBookingFlow - Professional Single-Page Booking --}}
<div class="appointment-booking-flow space-y-6">

    {{-- 1. BRANCH SELECTION --}}
    <div class="fi-section">
        <div class="fi-section-header">🏢 Filiale auswählen</div>

        @if(count($availableBranches) > 1)
            <div class="fi-radio-group">
                @foreach($availableBranches as $branch)
                    <label
                        class="fi-radio-option {{ $selectedBranchId === $branch['id'] ? 'selected' : '' }}"
                        wire:key="branch-{{ $branch['id'] }}">
                        <input
                            type="radio"
                            name="branch"
                            value="{{ $branch['id'] }}"
                            wire:model.live="selectedBranchId"
                            wire:click="selectBranch({{ $branch['id'] }})"
                            class="fi-radio-input">
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $branch['name'] }}</div>
                            @if(!empty($branch['address']))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $branch['address'] }}</div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        @elseif(count($availableBranches) === 1)
            <div class="text-sm text-gray-600 py-2">
                <strong>{{ $availableBranches[0]['name'] }}</strong>
                @if(!empty($availableBranches[0]['address']))
                    <span class="text-gray-400">- {{ $availableBranches[0]['address'] }}</span>
                @endif
            </div>
        @else
            <div class="text-sm text-red-600">Keine Filiale verfügbar</div>
        @endif
    </div>

    {{-- 2. SERVICE SELECTION --}}
    <div class="fi-section" role="region" aria-labelledby="service-selection-header">
        <div class="fi-section-header" id="service-selection-header">Service auswählen</div>

        <div class="fi-radio-group" role="radiogroup" aria-labelledby="service-selection-header">
            @foreach($availableServices as $service)
                <label
                    class="fi-radio-option {{ $selectedServiceId === $service['id'] ? 'selected' : '' }}"
                    wire:key="service-{{ $service['id'] }}"
                    aria-label="Service auswählen: {{ $service['name'] }}, Dauer {{ $service['duration_minutes'] }} Minuten">
                    <input
                        type="radio"
                        name="service"
                        value="{{ $service['id'] }}"
                        wire:model.live="selectedServiceId"
                        wire:change="selectService('{{ $service['id'] }}')"
                        class="fi-radio-input"
                        aria-describedby="service-{{ $service['id'] }}-desc"
                        {{ $selectedServiceId === $service['id'] ? 'checked' : '' }}>
                    <div class="flex-1" id="service-{{ $service['id'] }}-desc">
                        <div class="font-medium text-sm">{{ $service['name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $service['duration_minutes'] }} Minuten</div>
                    </div>
                </label>
            @endforeach
        </div>

        @if(count($availableServices) === 0)
            <div class="text-sm text-gray-400 text-center py-4">
                Keine Services verfügbar
            </div>
        @endif
    </div>

    {{-- 3. EMPLOYEE PREFERENCE --}}
    <div class="fi-section" role="region" aria-labelledby="employee-selection-header">
        <div class="fi-section-header" id="employee-selection-header">Mitarbeiter-Präferenz</div>

        <div class="fi-radio-group" role="radiogroup" aria-labelledby="employee-selection-header">
            {{-- "Any Available" Option --}}
            <label class="fi-radio-option {{ $employeePreference === 'any' ? 'selected' : '' }}"
                   aria-label="Mitarbeiter auswählen: Nächster verfügbarer Mitarbeiter, Maximale Auswahl an Terminen">
                <input
                    type="radio"
                    name="employee"
                    value="any"
                    wire:model.live="employeePreference"
                    wire:change="selectEmployee('any')"
                    class="fi-radio-input"
                    aria-describedby="employee-any-desc"
                    {{ $employeePreference === 'any' ? 'checked' : '' }}>
                <div class="flex-1" id="employee-any-desc">
                    <div class="font-medium text-sm">Nächster verfügbarer Mitarbeiter</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Maximale Auswahl an Terminen</div>
                </div>
            </label>

            {{-- Specific Employees --}}
            @foreach($availableEmployees as $employee)
                <label
                    class="fi-radio-option {{ $employeePreference === $employee['id'] ? 'selected' : '' }}"
                    wire:key="employee-{{ $employee['id'] }}"
                    aria-label="Mitarbeiter auswählen: {{ $employee['name'] }}{{ !empty($employee['email']) ? ', ' . $employee['email'] : '' }}">
                    <input
                        type="radio"
                        name="employee"
                        value="{{ $employee['id'] }}"
                        wire:model.live="employeePreference"
                        wire:change="selectEmployee('{{ $employee['id'] }}')"
                        class="fi-radio-input"
                        aria-describedby="employee-{{ $employee['id'] }}-desc"
                        {{ $employeePreference === $employee['id'] ? 'checked' : '' }}>
                    <div class="flex-1" id="employee-{{ $employee['id'] }}-desc">
                        <div class="font-medium text-sm">{{ $employee['name'] }}</div>
                        @if(!empty($employee['email']))
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $employee['email'] }}</div>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 4. CALENDAR/TIME SLOT SELECTION --}}
    <div class="fi-section">
        <div class="fi-section-header">
            Verfügbare Termine
            @if($serviceName)
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    ({{ $serviceName }} - {{ $serviceDuration }} Min)
                </span>
            @endif
        </div>

        {{-- Week Navigation --}}
        <nav class="flex items-center justify-between mb-4" aria-label="Wochennavigation">
            <button
                wire:click="previousWeek"
                type="button"
                class="fi-button-nav"
                wire:loading.attr="disabled"
                aria-label="Vorherige Woche anzeigen">
                ← Vorherige Woche
            </button>

            <div class="text-sm font-semibold text-gray-200"
                 aria-live="polite"
                 aria-atomic="true"
                 role="status">
                @if(isset($weekMetadata['start_date']) && isset($weekMetadata['end_date']))
                    <span class="sr-only">Aktuell angezeigte Woche: </span>
                    {{ $weekMetadata['start_date'] }} - {{ $weekMetadata['end_date'] }}
                @endif
            </div>

            <button
                wire:click="nextWeek"
                type="button"
                class="fi-button-nav"
                wire:loading.attr="disabled"
                aria-label="Nächste Woche anzeigen">
                Nächste Woche →
            </button>
        </nav>

        {{-- Loading State --}}
        @if($loading)
            <div class="text-center py-8">
                <div class="fi-loading-spinner"></div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">Lade Verfügbarkeiten...</div>
            </div>
        @endif

        {{-- Error State --}}
        @if($error)
            <div class="fi-error-alert">
                <strong>⚠️ Fehler:</strong> {{ $error }}
            </div>
        @endif

        {{-- Calendar Grid --}}
        @if(!$loading && !$error)
            @php
                // FIX: Use $this-> to access Livewire properties in @php blocks!
                $weekData = $this->weekData;  // Copy to local scope
                $weekMetadata = $this->weekMetadata;  // Copy to local scope
                $totalSlots = collect($weekData)->flatten(1)->count();
            @endphp

            @if($totalSlots === 0)
                {{-- No Availability Message --}}
                <div class="fi-warning-banner" style="padding: 1.5rem; margin: 1rem 0; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 0.5rem;">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <div style="font-size: 1.5rem;">⚠️</div>
                        <div>
                            <div style="font-weight: 600; font-size: 1rem; color: #92400e; margin-bottom: 0.5rem;">
                                Keine verfügbaren Termine
                            </div>
                            <div style="color: #78350f; font-size: 0.875rem; line-height: 1.5;">
                                <p style="margin-bottom: 0.5rem;">
                                    Für diesen Service wurden keine Termine gefunden. Dies kann folgende Gründe haben:
                                </p>
                                <ul style="list-style: disc; margin-left: 1.5rem; margin-top: 0.5rem;">
                                    <li>In Cal.com sind keine Mitarbeiter für Event Type zugewiesen</li>
                                    <li>Die zugewiesenen Mitarbeiter haben keine Verfügbarkeit konfiguriert</li>
                                    <li>Alle Termine für diese Woche sind bereits gebucht</li>
                                </ul>
                                <p style="margin-top: 0.75rem;">
                                    <strong>Lösung:</strong> Bitte prüfen Sie in Cal.com die Konfiguration des Event Types
                                    (Service: <strong>{{ $serviceName ?? 'N/A' }}</strong>)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- DESKTOP: 7-Day Grid (hidden on mobile) --}}
                <div class="fi-calendar-grid hidden md:grid">
                    {{-- Header Row --}}
                    <div class="fi-calendar-header" style="grid-column: 1;">Zeit</div>
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                    <div class="fi-calendar-header">
                        {{ $this->getDayLabel($dayKey) }}
                        @if(isset($weekMetadata['days'][$dayKey]))
                            <br><span class="text-xs text-gray-500 dark:text-gray-400">{{ $weekMetadata['days'][$dayKey] }}</span>
                        @endif
                    </div>
                @endforeach

                {{-- Time Rows (07:00 - 19:00, 30-minute intervals) --}}
                @php
                    // Generate 30-minute time slots from 07:00 to 19:00
                    $timeSlots = [];
                    for ($hour = 7; $hour <= 19; $hour++) {
                        foreach ([0, 30] as $minute) {
                            if ($hour === 19 && $minute === 30) continue; // Stop at 19:00
                            $timeSlots[] = sprintf('%02d:%02d', $hour, $minute);
                        }
                    }
                @endphp

                @foreach($timeSlots as $timeLabel)
                    {{-- Time Label --}}
                    <div class="fi-time-label fi-calendar-cell">{{ $timeLabel }}</div>

                    {{-- Day Cells --}}
                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                        <div class="fi-calendar-cell">
                            @php
                                // Find slot for this exact time on this day
                                $daySlots = $weekData[$dayKey] ?? [];
                                $slotForTime = collect($daySlots)->first(function($slot) use ($timeLabel) {
                                    return $slot['time'] === $timeLabel;
                                });
                            @endphp

                            @if($slotForTime)
                                <button
                                    wire:click="selectSlot('{{ $slotForTime['full_datetime'] }}', '{{ $slotForTime['day_name'] }} um {{ $slotForTime['time'] }}')"
                                    type="button"
                                    class="fi-slot-button {{ $this->isSlotSelected($slotForTime['full_datetime']) ? 'selected' : '' }}"
                                    wire:loading.attr="disabled"
                                    aria-label="Termin buchen: {{ $slotForTime['day_name'] }}, {{ $slotForTime['date'] }} um {{ $slotForTime['time'] }} Uhr"
                                    aria-pressed="{{ $this->isSlotSelected($slotForTime['full_datetime']) ? 'true' : 'false' }}"
                                    style="display: block !important; visibility: visible !important; opacity: 1 !important;">
                                    {{ $slotForTime['time'] }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endforeach
                </div>

                {{-- MOBILE: Accordion Day-Stack (shown on mobile only) --}}
                <div class="fi-calendar-mobile md:hidden">
                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                        @php
                            $daySlots = $weekData[$dayKey] ?? [];
                            $slotCount = count($daySlots);
                        @endphp

                        <div class="fi-calendar-day-section"
                             x-data="{ open: @js($loop->first && $slotCount > 0) }"
                             wire:key="mobile-day-{{ $dayKey }}">

                            {{-- Day Header (Accordion Toggle) --}}
                            <button @click="open = !open"
                                    type="button"
                                    class="fi-day-accordion-header"
                                    :aria-expanded="open.toString()"
                                    aria-controls="slots-{{ $dayKey }}"
                                    :class="{ 'has-slots': {{ $slotCount }} > 0 }">
                                <div class="flex-1 text-left">
                                    <span class="fi-day-name">{{ $this->getDayLabel($dayKey) }}</span>
                                    @if(isset($weekMetadata['days'][$dayKey]))
                                        <span class="fi-day-date">{{ $weekMetadata['days'][$dayKey] }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="fi-slot-count">
                                        @if($slotCount > 0)
                                            {{ $slotCount }} {{ $slotCount === 1 ? 'Slot' : 'Slots' }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">Keine Slots</span>
                                        @endif
                                    </span>
                                    <svg class="fi-chevron w-5 h-5 transition-transform duration-200"
                                         :class="{ 'rotate-180': open }"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </button>

                            {{-- Day Slots Grid (Collapsed/Expanded) --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                                 id="slots-{{ $dayKey }}"
                                 class="fi-day-slots-grid">
                                @if($slotCount > 0)
                                    @foreach($daySlots as $slot)
                                        <button wire:click="selectSlot('{{ $slot['full_datetime'] }}', '{{ $slot['day_name'] }} um {{ $slot['time'] }}')"
                                                type="button"
                                                class="fi-slot-button-mobile {{ $this->isSlotSelected($slot['full_datetime']) ? 'selected' : '' }}"
                                                wire:loading.attr="disabled"
                                                wire:key="mobile-slot-{{ $slot['full_datetime'] }}">
                                            {{ $slot['time'] }}
                                        </button>
                                    @endforeach
                                @else
                                    <div class="fi-empty-slots-message">
                                        Keine Termine verfügbar
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Info Banner --}}
                <div class="fi-info-banner">
                    <strong>Info:</strong>
                    Slots basieren auf {{ $serviceDuration }} Minuten Dauer.
                    @if($employeePreference === 'any')
                        Zeigt alle verfügbaren Mitarbeiter.
                    @else
                        @php
                            $selectedEmp = collect($availableEmployees)->firstWhere('id', $employeePreference);
                        @endphp
                        @if($selectedEmp)
                            Zeigt nur Termine von {{ $selectedEmp['name'] }}.
                        @endif
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- 5. CUSTOMER SEARCH/SELECTION --}}
    <div class="fi-section">
        <div class="fi-section-header">👤 Kunde auswählen</div>

        <div class="mb-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="customerSearchQuery"
                placeholder="Name, E-Mail oder Telefon eingeben..."
                class="fi-search-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>

        @if($selectedCustomerId && $selectedCustomerName)
            <div class="fi-selected-customer">
                <div class="flex items-center justify-between p-3 bg-success-50 border border-success-500 rounded-lg">
                    <div>
                        <div class="font-medium text-sm text-success-900">✓ {{ $selectedCustomerName }}</div>
                        <div class="text-xs text-success-700">Kunde ausgewählt</div>
                    </div>
                    <button
                        wire:click="$set('selectedCustomerId', null); $set('selectedCustomerName', null); $set('customerSearchQuery', '');"
                        class="text-success-700 hover:text-success-900 text-sm">
                        Ändern
                    </button>
                </div>
            </div>
        @endif

        {{-- Search Results OR Create New (ALWAYS VISIBLE when >= 3 chars) --}}
        @if(strlen($customerSearchQuery) >= 3 && !$selectedCustomerId)
            <div class="fi-search-results-container">
                {{-- Existing customers (if any) --}}
                @if(count($searchResults) > 0)
                    <div class="fi-search-results border border-gray-200 rounded-lg overflow-hidden mb-2">
                        @foreach($searchResults as $customer)
                            <button
                                wire:click="selectCustomer({{ $customer['id'] }})"
                                class="fi-customer-result w-full text-left p-3 hover:bg-gray-50 dark:hover:bg-gray-800 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition">
                                <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $customer['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    @if(!empty($customer['email']))
                                        {{ $customer['email'] }}
                                    @endif
                                    @if(!empty($customer['phone']))
                                        <span class="ml-2">{{ $customer['phone'] }}</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>

                    {{-- Divider text --}}
                    <div class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">
                        oder
                    </div>
                @else
                    {{-- No results message --}}
                    <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-3 mb-2">
                        Kein Kunde mit "{{ $customerSearchQuery }}" gefunden.
                    </div>
                @endif

                {{-- ALWAYS SHOW: Create New Button --}}
                <button
                    wire:click="showCreateCustomerForm"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-lg bg-success-600 text-white hover:bg-success-700 focus:ring-2 focus:ring-success-500 dark:bg-success-700 dark:hover:bg-success-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>➕ Neuen Kunden "{{ $customerSearchQuery }}" anlegen</span>
                </button>
            </div>
        @elseif(strlen($customerSearchQuery) > 0 && strlen($customerSearchQuery) < 3 && !$selectedCustomerId)
            <div class="text-xs text-gray-400 dark:text-gray-500 py-2">
                Mindestens 3 Zeichen eingeben...
            </div>
        @endif

        {{-- NEW CUSTOMER INLINE FORM --}}
        @if($showNewCustomerForm)
            <div class="fi-new-customer-form mt-4 p-4 border-2 border-success-500 rounded-lg bg-success-50 dark:bg-success-950">
                <div class="text-sm font-medium text-success-900 dark:text-success-100 mb-3">
                    ➕ Neuen Kunden anlegen
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Name *
                        </label>
                        <input
                            type="text"
                            wire:model="newCustomerName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="Vollständiger Name">
                        @error('newCustomerName')
                            <span class="text-xs text-danger-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Telefon
                        </label>
                        <input
                            type="tel"
                            wire:model="newCustomerPhone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="+49 123 456789">
                        @error('newCustomerPhone')
                            <span class="text-xs text-danger-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            E-Mail
                        </label>
                        <input
                            type="email"
                            wire:model="newCustomerEmail"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-success-500 focus:border-success-500 text-sm"
                            placeholder="kunde@example.com">
                        @error('newCustomerEmail')
                            <span class="text-xs text-danger-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            wire:click="createNewCustomer"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-success-600 text-white hover:bg-success-700 focus:ring-2 focus:ring-success-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Speichern
                        </button>
                        <button
                            wire:click="cancelCreateCustomer"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-2 focus:ring-gray-500">
                            Abbrechen
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- 6. SELECTED SLOT CONFIRMATION --}}
    @if($selectedSlot)
        <div class="fi-section fi-selected-confirmation">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold text-green-100 text-lg">Zeitslot ausgewählt</div>
                    <div class="text-sm text-green-200 mt-1">{{ $selectedSlotLabel }}</div>
                    <div class="text-xs text-green-300 mt-1">
                        Service: {{ $serviceName }} ({{ $serviceDuration }} Min)
                    </div>
                </div>
                <button
                    wire:click="$set('selectedSlot', null)"
                    class="px-4 py-2 bg-green-700 hover:bg-green-600 rounded text-sm text-white transition">
                    Ändern
                </button>
            </div>
        </div>
    @endif

</div>

{{-- Styles (Filament Theme Compatible) --}}
<style>
    /* Sections - use Filament's background colors */
    .fi-section {
        background-color: var(--color-gray-50);
        border: 1px solid var(--color-gray-200);
        border-radius: 0.75rem;
        padding: 1.5rem;
    }

    .dark .fi-section {
        background-color: var(--color-gray-800);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-700 */
    }

    .fi-section-header {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--color-gray-900);
        margin-bottom: 1rem;
    }

    .dark .fi-section-header {
        color: var(--color-gray-100);
    }

    .fi-radio-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Radio Options - Filament form style */
    .fi-radio-option {
        flex: 1;
        min-width: 200px;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        background-color: var(--color-white);
        border: 2px solid var(--color-gray-300);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .dark .fi-radio-option {
        background-color: var(--color-gray-700);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-radio-option:hover {
        background-color: var(--color-gray-50);
        border-color: var(--color-primary-500);
    }

    .dark .fi-radio-option:hover {
        background-color: var(--color-gray-600);
        border-color: var(--color-primary-400);
    }

    .fi-radio-option.selected {
        background-color: var(--color-primary-50);
        border-color: var(--color-primary-600);
    }

    .dark .fi-radio-option.selected {
        background-color: var(--color-primary-900);
        border-color: var(--color-primary-500);
    }

    /* NEW: Focus indicators for keyboard navigation (WCAG 2.4.7) */
    .fi-radio-option:focus-within {
        outline: 2px solid var(--color-primary-500);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .dark .fi-radio-option:focus-within {
        outline-color: var(--color-primary-400);
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
    }

    .fi-radio-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.75rem;
        cursor: pointer;
    }

    /* Calendar Grid */
    .fi-calendar-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        gap: 1px;
        background-color: var(--color-gray-300);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark .fi-calendar-grid {
        background-color: var(--color-gray-500); /* FIXED: Improved contrast for grid lines */
    }

    .fi-calendar-cell {
        background-color: var(--color-white);
        padding: 0.75rem;
        min-height: 60px;
    }

    .dark .fi-calendar-cell {
        background-color: var(--color-gray-800);
    }

    .fi-calendar-header {
        background-color: var(--color-gray-100);
        padding: 1rem;
        text-align: center;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--color-gray-700);
    }

    .dark .fi-calendar-header {
        background-color: var(--color-gray-700);
        color: var(--color-gray-200);
    }

    /* Slot Buttons */
    .fi-slot-button {
        width: 100%;
        padding: 0.625rem 0.5rem;
        background-color: var(--color-primary-600);
        color: white;
        border: 2px solid var(--color-primary-700);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .fi-slot-button:hover {
        background-color: var(--color-primary-700);
        border-color: var(--color-primary-800);
        transform: translateY(-1px);
    }

    .fi-slot-button.selected {
        background-color: var(--color-success-600);
        color: white;
        border-color: var(--color-success-700);
    }

    .fi-slot-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* NEW: Focus indicator for slot buttons */
    .fi-slot-button:focus {
        outline: 2px solid var(--color-white);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px var(--color-primary-400);
    }

    .fi-time-label {
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        color: var(--color-gray-500);
        font-weight: 500;
        background-color: var(--color-gray-50);
        position: sticky;
        left: 0;
        z-index: 10;
    }

    .dark .fi-time-label {
        color: var(--color-gray-400);
        background-color: var(--color-gray-800);
    }

    /* Info Banner */
    .fi-info-banner {
        background-color: var(--color-info-50);
        border: 1px solid var(--color-info-200);
        border-radius: 0.5rem;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        color: var(--color-info-700);
        margin-top: 1rem;
    }

    .dark .fi-info-banner {
        background-color: var(--color-info-900);
        border-color: var(--color-info-700);
        color: var(--color-info-200);
    }

    /* Navigation Buttons */
    .fi-button-nav {
        padding: 0.5rem 1rem;
        background-color: var(--color-white);
        color: var(--color-gray-700);
        border: 1px solid var(--color-gray-300);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .dark .fi-button-nav {
        background-color: var(--color-gray-700);
        color: var(--color-gray-200);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-50);
        border-color: var(--color-gray-400);
    }

    .dark .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-600);
        border-color: var(--color-gray-400); /* FIXED: Better hover contrast */
    }

    .fi-button-nav:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* NEW: Focus indicator for navigation buttons */
    .fi-button-nav:focus {
        outline: 2px solid var(--color-primary-500);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .dark .fi-button-nav:focus {
        outline-color: var(--color-primary-400);
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.2);
    }

    /* Selected Confirmation */
    .fi-selected-confirmation {
        background-color: var(--color-success-50);
        border-color: var(--color-success-500);
    }

    .dark .fi-selected-confirmation {
        background-color: var(--color-success-900);
        border-color: var(--color-success-600);
    }

    /* NEW: Search Input */
    .fi-search-input {
        background-color: var(--color-white);
        color: var(--color-gray-900);
        border: 1px solid var(--color-gray-300);
    }

    .dark .fi-search-input {
        background-color: var(--color-gray-700);
        color: var(--color-gray-100);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-search-input:focus {
        outline: none;
        ring: 2px;
        ring-color: var(--color-primary-500);
        border-color: var(--color-primary-500);
    }

    /* NEW: Search Results */
    .fi-search-results {
        background-color: var(--color-white);
        border: 1px solid var(--color-gray-200);
        border-radius: 0.5rem;
        overflow: hidden;
        max-height: 300px;
        overflow-y: auto;
    }

    .dark .fi-search-results {
        background-color: var(--color-gray-700);
        border-color: var(--color-gray-500); /* FIXED: Improved contrast from gray-600 */
    }

    .fi-customer-result {
        transition: background-color 0.15s ease;
    }

    .fi-customer-result:hover {
        background-color: var(--color-gray-50);
    }

    .dark .fi-customer-result:hover {
        background-color: var(--color-gray-700);
    }

    .fi-customer-result:last-child {
        border-bottom: none;
    }

    /* NEW: Selected Customer */
    .fi-selected-customer {
        margin-top: 0.5rem;
    }

    /* NEW: Error Alert - Improved visibility */
    .fi-error-alert {
        background-color: var(--color-danger-50);
        border: 2px solid var(--color-danger-500); /* Stronger border */
        border-radius: 0.5rem;
        padding: 1rem;
        font-size: 0.875rem;
        color: var(--color-danger-700);
        margin-bottom: 1rem;
    }

    .dark .fi-error-alert {
        background-color: var(--color-danger-900);
        border-color: var(--color-danger-400); /* Better contrast in dark mode */
        color: var(--color-danger-200);
    }

    /* NEW: Loading Spinner - Better visibility */
    .fi-loading-spinner {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        border: 3px solid var(--color-gray-300);
        border-top-color: var(--color-primary-600);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .dark .fi-loading-spinner {
        border-color: var(--color-gray-600);
        border-top-color: var(--color-primary-400); /* Better visibility in dark mode */
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ========================================
       ACCESSIBILITY UTILITIES
       ======================================== */

    /* Screen Reader Only (SR-Only) */
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

    /* ========================================
       MOBILE CALENDAR ACCORDION STYLES
       ======================================== */

    /* Mobile Container */
    .fi-calendar-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Day Section */
    .fi-calendar-day-section {
        border-radius: 0.5rem;
        overflow: hidden;
        background: var(--color-gray-50);
    }

    .dark .fi-calendar-day-section {
        background: var(--color-gray-800);
    }

    /* Accordion Header (Day Toggle) */
    .fi-day-accordion-header {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: var(--color-gray-100);
        border: 2px solid var(--color-gray-300);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s ease;
        min-height: 56px; /* WCAG AAA touch target */
        font-weight: 500;
        color: var(--color-gray-900);
    }

    .dark .fi-day-accordion-header {
        background: var(--color-gray-700);
        border-color: var(--color-gray-500);
        color: var(--color-gray-100);
    }

    .fi-day-accordion-header:hover {
        background: var(--color-gray-200);
        border-color: var(--color-primary-500);
    }

    .dark .fi-day-accordion-header:hover {
        background: var(--color-gray-600);
        border-color: var(--color-primary-400);
    }

    .fi-day-accordion-header.has-slots {
        border-color: var(--color-primary-600);
        background: var(--color-primary-50);
    }

    .dark .fi-day-accordion-header.has-slots {
        border-color: var(--color-primary-500);
        background: var(--color-gray-700);
    }

    /* Day Name & Date */
    .fi-day-name {
        font-weight: 600;
        font-size: 1rem;
        display: block;
        margin-bottom: 0.125rem;
    }

    .fi-day-date {
        font-size: 0.875rem;
        color: var(--color-gray-500);
        display: block;
    }

    .dark .fi-day-date {
        color: var(--color-gray-400);
    }

    /* Slot Count Badge */
    .fi-slot-count {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-primary-700);
    }

    .dark .fi-slot-count {
        color: var(--color-primary-300);
    }

    /* Chevron Icon */
    .fi-chevron {
        transition: transform 200ms ease;
    }

    /* Day Slots Grid (3 columns on mobile) */
    .fi-day-slots-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        padding: 1rem;
        background: var(--color-white);
    }

    .dark .fi-day-slots-grid {
        background: var(--color-gray-800);
    }

    /* Mobile Slot Buttons (WCAG AAA compliant: 56x56px) */
    .fi-slot-button-mobile {
        min-height: 56px;
        min-width: 56px;
        padding: 0.75rem;
        background-color: var(--color-primary-600);
        color: white;
        border: 2px solid var(--color-primary-700);
        border-radius: 0.5rem;
        font-size: 1rem; /* 16px for better readability on mobile */
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fi-slot-button-mobile:hover:not(:disabled) {
        background-color: var(--color-primary-700);
        border-color: var(--color-primary-800);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .fi-slot-button-mobile.selected {
        background-color: var(--color-success-600);
        border-color: var(--color-success-700);
        color: white;
    }

    .fi-slot-button-mobile:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .fi-slot-button-mobile:focus {
        outline: 2px solid var(--color-white);
        outline-offset: 2px;
        box-shadow: 0 0 0 4px var(--color-primary-400);
    }

    /* Empty Slots Message */
    .fi-empty-slots-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 2rem 1rem;
        color: var(--color-gray-500);
        font-size: 0.875rem;
    }

    .dark .fi-empty-slots-message {
        color: var(--color-gray-400);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .fi-radio-option {
            min-width: 100%;
        }

        /* Desktop grid should NOT scroll horizontally on mobile (it's hidden) */
        .fi-calendar-grid {
            overflow-x: auto;
        }

        /* Ensure all interactive elements meet WCAG AAA touch targets */
        .fi-button-nav,
        .fi-radio-option,
        .fi-slot-button {
            min-height: 56px;
        }
    }

    /* Small phones (< 360px) - 2 columns instead of 3 */
    @media (max-width: 360px) {
        .fi-day-slots-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
