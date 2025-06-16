<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 mb-6">
    <div class="text-center">
        <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-800/30 rounded-full flex items-center justify-center mb-4">
            <x-heroicon-o-check-circle class="w-10 h-10 text-green-600 dark:text-green-400" />
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Glückwunsch! Ihre Einrichtung ist abgeschlossen.
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Ihr AskProAI-System ist jetzt einsatzbereit. Ihre Kunden können ab sofort 
            telefonisch Termine buchen - rund um die Uhr, 7 Tage die Woche.
        </p>
        
        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-primary-600">
                    {{ auth()->user()->company->branches()->count() }}
                </div>
                <div class="text-sm text-gray-500">Standorte</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-primary-600">
                    {{ auth()->user()->company->staff()->count() }}
                </div>
                <div class="text-sm text-gray-500">Mitarbeiter</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-primary-600">
                    {{ auth()->user()->company->services()->count() }}
                </div>
                <div class="text-sm text-gray-500">Dienstleistungen</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-primary-600">
                    24/7
                </div>
                <div class="text-sm text-gray-500">Verfügbarkeit</div>
            </div>
        </div>
    </div>
</div>