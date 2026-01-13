{{-- Vereinfachtes Template basierend auf Filament Standard --}}
{{-- Die komplexen x-data Keyboard-Shortcuts wurden entfernt da sie den Blade-Parser stören --}}
<x-filament-panels::page
    class="fi-resource-view-record-page fi-resource-{{ str_replace('/', '-', $this->getResource()::getSlug()) }} service-case-view-page"
>
    {{-- Skip Link for Accessibility --}}
    <a href="#main-content" class="skip-to-content">Zum Hauptinhalt springen</a>

    {{-- ServiceNow-Style Enhanced Header --}}
    @include('filament.resources.service-case-resource.components.case-header', [
        'record' => $this->record
    ])

    {{-- Main Content with Split Layout Infolist --}}
    <div id="main-content" class="service-case-content mt-6">
        {{ $this->infolist }}
    </div>

    {{-- NOTE: Footer Widgets (Activity Timeline) are rendered by the parent
         x-filament-panels::page component automatically. Do NOT render them
         here again to avoid duplication. --}}

    {{-- Relation Managers (if any) --}}
    @if (count($relationManagers = $this->getRelationManagers()))
        <x-filament-panels::resources.relation-managers
            :active-manager="$this->activeRelationManager"
            :managers="$relationManagers"
            :owner-record="$record"
            :page-class="static::class"
        />
    @endif
</x-filament-panels::page>
