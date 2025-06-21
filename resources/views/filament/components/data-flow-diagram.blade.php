<div class="rounded-xl">
    {{-- Klare Erklärung oben --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-4">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Die hier angezeigten Event-Types sind <strong>lokale Kopien</strong> aus Ihrer Datenbank.
                </p>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    Sie wurden zuvor aus Cal.com importiert oder manuell angelegt.
                </p>
            </div>
        </div>
    </div>

    {{-- Vereinfachtes Diagramm --}}
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 shadow-inner">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
            So funktioniert der Datenfluss
        </h3>
        
        <div class="max-w-3xl mx-auto">
            {{-- Desktop: Horizontal, Mobile: Vertikal --}}
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4 lg:gap-8">
                {{-- Cal.com --}}
                <div class="flex-1 w-full max-w-xs">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border-2 border-blue-300 dark:border-blue-700 text-center">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-cloud class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white text-lg">Cal.com</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Original-Quelle
                        </p>
                    </div>
                </div>
                
                {{-- Arrow Desktop --}}
                <div class="hidden lg:flex items-center">
                    <div class="flex flex-col items-center">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Import</span>
                        <x-heroicon-o-arrow-right class="w-8 h-8 text-blue-400" />
                    </div>
                </div>
                
                {{-- Arrow Mobile --}}
                <div class="lg:hidden">
                    <div class="flex flex-col items-center">
                        <x-heroicon-o-arrow-down class="w-8 h-8 text-blue-400" />
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium mt-1">Import</span>
                    </div>
                </div>
                
                {{-- AskProAI DB --}}
                <div class="flex-1 w-full max-w-xs">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border-2 border-green-300 dark:border-green-700 text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-circle-stack class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white text-lg">Ihre Datenbank</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Lokale Kopie
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">calcom_event_types</code>
                        </p>
                    </div>
                </div>
                
                {{-- Arrow Desktop --}}
                <div class="hidden lg:flex items-center">
                    <div class="flex flex-col items-center">
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">Lesen</span>
                        <x-heroicon-o-arrow-right class="w-8 h-8 text-green-400" />
                    </div>
                </div>
                
                {{-- Arrow Mobile --}}
                <div class="lg:hidden">
                    <div class="flex flex-col items-center">
                        <x-heroicon-o-arrow-down class="w-8 h-8 text-green-400" />
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium mt-1">Lesen</span>
                    </div>
                </div>
                
                {{-- Setup Wizard --}}
                <div class="flex-1 w-full max-w-xs">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border-2 border-purple-300 dark:border-purple-700 text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white text-lg">Dieser Wizard</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Zeigt lokale Daten
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Zeitstempel Info --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <x-heroicon-o-clock class="inline-block w-4 h-4 mr-1" />
                Daten müssen regelmäßig importiert werden, um aktuell zu bleiben
            </p>
        </div>
    </div>
    
    {{-- Action Box --}}
    <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex items-center justify-between">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Keine Event-Types vorhanden?
                    </p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Nutzen Sie den <a href="/admin/event-type-import-wizard" class="underline font-medium">Import-Wizard</a> um Event-Types aus Cal.com zu importieren.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>