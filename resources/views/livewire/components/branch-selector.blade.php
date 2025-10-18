{{-- Branch Selector Component
    Reusable component for branch selection in booking flow

    Accessibility Features:
    - ARIA roles and labels for screen readers
    - Keyboard navigation (Arrow keys, Enter)
    - Focus indicators
    - Error handling and live announcements

    Props:
    - $availableBranches: Array of branches
    - $selectedBranchId: Currently selected branch

    Methods:
    - selectBranch(branchId): Select a branch
--}}

<div class="booking-section">
    <div class="booking-section-title" id="branch-selector-label">
        🏢 Filiale auswählen
    </div>
    <div class="booking-section-subtitle">
        Wählen Sie eine Filiale aus, um Verfügbarkeiten zu sehen
    </div>

    {{-- Announcements for screen readers --}}
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
        @if(count($availableBranches) === 0)
            Keine Filiale verfügbar
        @elseif(count($availableBranches) === 1)
            Filiale {{ $availableBranches[0]['name'] }} wurde automatisch ausgewählt
        @elseif($selectedBranchId)
            @php
                $selected = collect($availableBranches)->firstWhere('id', $selectedBranchId);
            @endphp
            Filiale {{ $selected['name'] ?? 'Unknown' }} wurde ausgewählt
        @else
            Bitte wählen Sie eine Filiale aus
        @endif
    </div>

    @if(count($availableBranches) === 0)
        {{-- No Branches Available --}}
        <div class="booking-alert alert-error" role="alert">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <div class="alert-title">Keine Filiale verfügbar</div>
                <div class="alert-message">Es sind derzeit keine Filialen verfügbar. Bitte kontaktieren Sie den Support.</div>
            </div>
        </div>

    @elseif(count($availableBranches) === 1)
        {{-- Single Branch - Auto-selected Display --}}
        <div class="selector-card active"
             role="status"
             aria-label="Filiale automatisch ausgewählt: {{ $availableBranches[0]['name'] }}">
            <div class="selector-card-header">
                <div class="selector-card-icon" aria-hidden="true">🏢</div>
                <div class="flex-1">
                    <div class="selector-card-title">
                        {{ $availableBranches[0]['name'] }}
                    </div>
                    @if(!empty($availableBranches[0]['address']))
                        <div class="selector-card-subtitle">
                            {{ $availableBranches[0]['address'] }}
                        </div>
                    @endif
                </div>
                <div class="ml-2 text-green-500 dark:text-green-400" aria-hidden="true">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </div>
            </div>
        </div>

    @else
        {{-- Multiple Branches - Selection Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3"
             role="radiogroup"
             aria-labelledby="branch-selector-label"
             aria-describedby="branch-instructions">
            @foreach($availableBranches as $branch)
                <button
                    wire:click="selectBranch('{{ $branch['id'] }}')"
                    type="button"
                    class="selector-card {{ $selectedBranchId === $branch['id'] ? 'active' : '' }}"
                    wire:key="branch-{{ $branch['id'] }}"
                    role="radio"
                    aria-checked="{{ $selectedBranchId === $branch['id'] ? 'true' : 'false' }}"
                    aria-label="Filiale {{ $branch['name'] }}{{ !empty($branch['address']) ? ' - ' . $branch['address'] : '' }}"
                    tabindex="{{ $selectedBranchId === $branch['id'] ? '0' : '-1' }}">

                    <div class="selector-card-header">
                        <div class="selector-card-icon" aria-hidden="true">🏢</div>
                        <div class="flex-1">
                            <div class="selector-card-title">
                                {{ $branch['name'] }}
                            </div>
                            @if(!empty($branch['address']))
                                <div class="selector-card-subtitle">
                                    {{ $branch['address'] }}
                                </div>
                            @endif
                        </div>
                        @if($selectedBranchId === $branch['id'])
                            <div class="ml-2 text-green-500 dark:text-green-400" aria-hidden="true">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        <div id="branch-instructions" class="sr-only">
            Verwenden Sie die Pfeiltasten, um zwischen Filialen zu navigieren, und drücken Sie die Eingabetaste, um auszuwählen.
        </div>
    @endif

    {{-- Selection Info --}}
    @if($selectedBranchId && count($availableBranches) > 0)
        @php
            $selected = collect($availableBranches)->firstWhere('id', $selectedBranchId);
        @endphp
        <div class="booking-alert alert-success mt-3" role="status">
            <div class="alert-icon" aria-hidden="true">✅</div>
            <div class="alert-content">
                <div class="alert-title">Filiale gewählt</div>
                <div class="alert-message"><strong>{{ $selected['name'] ?? 'Unknown' }}</strong></div>
            </div>
        </div>
    @endif
</div>

{{-- Utility class for screen reader only content --}}
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
