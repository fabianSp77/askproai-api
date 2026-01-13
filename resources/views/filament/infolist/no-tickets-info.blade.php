@php
    $record = $getRecord();
    $isServiceDeskCall = ($record->gateway_mode === 'service_desk' || $record->detected_intent === 'service_desk');
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6 text-center">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
        <x-heroicon-o-ticket class="h-6 w-6 text-gray-400 dark:text-gray-500" />
    </div>
    <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">
        Keine Tickets vorhanden
    </h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Für diesen Anruf wurden keine Service Cases erstellt.
    </p>
    @if($isServiceDeskCall)
        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
            <x-heroicon-m-exclamation-triangle class="inline w-4 h-4 mr-1" />
            Dieser Anruf wurde als Service-Desk-Anruf erkannt, aber es wurde kein Ticket erstellt.
        </p>
    @endif
</div>
