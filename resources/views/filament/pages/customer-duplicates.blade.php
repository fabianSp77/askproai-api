<div class="space-y-4">
    {{-- Current Customer --}}
    <div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 p-4 border-2 border-warning-500">
        <div class="flex items-center gap-2 mb-2">
            <span class="font-bold text-warning-700 dark:text-warning-400">AKTUELL ANGEZEIGT</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-semibold">Name:</span> {{ $current->name }}
            </div>
            <div>
                <span class="font-semibold">Kundennr:</span> {{ $current->customer_number ?? 'N/A' }}
            </div>
            <div>
                <span class="font-semibold">Telefon:</span> {{ $current->phone ?? 'N/A' }}
            </div>
            <div>
                <span class="font-semibold">E-Mail:</span> {{ $current->email ?? 'N/A' }}
            </div>
            <div>
                <span class="font-semibold">Firma:</span> {{ $current->company->name ?? 'N/A' }}
            </div>
            <div>
                <span class="font-semibold">Erstellt:</span> {{ $current->created_at->format('d.m.Y') }}
            </div>
            <div>
                <span class="font-semibold">Termine:</span> {{ $current->appointment_count }}
            </div>
            <div>
                <span class="font-semibold">Anrufe:</span> {{ $current->calls()->count() }}
            </div>
        </div>
    </div>

    {{-- Duplicates --}}
    <div class="space-y-3">
        <h3 class="text-lg font-semibold">Mögliche Duplikate ({{ $duplicates->count() }})</h3>

        @foreach($duplicates as $duplicate)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">{{ $duplicate->name }}</span>
                    @if($duplicate->phone === $current->phone)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                            </svg>
                            Gleiche Telefonnummer
                        </span>
                    @endif
                    @if($duplicate->email === $current->email && $current->email)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            Gleiche E-Mail
                        </span>
                    @endif
                </div>
                <a href="{{ route('filament.admin.resources.customers.view', $duplicate->id) }}"
                   target="_blank"
                   class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
                    Ansehen
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-400">
                <div>
                    <span class="font-medium">Kundennr:</span> {{ $duplicate->customer_number ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Telefon:</span> {{ $duplicate->phone ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">E-Mail:</span> {{ $duplicate->email ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Firma:</span> {{ $duplicate->company->name ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Erstellt:</span> {{ $duplicate->created_at->format('d.m.Y') }}
                </div>
                <div>
                    <span class="font-medium">Termine:</span> {{ $duplicate->appointment_count }}
                </div>
                <div>
                    <span class="font-medium">Anrufe:</span> {{ $duplicate->calls()->count() }}
                </div>
                <div>
                    <span class="font-medium">Umsatz:</span> €{{ number_format($duplicate->total_revenue, 2) }}
                </div>
            </div>

            {{-- Comparison Warnings --}}
            @php
                $warnings = [];
                if ($duplicate->company_id !== $current->company_id) {
                    $warnings[] = '⚠️ Unterschiedliche Firmen - vorsichtig beim Zusammenführen!';
                }
                if ($duplicate->appointment_count > 0 && $current->appointment_count > 0) {
                    $warnings[] = '⚠️ Beide Kunden haben Termine - Merge könnte komplex sein';
                }
                if ($duplicate->created_at < $current->created_at) {
                    $warnings[] = '💡 Dieser Kunde ist älter - eventuell Hauptdatensatz';
                }
            @endphp

            @if(count($warnings) > 0)
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
                    @foreach($warnings as $warning)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $warning }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Actions Info --}}
    <div class="rounded-lg bg-success-50 dark:bg-success-900/20 p-4 border border-success-200 dark:border-success-800">
        <div class="flex gap-2">
            <svg class="w-5 h-5 text-success-600 dark:text-success-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-sm">
                <p class="font-semibold text-success-800 dark:text-success-200 mb-1">✅ Zusammenführen verfügbar!</p>
                <ul class="list-disc list-inside space-y-1 text-success-700 dark:text-success-300">
                    <li><strong>Automatisches Merge:</strong> Schließen Sie diese Modal und nutzen Sie die "Duplikat #X zusammenführen" Buttons im Header</li>
                    <li><strong>Sicher:</strong> Alle Anrufe, Termine und Notizen werden automatisch übertragen</li>
                    <li><strong>Transaktionssicher:</strong> Bei Fehler wird alles zurückgerollt</li>
                    <li><strong>Audit Trail:</strong> Merge wird in Notizen dokumentiert</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Warning --}}
    <div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 p-4 border border-warning-200 dark:border-warning-800">
        <div class="flex gap-2">
            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-sm">
                <p class="font-semibold text-warning-800 dark:text-warning-200 mb-1">⚠️ Wichtige Hinweise</p>
                <ul class="list-disc list-inside space-y-1 text-warning-700 dark:text-warning-300">
                    <li><strong>Nicht rückgängig:</strong> Merge kann nicht zurückgesetzt werden (nur aus Backup)</li>
                    <li><strong>Firmen prüfen:</strong> Nur Kunden der gleichen Firma können zusammengeführt werden</li>
                    <li><strong>Bei Unsicherheit:</strong> Telefonnummer/E-Mail manuell korrigieren statt mergen</li>
                </ul>
            </div>
        </div>
    </div>
</div>
