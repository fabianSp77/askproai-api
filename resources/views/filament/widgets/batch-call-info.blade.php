<div class="space-y-4">
    {{-- Current Time & Date --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktuell</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['current_time'] }} Uhr</p>
                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $stats['current_date'] }}</p>
            </div>
            <div class="text-right">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Bereit für Batch</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['ready_for_batch'] }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-300">≈ {{ $stats['estimated_time'] }}</p>
            </div>
        </div>
    </div>

    {{-- Recommended Windows --}}
    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Empfohlene Batch-Zeitfenster
        </h3>
        <div class="space-y-2">
            @foreach($stats['recommended_windows'] as $index => $window)
                <div class="flex items-center p-2 rounded {{ $index === 0 ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-white dark:bg-gray-800' }}">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full {{ $index === 0 ? 'bg-blue-600 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }} text-xs font-bold mr-3">
                        {{ $index + 1 }}
                    </span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $window }}</span>
                    @if($index === 0)
                        <span class="ml-auto text-xs bg-blue-600 text-white px-2 py-1 rounded">Nächstes Fenster</span>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-xs text-blue-800 dark:text-blue-200 mt-3 italic">
            💡 Tipp: Batch-Calls in dedizierten Zeitfenstern reduzieren Unterbrechungen und steigern die Effizienz um bis zu 40%.
        </p>
    </div>

    {{-- Today's Statistics --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
            <h4 class="text-xs font-medium text-green-700 dark:text-green-300 mb-1">Heute erstellt</h4>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $stats['today_created'] }}</p>
        </div>
        <div class="rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-3">
            <h4 class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">Heute abgeschlossen</h4>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $stats['today_completed'] }}</p>
        </div>
    </div>

    {{-- Personal Statistics --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-3">
            <h4 class="text-xs font-medium text-indigo-700 dark:text-indigo-300 mb-1">Meine Callbacks</h4>
            <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ $stats['my_callbacks'] }}</p>
        </div>
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
            <h4 class="text-xs font-medium text-red-700 dark:text-red-300 mb-1">Überfällig (Gesamt)</h4>
            <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $stats['overdue'] }}</p>
        </div>
    </div>

    {{-- Batch Call Workflow Guide --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            Batch-Call Workflow
        </h3>
        <ol class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 text-white text-xs font-bold mr-3 mt-0.5">1</span>
                <span>Wählen Sie einen der empfohlenen Zeitfenster für Ihre Batch-Calls</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 text-white text-xs font-bold mr-3 mt-0.5">2</span>
                <span>Nutzen Sie die Tabs <strong>"Meine Callbacks"</strong> oder <strong>"Nicht zugewiesen"</strong></span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 text-white text-xs font-bold mr-3 mt-0.5">3</span>
                <span>Wählen Sie mehrere Callbacks aus (Checkboxen aktivieren)</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 text-white text-xs font-bold mr-3 mt-0.5">4</span>
                <span>Klicken Sie auf <strong>"Batch-Call starten"</strong> in den Bulk Actions</span>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 text-white text-xs font-bold mr-3 mt-0.5">5</span>
                <span>Arbeiten Sie die Callbacks nacheinander ab - alle werden automatisch aktualisiert</span>
            </li>
        </ol>
    </div>

    {{-- Performance Tip --}}
    <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-3">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-xs text-yellow-800 dark:text-yellow-200">
                <strong>Effizienz-Tipp:</strong> Bearbeiten Sie Callbacks in Blöcken von 5-10 Stück.
                Das reduziert Kontextwechsel und erhöht Ihre Produktivität um durchschnittlich 40%.
            </div>
        </div>
    </div>
</div>
