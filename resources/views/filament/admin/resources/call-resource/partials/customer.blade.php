<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <div class="fi-section-header-heading flex-1">
            <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Kunde
            </h3>
            <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                Kundeninformationen
            </p>
        </div>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            @if($record->customer)
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            @if(\App\Filament\Admin\Resources\CustomerResource::canView($record->customer))
                                <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$record->customer]) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline">
                                    {{ $record->customer->name }}
                                </a>
                            @else
                                {{ $record->customer->name }}
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-Mail</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->customer->email ?? '—' }}
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $record->customer->phone ?? '—' }}
                        </dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Kein Kunde zugeordnet</p>
            @endif
        </div>
    </div>
</div>