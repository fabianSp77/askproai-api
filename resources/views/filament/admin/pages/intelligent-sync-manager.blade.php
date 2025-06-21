<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Empfehlungen --}}
        @if(count($this->recommendations) > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="flex-1">
                        <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Intelligente Empfehlungen
                        </h3>
                        <p class="fi-section-description text-sm text-gray-600 dark:text-gray-400">
                            Basierend auf Ihrem System-Status
                        </p>
                    </div>
                </div>
                
                <div class="fi-section-content p-6 pt-0">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->recommendations as $recommendation)
                            <div class="relative rounded-lg border {{ $recommendation['priority'] === 'high' ? 'border-danger-300 bg-danger-50' : 'border-gray-200 bg-gray-50' }} p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $recommendation['type'] === 'calls' ? 'Anrufe' : 'Termine' }}
                                        </h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $recommendation['reason'] }}
                                        </p>
                                    </div>
                                    <span class="ml-3 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $recommendation['priority'] === 'high' ? 'bg-danger-100 text-danger-700' : 'bg-primary-100 text-primary-700' }}">
                                        {{ $recommendation['priority'] === 'high' ? 'Hoch' : 'Mittel' }}
                                    </span>
                                </div>
                                
                                @if(isset($recommendation['suggested_filters']))
                                    <button
                                        wire:click="applyRecommendation('{{ $recommendation['type'] }}')"
                                        class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-500"
                                    >
                                        <x-heroicon-m-arrow-right-circle class="h-4 w-4" />
                                        Filter anwenden
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Letzte Synchronisationen --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                <div class="flex-1">
                    <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Letzte Synchronisationen
                    </h3>
                </div>
            </div>
            
            <div class="fi-section-content">
                <div class="grid gap-4 p-6 pt-0 sm:grid-cols-2">
                    {{-- Anrufe --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-900">
                                <x-heroicon-o-phone class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Anrufe</h4>
                                @if($this->lastSyncInfo['calls']['time'])
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($this->lastSyncInfo['calls']['time'])->diffForHumans() }}
                                    </p>
                                    <div class="mt-2 flex gap-4 text-xs">
                                        <span>Gesamt: {{ $this->lastSyncInfo['calls']['stats']['total'] }}</span>
                                        <span class="text-success-600">Neu: {{ $this->lastSyncInfo['calls']['stats']['new'] }}</span>
                                        <span class="text-warning-600">Aktualisiert: {{ $this->lastSyncInfo['calls']['stats']['updated'] }}</span>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500">Noch keine Synchronisation</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Termine --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-success-100 p-2 dark:bg-success-900">
                                <x-heroicon-o-calendar class="h-5 w-5 text-success-600 dark:text-success-400" />
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Termine</h4>
                                @if($this->lastSyncInfo['appointments']['time'])
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($this->lastSyncInfo['appointments']['time'])->diffForHumans() }}
                                    </p>
                                    <div class="mt-2 flex gap-4 text-xs">
                                        <span>Gesamt: {{ $this->lastSyncInfo['appointments']['stats']['total'] }}</span>
                                        <span class="text-success-600">Neu: {{ $this->lastSyncInfo['appointments']['stats']['new'] }}</span>
                                        <span class="text-warning-600">Aktualisiert: {{ $this->lastSyncInfo['appointments']['stats']['updated'] }}</span>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-500">Noch keine Synchronisation</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Sync Forms --}}
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>
        
        {{-- Vorschau Modal für Anrufe --}}
        @if($showCallPreview && !empty($callPreviewData))
            <x-filament::modal
                id="call-preview-modal"
                :visible="$showCallPreview"
                width="xl"
            >
                <x-slot name="heading">
                    Anruf-Synchronisation Vorschau
                </x-slot>
                
                <div class="space-y-4">
                    <div class="rounded-lg bg-primary-50 p-4 dark:bg-primary-900/20">
                        <p class="text-sm font-medium text-primary-900 dark:text-primary-100">
                            {{ $callPreviewData['would_sync'] }} von {{ $callPreviewData['total_available'] }} Anrufen würden synchronisiert
                        </p>
                    </div>
                    
                    @if(isset($callPreviewData['preview']) && count($callPreviewData['preview']) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Zeit</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nummer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dauer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach(array_slice($callPreviewData['preview'], 0, 10) as $call)
                                        <tr class="{{ $call['would_sync'] ? '' : 'opacity-50' }}">
                                            <td class="px-3 py-2 text-sm">{{ $call['start_time']->format('d.m.Y H:i') }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $call['from_number'] }}</td>
                                            <td class="px-3 py-2 text-sm">{{ round($call['duration'] / 1000) }}s</td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $call['status'] === 'new' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $call['status'] === 'new' ? 'Neu' : 'Existiert' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                
                <x-slot name="footer">
                    <x-filament::button
                        wire:click="$set('showCallPreview', false)"
                        color="gray"
                    >
                        Schließen
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        @endif
    </div>
</x-filament-panels::page>