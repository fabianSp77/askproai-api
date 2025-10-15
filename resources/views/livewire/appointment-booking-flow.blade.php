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
                                <div class="text-xs text-gray-400">{{ $branch['address'] }}</div>
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

    {{-- 2. CUSTOMER SEARCH --}}
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

        @if(strlen($customerSearchQuery) >= 3 && count($searchResults) > 0 && !$selectedCustomerId)
            <div class="fi-search-results">
                @foreach($searchResults as $customer)
                    <button
                        wire:click="selectCustomer({{ $customer['id'] }})"
                        class="fi-customer-result w-full text-left p-3 hover:bg-gray-50 border-b border-gray-200 transition">
                        <div class="font-medium text-sm">{{ $customer['name'] }}</div>
                        <div class="text-xs text-gray-500">
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
        @elseif(strlen($customerSearchQuery) >= 3 && count($searchResults) === 0 && !$selectedCustomerId)
            <div class="text-sm text-gray-400 text-center py-4">
                Kein Kunde gefunden. Bitte anderen Suchbegriff eingeben.
            </div>
        @elseif(strlen($customerSearchQuery) > 0 && strlen($customerSearchQuery) < 3 && !$selectedCustomerId)
            <div class="text-xs text-gray-400 py-2">
                Mindestens 3 Zeichen eingeben...
            </div>
        @endif
    </div>

    {{-- 3. SERVICE SELECTION --}}
    <div class="fi-section">
        <div class="fi-section-header">Service auswählen</div>

        <div class="fi-radio-group">
            @foreach($availableServices as $service)
                <label
                    class="fi-radio-option {{ $selectedServiceId === $service['id'] ? 'selected' : '' }}"
                    wire:key="service-{{ $service['id'] }}">
                    <input
                        type="radio"
                        name="service"
                        value="{{ $service['id'] }}"
                        wire:model.live="selectedServiceId"
                        wire:change="selectService('{{ $service['id'] }}')"
                        class="fi-radio-input">
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $service['name'] }}</div>
                        <div class="text-xs text-gray-400">{{ $service['duration_minutes'] }} Minuten</div>
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

    {{-- 2. EMPLOYEE PREFERENCE --}}
    <div class="fi-section">
        <div class="fi-section-header">Mitarbeiter-Präferenz</div>

        <div class="fi-radio-group">
            {{-- "Any Available" Option --}}
            <label class="fi-radio-option {{ $employeePreference === 'any' ? 'selected' : '' }}">
                <input
                    type="radio"
                    name="employee"
                    value="any"
                    wire:model.live="employeePreference"
                    wire:change="selectEmployee('any')"
                    class="fi-radio-input">
                <div class="flex-1">
                    <div class="font-medium text-sm">Nächster verfügbarer Mitarbeiter</div>
                    <div class="text-xs text-gray-400">Maximale Auswahl an Terminen</div>
                </div>
            </label>

            {{-- Specific Employees --}}
            @foreach($availableEmployees as $employee)
                <label
                    class="fi-radio-option {{ $employeePreference === $employee['id'] ? 'selected' : '' }}"
                    wire:key="employee-{{ $employee['id'] }}">
                    <input
                        type="radio"
                        name="employee"
                        value="{{ $employee['id'] }}"
                        wire:model.live="employeePreference"
                        wire:change="selectEmployee('{{ $employee['id'] }}')"
                        class="fi-radio-input">
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $employee['name'] }}</div>
                        @if(!empty($employee['email']))
                            <div class="text-xs text-gray-400">{{ $employee['email'] }}</div>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 3. CALENDAR --}}
    <div class="fi-section">
        <div class="fi-section-header">
            Verfügbare Termine
            @if($serviceName)
                <span class="text-sm font-normal text-gray-400">
                    ({{ $serviceName }} - {{ $serviceDuration }} Min)
                </span>
            @endif
        </div>

        {{-- Week Navigation --}}
        <div class="flex items-center justify-between mb-4">
            <button
                wire:click="previousWeek"
                class="fi-button-nav"
                wire:loading.attr="disabled">
                ← Vorherige Woche
            </button>

            <div class="text-sm font-semibold text-gray-200">
                @if(isset($weekMetadata['start_date']) && isset($weekMetadata['end_date']))
                    {{ $weekMetadata['start_date'] }} - {{ $weekMetadata['end_date'] }}
                @endif
            </div>

            <button
                wire:click="nextWeek"
                class="fi-button-nav"
                wire:loading.attr="disabled">
                Nächste Woche →
            </button>
        </div>

        {{-- Loading State --}}
        @if($loading)
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <div class="mt-2 text-sm text-gray-400">Lade Verfügbarkeiten...</div>
            </div>
        @endif

        {{-- Error State --}}
        @if($error)
            <div class="bg-red-900/20 border border-red-700 rounded-lg p-4 text-sm text-red-300">
                <strong>Fehler:</strong> {{ $error }}
            </div>
        @endif

        {{-- Calendar Grid --}}
        @if(!$loading && !$error)
            <div class="fi-calendar-grid">
                {{-- Header Row --}}
                <div class="fi-calendar-header" style="grid-column: 1;">Zeit</div>
                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                    <div class="fi-calendar-header">
                        {{ $this->getDayLabel($dayKey) }}
                        @if(isset($weekMetadata['days'][$dayKey]))
                            <br><span class="text-xs text-gray-400">{{ $weekMetadata['days'][$dayKey] }}</span>
                        @endif
                    </div>
                @endforeach

                {{-- Time Rows (08:00 - 18:00) --}}
                @foreach(range(8, 18) as $hour)
                    @php
                        $timeLabel = sprintf('%02d:00', $hour);
                    @endphp

                    {{-- Time Label --}}
                    <div class="fi-time-label fi-calendar-cell">{{ $timeLabel }}</div>

                    {{-- Day Cells --}}
                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayKey)
                        <div class="fi-calendar-cell">
                            @php
                                // Find slots for this hour on this day
                                $daySlots = $weekData[$dayKey] ?? [];
                                $slotForHour = collect($daySlots)->first(function($slot) use ($timeLabel) {
                                    return str_starts_with($slot['time'], $timeLabel);
                                });
                            @endphp

                            @if($slotForHour)
                                <button
                                    wire:click="selectSlot('{{ $slotForHour['full_datetime'] }}', '{{ $slotForHour['day_name'] }} um {{ $slotForHour['time'] }}')"
                                    class="fi-slot-button {{ $this->isSlotSelected($slotForHour['full_datetime']) ? 'selected' : '' }}"
                                    wire:loading.attr="disabled">
                                    {{ $slotForHour['time'] }}
                                </button>
                            @endif
                        </div>
                    @endforeach
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
    </div>

    {{-- 4. SELECTED SLOT CONFIRMATION --}}
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
        border-color: var(--color-gray-700);
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
        border-color: var(--color-gray-600);
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
        background-color: var(--color-gray-600);
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
        border-color: var(--color-gray-600);
    }

    .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-50);
        border-color: var(--color-gray-400);
    }

    .dark .fi-button-nav:hover:not(:disabled) {
        background-color: var(--color-gray-600);
        border-color: var(--color-gray-500);
    }

    .fi-button-nav:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        background-color: var(--color-gray-800);
        color: var(--color-gray-100);
        border-color: var(--color-gray-600);
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
        background-color: var(--color-gray-800);
        border-color: var(--color-gray-600);
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

    /* Responsive */
    @media (max-width: 768px) {
        .fi-radio-option {
            min-width: 100%;
        }

        .fi-calendar-grid {
            overflow-x: auto;
        }
    }
</style>
