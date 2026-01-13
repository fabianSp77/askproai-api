@php
    $record = $getRecord();

    // Ensure relations are loaded (fallback if eager loading didn't work)
    if ($record->category_id && !$record->relationLoaded('category')) {
        $record->load('category');
    }
    if ($record->call_id && !$record->relationLoaded('call')) {
        $record->load('call');
    }

    $hasCall = $record->call_id !== null;
    $hasCustomer = $record->customer_id !== null;
    $hasAssigned = $record->assigned_to !== null;
@endphp

<div class="space-y-3" role="region" aria-label="Verknupfte Datensätze">
    {{-- Related Call --}}
    @if($hasCall)
        <a href="{{ route('filament.admin.resources.calls.view', $record->call_id) }}"
           class="flex items-center gap-3 p-3 bg-primary-50 dark:bg-primary-950 rounded-lg border border-primary-200 dark:border-primary-800 transition-all hover:bg-primary-100 dark:hover:bg-primary-900 hover:shadow-sm group"
           aria-label="Anruf #{{ $record->call_id }} anzeigen">
            <div class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center" aria-hidden="true">
                <x-heroicon-o-phone-arrow-up-right class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-primary-900 dark:text-primary-100 group-hover:underline">
                    Anruf #{{ $record->call_id }}
                </div>
                @if($record->call)
                    <div class="text-xs text-primary-600 dark:text-primary-400">
                        {{ $record->call->duration_sec ? gmdate('i:s', $record->call->duration_sec) : '—' }}
                        @if($record->call->recording_url)
                            <span class="ml-1 inline-flex items-center gap-1">
                                <x-heroicon-o-speaker-wave class="w-3 h-3" />
                                Aufnahme
                            </span>
                        @endif
                    </div>
                @endif
            </div>
            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-primary-400 dark:text-primary-500 group-hover:text-primary-600" />
        </a>
    @endif

    {{-- Related Customer --}}
    @if($hasCustomer)
        <a href="{{ route('filament.admin.resources.customers.edit', $record->customer_id) }}"
           class="flex items-center gap-3 p-3 bg-success-50 dark:bg-success-950 rounded-lg border border-success-200 dark:border-success-800 transition-all hover:bg-success-100 dark:hover:bg-success-900 hover:shadow-sm group"
           aria-label="Kunde {{ $record->customer?->name ?? '#' . $record->customer_id }} bearbeiten">
            <div class="flex-shrink-0 w-10 h-10 bg-success-100 dark:bg-success-900 rounded-full flex items-center justify-center" aria-hidden="true">
                <x-heroicon-o-user-circle class="w-5 h-5 text-success-600 dark:text-success-400" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-success-900 dark:text-success-100 group-hover:underline truncate">
                    {{ $record->customer?->name ?? 'Kunde #' . $record->customer_id }}
                </div>
                @if($record->customer?->email)
                    <div class="text-xs text-success-600 dark:text-success-400 truncate">
                        {{ $record->customer->email }}
                    </div>
                @endif
            </div>
            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-success-400 dark:text-success-500 group-hover:text-success-600" />
        </a>
    @endif

    {{-- Assigned Staff --}}
    @if($hasAssigned)
        <div class="flex items-center gap-3 p-3 bg-warning-50 dark:bg-warning-950 rounded-lg border border-warning-200 dark:border-warning-800" role="article" aria-label="Zugewiesen an {{ $record->assignedTo?->name ?? 'Mitarbeiter' }}">
            <div class="flex-shrink-0 w-10 h-10 bg-warning-100 dark:bg-warning-900 rounded-full flex items-center justify-center" aria-hidden="true">
                <x-heroicon-o-user class="w-5 h-5 text-warning-600 dark:text-warning-400" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs text-warning-600 dark:text-warning-400 uppercase tracking-wide">Zugewiesen an</div>
                <div class="text-sm font-semibold text-warning-900 dark:text-warning-100 truncate">
                    {{ $record->assignedTo?->name ?? 'Mitarbeiter #' . $record->assigned_to }}
                </div>
            </div>
        </div>
    @endif

    {{-- Category --}}
    @if($record->category_id)
        @php
            // Trust eager loading from resolveRecord() - NO fallback DB query (N+1 risk)
            $categoryName = $record->category?->name ?? 'Unbekannt';
        @endphp
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700" role="article" aria-label="Kategorie: {{ $categoryName }}">
            <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center" aria-hidden="true">
                <x-heroicon-o-tag class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kategorie</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $categoryName }}
                </div>
            </div>
        </div>
    @endif

    {{-- No Relations --}}
    @if(!$hasCall && !$hasCustomer && !$hasAssigned && !$record->category_id)
        <div class="text-center py-6 text-gray-500 dark:text-gray-400" role="status">
            <x-heroicon-o-link-slash class="w-8 h-8 mx-auto mb-2 opacity-50" aria-hidden="true" />
            <p class="text-sm">Keine Verknupfungen</p>
        </div>
    @endif
</div>
