<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <button type="button" 
                onclick="this.closest('.fi-section').querySelector('.fi-section-content-ctn').classList.toggle('hidden')"
                class="fi-section-header-heading flex-1 flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 -mx-6 px-6 -my-4 py-4">
            <div>
                <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Technische Details
                </h3>
                <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                    System-Informationen und Debug-Daten
                </p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10 hidden">
        <div class="fi-section-content p-6">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Retell Call ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                        {{ $record->retell_call_id ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->agent_id ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Firma</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->company?->name ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Filiale</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->branch?->name ?? '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kosten</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->cost ? '€ ' . number_format($record->cost, 2) : '—' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anruftyp</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->call_type ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>