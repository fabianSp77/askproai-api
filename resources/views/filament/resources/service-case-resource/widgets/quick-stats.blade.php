@php
    use App\Models\ServiceCase;

    $record = $getRecord();

    // Calculate age (cast to int to avoid float deprecation warnings)
    $ageHours = (int) $record->created_at->diffInHours(now());
    $ageDays = (int) floor($ageHours / 24);
    $remainingHours = $ageHours % 24;

    // Enrichment status
    $enrichmentConfig = match($record->enrichment_status ?? 'pending') {
        ServiceCase::ENRICHMENT_ENRICHED => ['color' => 'success', 'label' => 'Angereichert', 'icon' => 'heroicon-o-check-circle'],
        ServiceCase::ENRICHMENT_PENDING => ['color' => 'warning', 'label' => 'Ausstehend', 'icon' => 'heroicon-o-clock'],
        ServiceCase::ENRICHMENT_TIMEOUT => ['color' => 'danger', 'label' => 'Timeout', 'icon' => 'heroicon-o-x-circle'],
        ServiceCase::ENRICHMENT_SKIPPED => ['color' => 'gray', 'label' => 'Übersprungen', 'icon' => 'heroicon-o-minus-circle'],
        default => ['color' => 'gray', 'label' => 'Unbekannt', 'icon' => 'heroicon-o-question-mark-circle'],
    };
@endphp

<div class="space-y-3" role="region" aria-label="Kurzinfo und Statistiken">
    {{-- Priority --}}
    @php
        $priorityConfig = match($record->priority) {
            ServiceCase::PRIORITY_CRITICAL => ['color' => 'danger', 'label' => 'Kritisch', 'icon' => 'heroicon-o-fire', 'pulse' => true],
            ServiceCase::PRIORITY_HIGH => ['color' => 'warning', 'label' => 'Hoch', 'icon' => 'heroicon-o-exclamation-triangle', 'pulse' => false],
            ServiceCase::PRIORITY_NORMAL => ['color' => 'primary', 'label' => 'Normal', 'icon' => 'heroicon-o-minus', 'pulse' => false],
            ServiceCase::PRIORITY_LOW => ['color' => 'gray', 'label' => 'Niedrig', 'icon' => 'heroicon-o-arrow-down', 'pulse' => false],
            default => ['color' => 'gray', 'label' => 'Unbekannt', 'icon' => 'heroicon-o-question-mark-circle', 'pulse' => false],
        };
    @endphp
    <div @class([
        'flex items-center justify-between p-3 rounded-lg transition-colors',
        'bg-red-50 dark:bg-red-950 priority-critical' => $record->priority === ServiceCase::PRIORITY_CRITICAL,
        'bg-yellow-50 dark:bg-yellow-950' => $record->priority === ServiceCase::PRIORITY_HIGH,
        'bg-gray-50 dark:bg-gray-800' => !in_array($record->priority, [ServiceCase::PRIORITY_CRITICAL, ServiceCase::PRIORITY_HIGH]),
    ]) role="article" aria-label="Priorität: {{ $priorityConfig['label'] }}">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <x-dynamic-component :component="$priorityConfig['icon']" @class([
                'w-5 h-5',
                'text-red-600 dark:text-red-400' => $record->priority === ServiceCase::PRIORITY_CRITICAL,
                'text-yellow-600 dark:text-yellow-400' => $record->priority === ServiceCase::PRIORITY_HIGH,
            ]) aria-hidden="true" />
            <span class="text-sm font-medium">Priorität</span>
        </div>
        <x-filament::badge :color="$priorityConfig['color']" size="sm" aria-label="Prioritätsstufe {{ $priorityConfig['label'] }}">
            {{ $priorityConfig['label'] }}
        </x-filament::badge>
    </div>

    {{-- Case Age --}}
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-750" role="article" aria-label="Fall-Alter: {{ $ageDays > 0 ? $ageDays . ' Tage ' . $remainingHours . ' Stunden' : $ageHours . ' Stunden' }}">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-clock class="w-5 h-5" aria-hidden="true" />
            <span class="text-sm font-medium">Alter</span>
        </div>
        <div class="text-right">
            <div class="font-bold text-gray-900 dark:text-white">
                @if($ageDays > 0)
                    <span class="text-lg">{{ $ageDays }}</span><span class="text-xs text-gray-500">d</span>
                    <span class="text-lg ml-1">{{ $remainingHours }}</span><span class="text-xs text-gray-500">h</span>
                @else
                    <span class="text-lg">{{ $ageHours }}</span><span class="text-xs text-gray-500">h</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Output Status --}}
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-750" role="article" aria-label="Output-Status">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-paper-airplane class="w-5 h-5" aria-hidden="true" />
            <span class="text-sm font-medium">Output</span>
        </div>
        @php
            $outputColor = match($record->output_status) {
                ServiceCase::OUTPUT_SENT => 'success',
                ServiceCase::OUTPUT_FAILED => 'danger',
                default => 'warning',
            };
            $outputLabel = match($record->output_status) {
                ServiceCase::OUTPUT_SENT => 'Gesendet',
                ServiceCase::OUTPUT_FAILED => 'Fehler',
                default => 'Ausstehend',
            };
        @endphp
        <x-filament::badge :color="$outputColor" size="sm">
            {{ $outputLabel }}
        </x-filament::badge>
    </div>

    {{-- Enrichment Status --}}
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-750" role="article" aria-label="Anreicherungsstatus: {{ $enrichmentConfig['label'] }}">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-sparkles class="w-5 h-5" aria-hidden="true" />
            <span class="text-sm font-medium">Anreicherung</span>
        </div>
        <x-filament::badge :color="$enrichmentConfig['color']" size="sm">
            {{ $enrichmentConfig['label'] }}
        </x-filament::badge>
    </div>

    {{-- Last Update --}}
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-750" role="article" aria-label="Letzte Aktualisierung: {{ $record->updated_at->diffForHumans() }}">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <x-heroicon-o-arrow-path class="w-5 h-5" aria-hidden="true" />
            <span class="text-sm font-medium">Aktualisiert</span>
        </div>
        <div class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $record->updated_at->diffForHumans() }}
        </div>
    </div>

    {{-- Transcript Stats (if enriched) --}}
    @if($record->enrichment_status === ServiceCase::ENRICHMENT_ENRICHED && $record->transcript_segment_count)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-750" role="article" aria-label="Transkript mit {{ $record->transcript_segment_count }} Segmenten">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <x-heroicon-o-document-text class="w-5 h-5" aria-hidden="true" />
                <span class="text-sm font-medium">Transcript</span>
            </div>
            <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $record->transcript_segment_count }} Segmente
            </div>
        </div>
    @endif
</div>
