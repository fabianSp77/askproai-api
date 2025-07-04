<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">ML Dashboard - Statistiken</h2>
            
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                    <div class="text-2xl font-bold">{{ number_format($stats['total_calls']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Gesamte Anrufe</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded">
                    <div class="text-2xl font-bold">{{ number_format($stats['calls_with_transcript']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Transkript</div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded">
                    <div class="text-2xl font-bold">{{ number_format($stats['calls_with_predictions']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Analysiert</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-2">Info</h3>
            <p class="text-gray-600 dark:text-gray-400">
                Dies ist eine vereinfachte Version des ML Dashboards ohne komplexe Livewire-Komponenten.
                Die vollständige Version mit Training und Analyse-Funktionen wird noch entwickelt.
            </p>
        </div>
    </div>
</x-filament-panels::page>