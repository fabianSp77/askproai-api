<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <div class="fi-section-header-heading flex-1">
            <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Analyse
            </h3>
            <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                Sentiment und Terminbuchungen
            </p>
        </div>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stimmung</dt>
                    <dd class="mt-1">
                        @php
                            $sentimentColor = match($record->sentiment) {
                                'positive' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'negative' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'neutral' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                            };
                            $sentimentLabel = match($record->sentiment) {
                                'positive' => 'Positiv',
                                'negative' => 'Negativ',
                                'neutral' => 'Neutral',
                                default => $record->sentiment ?? 'Unbekannt'
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $sentimentColor }}">
                            {{ $sentimentLabel }}
                        </span>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Termin gebucht</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $record->appointment_made ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                            {{ $record->appointment_made ? 'Ja' : 'Nein' }}
                        </span>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Termin angefragt</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $record->appointment_requested ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                            {{ $record->appointment_requested ? 'Ja' : 'Nein' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>