<div>
    <div class="fi-wi-widget">
        <div class="fi-wi-header mb-6">
            <h2 class="fi-wi-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Prepaid Guthaben Übersicht
            </h2>
        </div>
        
        <!-- Hauptstatistiken -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Gesamtguthaben -->
            <div class="fi-wi-stats-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-stat-icon fi-color-custom flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: rgba(34, 197, 94, 0.1)">
                        <x-heroicon-o-currency-euro class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </span>
                    <div class="flex-1">
                        <p class="fi-wi-stats-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                            Gesamtguthaben
                        </p>
                        <p class="fi-wi-stats-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($totalBalance, 2, ',', '.') }} €
                        </p>
                    </div>
                </div>
                @if($totalReserved > 0)
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Reserviert: {{ number_format($totalReserved, 2, ',', '.') }} €
                </p>
                @endif
            </div>
            
            <!-- Niedrige Guthaben -->
            <div class="fi-wi-stats-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-stat-icon fi-color-custom flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: rgba(251, 146, 60, 0.1)">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </span>
                    <div class="flex-1">
                        <p class="fi-wi-stats-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                            Niedrige Guthaben
                        </p>
                        <p class="fi-wi-stats-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $companiesWithLowBalance }}
                        </p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Unter Warnschwelle
                </p>
            </div>
            
            <!-- Heutige Transaktionen -->
            <div class="fi-wi-stats-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-stat-icon fi-color-custom flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: rgba(59, 130, 246, 0.1)">
                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </span>
                    <div class="flex-1">
                        <p class="fi-wi-stats-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                            Heute
                        </p>
                        <div class="flex items-baseline gap-x-2">
                            <span class="text-sm font-semibold text-success-600 dark:text-success-400">
                                +{{ number_format($todayCredits, 2, ',', '.') }} €
                            </span>
                            <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">
                                -{{ number_format($todayDebits, 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Netto: {{ number_format($todayCredits - $todayDebits, 2, ',', '.') }} €
                </p>
            </div>
            
            <!-- Auto-Aufladung -->
            <div class="fi-wi-stats-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <span class="fi-wi-stats-stat-icon fi-color-custom flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: rgba(168, 85, 247, 0.1)">
                        <x-heroicon-o-arrow-path class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </span>
                    <div class="flex-1">
                        <p class="fi-wi-stats-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                            Auto-Aufladung
                        </p>
                        <p class="fi-wi-stats-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $autoTopupEnabled }}
                        </p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Aktiviert
                </p>
            </div>
        </div>
        
        <!-- Zwei Spalten Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Verbraucher -->
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header px-6 py-4">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Top Verbraucher ({{ now()->format('F Y') }})
                    </h3>
                </div>
                <div class="fi-section-content px-6 pb-6">
                    @if($topConsumers->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Keine Verbrauchsdaten für diesen Monat.
                        </p>
                    @else
                        <div class="space-y-3">
                            @foreach($topConsumers as $consumer)
                                <div class="flex items-center justify-between py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $consumer->company_name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">
                                            -{{ number_format($consumer->total_consumption, 2, ',', '.') }} €
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Kritische Guthaben -->
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header px-6 py-4">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Kritische Guthaben (&lt; 10€)
                    </h3>
                </div>
                <div class="fi-section-content px-6 pb-6">
                    @if($criticalBalances->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Keine kritischen Guthaben.
                        </p>
                    @else
                        <div class="space-y-3">
                            @foreach($criticalBalances as $balance)
                                <div class="flex items-center justify-between py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $balance->company->name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Verfügbar: {{ number_format($balance->getEffectiveBalance(), 2, ',', '.') }} €
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold {{ $balance->balance > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400' }}">
                                            {{ number_format($balance->balance, 2, ',', '.') }} €
                                        </p>
                                        @if($balance->reserved_balance > 0)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Reserviert: {{ number_format($balance->reserved_balance, 2, ',', '.') }} €
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('filament.admin.resources.prepaid-balances.index') }}" 
               class="fi-btn fi-btn-size-sm relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-gray text-gray-700 bg-white shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-800 dark:ring-gray-600 dark:hover:bg-gray-700 gap-1 px-2.5 py-1.5 text-sm inline-grid">
                <x-heroicon-m-list-bullet class="fi-btn-icon h-4 w-4" />
                <span>Alle Guthaben anzeigen</span>
            </a>
            
            <a href="{{ route('filament.admin.resources.prepaid-balances.index', ['tableFilters[low_balance][isActive]' => true]) }}" 
               class="fi-btn fi-btn-size-sm relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-warning text-warning-700 bg-warning-50 ring-1 ring-warning-300 hover:bg-warning-100 dark:text-warning-400 dark:bg-warning-950 dark:ring-warning-600 dark:hover:bg-warning-900 gap-1 px-2.5 py-1.5 text-sm inline-grid">
                <x-heroicon-m-exclamation-triangle class="fi-btn-icon h-4 w-4" />
                <span>Niedrige Guthaben</span>
            </a>
        </div>
    </div>
</div>