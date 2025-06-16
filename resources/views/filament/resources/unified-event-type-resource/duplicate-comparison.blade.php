<div class="space-y-4">
    {{-- Header mit Event Type Name --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
            Duplikat gefunden: {{ $calcomData['title'] ?? 'Unbekannter Event Type' }}
        </h3>
        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
            Dieser Event Type existiert bereits lokal. Bitte überprüfen Sie die Unterschiede:
        </p>
    </div>

    {{-- Vergleichstabelle --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Eigenschaft
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Lokale Daten
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Cal.com Daten
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                {{-- Name/Titel --}}
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Name
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $localData->title ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $calcomData['title'] ?? '-' }}
                    </td>
                    <td class="px-4 py-3">
                        @if(($localData->title ?? '') !== ($calcomData['title'] ?? ''))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Unterschiedlich
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Gleich
                            </span>
                        @endif
                    </td>
                </tr>

                {{-- Slug/URL --}}
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        URL-Slug
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $localData->slug ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $calcomData['slug'] ?? '-' }}
                    </td>
                    <td class="px-4 py-3">
                        @if(($localData->slug ?? '') !== ($calcomData['slug'] ?? ''))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Unterschiedlich
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Gleich
                            </span>
                        @endif
                    </td>
                </tr>

                {{-- Dauer --}}
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Dauer
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $localData->duration ?? '-' }} Minuten
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $calcomData['length'] ?? '-' }} Minuten
                    </td>
                    <td class="px-4 py-3">
                        @if(($localData->duration ?? 0) != ($calcomData['length'] ?? 0))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Unterschiedlich
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Gleich
                            </span>
                        @endif
                    </td>
                </tr>

                {{-- Preis --}}
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Preis
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ number_format($localData->price ?? 0, 2, ',', '.') }} €
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ number_format($calcomData['price'] ?? 0, 2, ',', '.') }} €
                    </td>
                    <td class="px-4 py-3">
                        @if(($localData->price ?? 0) != ($calcomData['price'] ?? 0))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Unterschiedlich
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Gleich
                            </span>
                        @endif
                    </td>
                </tr>

                {{-- Aktiv Status --}}
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Status
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        @if($localData->is_active ?? false)
                            <span class="text-green-600 dark:text-green-400">✓ Aktiv</span>
                        @else
                            <span class="text-red-600 dark:text-red-400">✗ Inaktiv</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        @if(!($calcomData['hidden'] ?? false))
                            <span class="text-green-600 dark:text-green-400">✓ Aktiv</span>
                        @else
                            <span class="text-red-600 dark:text-red-400">✗ Inaktiv</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(($localData->is_active ?? false) !== !($calcomData['hidden'] ?? false))
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Unterschiedlich
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Gleich
                            </span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Zusammenfassung der Unterschiede --}}
    @php
        $differences = [];
        if(($localData->title ?? '') !== ($calcomData['title'] ?? '')) $differences[] = 'Name';
        if(($localData->slug ?? '') !== ($calcomData['slug'] ?? '')) $differences[] = 'URL-Slug';
        if(($localData->duration ?? 0) != ($calcomData['length'] ?? 0)) $differences[] = 'Dauer';
        if(($localData->price ?? 0) != ($calcomData['price'] ?? 0)) $differences[] = 'Preis';
        if(($localData->is_active ?? false) !== !($calcomData['hidden'] ?? false)) $differences[] = 'Status';
    @endphp

    @if(count($differences) > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Gefundene Unterschiede:
                    </h3>
                    <div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($differences as $diff)
                                <li>{{ $diff }} wurde geändert</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        Keine Unterschiede gefunden - die Daten sind identisch.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Technische Details (ausklappbar) --}}
    <details class="border border-gray-200 dark:border-gray-700 rounded-lg">
        <summary class="px-4 py-3 bg-gray-50 dark:bg-gray-800 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
            Technische Details anzeigen
        </summary>
        <div class="p-4 space-y-2 text-xs font-mono text-gray-600 dark:text-gray-400">
            <div>Lokale ID: {{ $localData->id ?? 'N/A' }}</div>
            <div>Cal.com ID: {{ $calcomData['id'] ?? 'N/A' }}</div>
            <div>External ID: {{ $localData->external_id ?? 'N/A' }}</div>
        </div>
    </details>
</div>
