<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <div class="fi-section-header-heading flex-1">
            <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Anruf-Details
            </h3>
            <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                Informationen zu diesem Anruf
            </p>
        </div>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anruf ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">#{{ $record->id }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Datum & Zeit</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->created_at?->format('d.m.Y H:i:s') ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dauer</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->duration_sec ? gmdate('i:s', $record->duration_sec) . ' Min' : '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anrufer</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->from_number ?? 'Unbekannt' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Angerufene Nummer</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->to_number ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusColor = match($record->call_status) {
                                'ended', 'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'error' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $statusColor }}">
                            {{ $record->call_status ?? 'Unbekannt' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>