<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
        <div class="mb-4 rounded-full bg-warning-50 p-3 dark:bg-warning-500/10">
            <x-filament::icon
                icon="heroicon-o-exclamation-triangle"
                class="h-6 w-6 text-warning-500"
            />
        </div>
        <h4 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
            Steuerliche Angaben fehlen
        </h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Mindestens eine Steuernummer oder USt-IdNr. ist erforderlich, um konforme Rechnungen nach §14 UStG zu erstellen.
        </p>
    </div>
</x-dynamic-component>
