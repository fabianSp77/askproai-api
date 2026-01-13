@php
    $record = $getRecord();
    $casesCount = $record?->cases()->count() ?? 0;
    $childrenCount = $record?->children()->count() ?? 0;
    $isActive = $record?->is_active ?? false;
    $hasParent = $record?->parent_id !== null;
    $outputConfig = $record?->outputConfiguration;
@endphp

{{-- 🎨 Status Panel für ServiceCaseCategory Edit Form --}}
{{-- Pattern: Übernommen von ServiceOutputConfigurationResource --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
     role="region"
     aria-label="Kategorie-Status Übersicht">

    {{-- 1. Aktiv-Status --}}
    <div class="flex items-center gap-2">
        @if($isActive)
            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" aria-hidden="true" />
            <span class="text-sm font-medium text-success-700 dark:text-success-400">Aktiv</span>
        @else
            <x-heroicon-o-x-circle class="w-5 h-5 text-gray-400" aria-hidden="true" />
            <span class="text-sm text-gray-500 dark:text-gray-400">Inaktiv</span>
        @endif
    </div>

    {{-- 2. Cases Count --}}
    <div class="flex items-center gap-2">
        <x-heroicon-o-ticket class="w-5 h-5 text-info-500" aria-hidden="true" />
        <span class="text-sm text-gray-700 dark:text-gray-300">
            {{ $casesCount }} {{ $casesCount === 1 ? 'Case' : 'Cases' }}
        </span>
    </div>

    {{-- 3. Hierarchie-Status --}}
    <div class="flex items-center gap-2">
        @if($hasParent)
            <x-heroicon-o-folder class="w-5 h-5 text-warning-500" aria-hidden="true" />
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Sub-Kategorie
                @if($record?->parent)
                    <span class="text-xs text-gray-500">({{ $record->parent->name }})</span>
                @endif
            </span>
        @elseif($childrenCount > 0)
            <x-heroicon-o-folder-open class="w-5 h-5 text-primary-500" aria-hidden="true" />
            <span class="text-sm text-gray-700 dark:text-gray-300">
                Root mit {{ $childrenCount }} {{ $childrenCount === 1 ? 'Kind' : 'Kindern' }}
            </span>
        @else
            <x-heroicon-o-folder-open class="w-5 h-5 text-primary-500" aria-hidden="true" />
            <span class="text-sm text-gray-700 dark:text-gray-300">Root-Kategorie</span>
        @endif
    </div>

    {{-- 4. Output-Konfiguration --}}
    <div class="flex items-center gap-2">
        @if($outputConfig)
            <x-heroicon-o-paper-airplane class="w-5 h-5 text-success-500" aria-hidden="true" />
            <span class="text-sm text-gray-700 dark:text-gray-300 truncate" title="{{ $outputConfig->name }}">
                {{ \Illuminate\Support\Str::limit($outputConfig->name, 20) }}
            </span>
        @else
            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" aria-hidden="true" />
            <span class="text-sm text-warning-600 dark:text-warning-400">Keine Ausgabe</span>
        @endif
    </div>
</div>

{{-- SLA Vorschau wenn konfiguriert --}}
@if($record && ($record->sla_response_hours || $record->sla_resolution_hours))
    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-center gap-2 mb-2">
            <x-heroicon-o-clock class="w-4 h-4 text-blue-500" aria-hidden="true" />
            <span class="text-xs font-medium text-blue-700 dark:text-blue-300">SLA-Zeiten</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Reaktion:</span>
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $record->sla_response_hours ?? '-' }}h
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Lösung:</span>
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $record->sla_resolution_hours ?? '-' }}h
                </span>
            </div>
        </div>
    </div>
@endif
