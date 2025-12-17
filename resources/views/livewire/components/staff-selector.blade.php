{{-- Staff Selector Component
    Reusable component for staff/employee selection in booking flow

    Props:
    - $availableEmployees: Array of qualified staff
    - $employeePreference: 'any' or staff ID
    - $serviceId: Current service (for context)

    Methods:
    - selectEmployee(preference): Select staff or 'any'
--}}

<div class="booking-section">
    <div class="booking-section-title">
        👥 Mitarbeiter-Präferenz
    </div>
    <div class="booking-section-subtitle">
        Wählen Sie einen Mitarbeiter oder „Nächster verfügbar"
    </div>

    @if(!$serviceId)
        {{-- No Service Selected --}}
        <div class="booking-alert alert-info">
            ℹ️ Wählen Sie zuerst einen Service aus
        </div>

    @elseif(count($availableEmployees) === 0)
        {{-- No Employees Available --}}
        <div class="booking-alert alert-warning">
            ⚠️ Keine Mitarbeiter für diesen Service verfügbar
        </div>

    @else
        {{-- Employee Selection Grid --}}
        <div class="space-y-2" role="radiogroup" aria-labelledby="staff-selector-label">

            {{-- "Any Available" Option --}}
            <button
                wire:click="selectEmployee('any')"
                type="button"
                class="selector-card w-full {{ $employeePreference === 'any' ? 'active' : '' }}"
                role="radio"
                :aria-checked="$employeePreference === 'any'"
                aria-label="Mitarbeiter: Nächster verfügbarer, Maximale Auswahl an Terminen">

                <div class="selector-card-header">
                    <div class="selector-card-icon">✨</div>
                    <div class="flex-1">
                        <div class="selector-card-title">
                            Nächster verfügbarer Mitarbeiter
                        </div>
                        <div class="selector-card-subtitle">
                            Maximale Auswahl an Terminen
                        </div>
                    </div>
                    @if($employeePreference === 'any')
                        <div class="ml-2 text-green-500 dark:text-green-400">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                            </svg>
                        </div>
                    @endif
                </div>
            </button>

            {{-- Specific Employees --}}
            <div class="border-t border-[var(--calendar-border)] pt-3">
                <div class="text-xs font-semibold text-[var(--calendar-text-secondary)] mb-2">
                    Oder wählen Sie einen spezifischen Mitarbeiter:
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($availableEmployees as $employee)
                        <button
                            wire:click="selectEmployee('{{ $employee['id'] }}')"
                            type="button"
                            class="selector-card {{ $employeePreference === $employee['id'] ? 'active' : '' }}"
                            wire:key="employee-{{ $employee['id'] }}"
                            role="radio"
                            :aria-checked="$employeePreference === '{{ $employee['id'] }}'"
                            aria-label="Mitarbeiter: {{ $employee['name'] }}{{ !empty($employee['email']) ? ', ' . $employee['email'] : '' }}">

                            <div class="selector-card-header">
                                <div class="selector-card-icon">👤</div>
                                <div class="flex-1">
                                    <div class="selector-card-title">
                                        {{ $employee['name'] }}
                                    </div>
                                    @if(!empty($employee['email']))
                                        <div class="selector-card-subtitle">
                                            {{ $employee['email'] }}
                                        </div>
                                    @endif
                                </div>
                                @if($employeePreference === $employee['id'])
                                    <div class="ml-2 text-green-500 dark:text-green-400">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Selection Info --}}
    @if($employeePreference && count($availableEmployees) > 0)
        @php
            $label = $employeePreference === 'any'
                ? 'Nächster verfügbarer'
                : collect($availableEmployees)->firstWhere('id', $employeePreference)['name'] ?? 'Unbekannt';
        @endphp
        <div class="booking-alert alert-success mt-3">
            ✅ Mitarbeiter: <strong>{{ $label }}</strong>
        </div>
    @endif
</div>
