@php
    $serviceCase = $getRecord();
@endphp

<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
    <a href="{{ \App\Filament\Resources\ServiceCaseResource::getUrl('view', ['record' => $serviceCase->id]) }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors"
       target="_blank">
        <span>Ticket vollständig anzeigen</span>
        <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
    </a>
</div>
