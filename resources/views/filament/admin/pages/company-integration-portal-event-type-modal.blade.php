{{-- Branch Event Type Management Modal --}}
<div
    x-data="{ open: false }"
    x-init="
        $watch('open', value => console.log('Event Type Modal open state:', value));
        Livewire.on('closeBranchEventTypeModal', () => { open = false; });
        $wire.$watch('showBranchEventTypeModal', value => { open = value; });
    "
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    aria-modal="true"
>
    <div class="flex items-center justify-center min-h-screen p-4">
        {{-- Background overlay --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="open = false"
        ></div>

        {{-- Modal panel --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white dark:bg-gray-900 rounded-lg text-left shadow-xl transform transition-all w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
        >
            <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white flex items-center justify-between" id="modal-title">
                            <span>Event Types verwalten</span>
                            <button
                                @click="open = false"
                                type="button"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <x-heroicon-m-x-mark class="h-6 w-6" />
                            </button>
                        </h3>
                        
                        @if($currentBranchId && isset($branches[array_search($currentBranchId, array_column($branches, 'id'))]))
                            @php
                                $currentBranch = $branches[array_search($currentBranchId, array_column($branches, 'id'))];
                            @endphp
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Filiale: <span class="font-medium">{{ $currentBranch['name'] }}</span>
                            </p>
                        @endif

                        <div class="mt-6 space-y-6">
                            {{-- Assigned Event Types --}}
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    Zugeordnete Event Types
                                </h4>
                                
                                @if(!empty($branchEventTypes[$currentBranchId]))
                                    <div class="space-y-2">
                                        @foreach($branchEventTypes[$currentBranchId] as $eventType)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div class="flex items-center space-x-3">
                                                    <button
                                                        wire:click="setPrimaryEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                                        class="flex-shrink-0"
                                                        title="{{ $eventType['is_primary'] ? 'Ist bereits Primary' : 'Als Primary setzen' }}"
                                                    >
                                                        @if($eventType['is_primary'])
                                                            <x-heroicon-m-star class="w-5 h-5 text-yellow-500" />
                                                        @else
                                                            <x-heroicon-o-star class="w-5 h-5 text-gray-400 hover:text-yellow-500" />
                                                        @endif
                                                    </button>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $eventType['name'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $eventType['duration'] }} Minuten • Cal.com ID: {{ $eventType['calcom_id'] }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <button
                                                    wire:click="removeBranchEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                                    class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                    title="Event Type entfernen"
                                                >
                                                    <x-heroicon-m-x-circle class="w-5 h-5" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                        Keine Event Types zugeordnet
                                    </p>
                                @endif
                            </div>

                            {{-- Add New Event Type --}}
                            @if(!empty($availableEventTypes))
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                        Event Type hinzufügen
                                    </h4>
                                    
                                    <div class="flex gap-2">
                                        <select
                                            wire:model.live="selectedEventTypeToAdd"
                                            class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        >
                                            <option value="">Event Type auswählen...</option>
                                            @foreach($availableEventTypes as $eventType)
                                                <option value="{{ $eventType['id'] }}">
                                                    {{ $eventType['name'] }} ({{ $eventType['duration'] }} Min.)
                                                </option>
                                            @endforeach
                                        </select>
                                        
                                        <x-filament::button
                                            wire:click="addSelectedEventType"
                                            wire:loading.attr="disabled"
                                            size="sm"
                                            icon="heroicon-m-plus"
                                        >
                                            <span wire:loading.remove>Hinzufügen</span>
                                            <span wire:loading>Lädt...</span>
                                        </x-filament::button>
                                    </div>
                                    
                                    {{-- Debug Info --}}
                                    @if(config('app.debug'))
                                        <div class="mt-2 text-xs text-gray-500">
                                            Debug: Selected ID = {{ $selectedEventTypeToAdd ?? 'null' }}, 
                                            Available Count = {{ count($availableEventTypes) }},
                                            Current Branch = {{ $currentBranchId ?? 'null' }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                        Alle verfügbaren Event Types sind bereits zugeordnet.
                                    </p>
                                </div>
                            @endif

                            {{-- Info Box --}}
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                            Hinweise zur Event Type Verwaltung
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>Jede Filiale kann mehrere Event Types haben</li>
                                                <li>Ein Event Type muss als Primary markiert sein</li>
                                                <li>Staff-Zuordnungen erfolgen über Cal.com</li>
                                                <li>Event Types werden von Cal.com synchronisiert</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <x-filament::button
                    wire:click="closeBranchEventTypeModal"
                    wire:loading.attr="disabled"
                    color="gray"
                >
                    Schließen
                </x-filament::button>
            </div>
        </div>
    </div>
</div>