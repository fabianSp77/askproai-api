<x-filament-panels::page>
    <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
        <div class="flex">
            <div class="flex-shrink-0">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-400" />
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Duplikate-Erkennung
                </h3>
                <div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    <p>
                        Diese Seite zeigt Kunden mit identischen E-Mail-Adressen, Telefonnummern oder Namen. 
                        Überprüfen Sie die Einträge sorgfältig, bevor Sie sie zusammenführen.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>