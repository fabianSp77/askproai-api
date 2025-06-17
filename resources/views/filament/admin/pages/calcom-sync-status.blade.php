<x-filament-panels::page>
    @php
        $stats = $this->getSyncStatistics();
        $errors = $this->getRecentSyncErrors();
    @endphp
    
    <div class="space-y-6">
        <!-- Statistik-Karten -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Synced -->
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-400/10">
                            <x-heroicon-o-cloud-arrow-down class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gesamt synchronisiert</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['total_synced'], 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Termine mit Cal.com
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Last 24h -->
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 dark:bg-success-400/10">
                            <x-heroicon-o-clock class="h-5 w-5 text-success-600 dark:text-success-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Letzte 24 Stunden</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['synced_last_24h'], 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Updates erhalten
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Pending -->
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-400/10">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ausstehend</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stats['pending_sync'], 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Noch zu synchronisieren
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Failed -->
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $stats['failed_syncs'] > 0 ? 'bg-danger-50 dark:bg-danger-400/10' : 'bg-success-50 dark:bg-success-400/10' }}">
                            <x-heroicon-o-x-circle class="h-5 w-5 {{ $stats['failed_syncs'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fehlgeschlagen</p>
                        <p class="text-3xl font-semibold tracking-tight {{ $stats['failed_syncs'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-950 dark:text-white' }}">
                            {{ number_format($stats['failed_syncs'], 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Sync-Fehler
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sync Info -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sync Status -->
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Synchronisations-Status</h3>
                
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Letzte Synchronisation:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            @if($stats['last_sync'])
                                {{ $stats['last_sync']->format('d.m.Y H:i:s') }}
                                <span class="text-xs text-gray-500">({{ $stats['last_sync']->diffForHumans() }})</span>
                            @else
                                <span class="text-gray-500">Noch nicht synchronisiert</span>
                            @endif
                        </dd>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Nächste geplante Sync:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            @if($stats['next_sync'])
                                {{ $stats['next_sync']->format('d.m.Y H:i:s') }}
                                <span class="text-xs text-gray-500">({{ $stats['next_sync']->diffForHumans() }})</span>
                            @else
                                <span class="text-gray-500">Nicht geplant</span>
                            @endif
                        </dd>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Webhook Status:</dt>
                        <dd class="text-sm font-medium">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-check-circle class="h-4 w-4 text-success-600" />
                                <span class="text-success-600">Aktiv</span>
                            </span>
                        </dd>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">API Verbindung:</dt>
                        <dd class="text-sm font-medium">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-check-circle class="h-4 w-4 text-success-600" />
                                <span class="text-success-600">Verbunden</span>
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Recent Errors -->
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Letzte Fehler</h3>
                
                @if(count($errors) > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($errors as $error)
                            <div class="p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                                <div class="flex items-start gap-2">
                                    <x-heroicon-o-exclamation-circle class="h-5 w-5 text-danger-600 dark:text-danger-400 flex-shrink-0 mt-0.5" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-danger-800 dark:text-danger-200">
                                            {{ $error['job_name'] }}
                                        </p>
                                        <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">
                                            {{ $error['failed_at']->format('d.m.Y H:i:s') }}
                                        </p>
                                        <p class="text-xs text-danger-700 dark:text-danger-300 mt-1 break-words">
                                            {{ $error['exception'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-success-400" />
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Keine Fehler in den letzten 24 Stunden
                        </p>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Recent Synced Appointments Table -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="px-6 py-4">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Kürzlich synchronisierte Termine
                </h3>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>