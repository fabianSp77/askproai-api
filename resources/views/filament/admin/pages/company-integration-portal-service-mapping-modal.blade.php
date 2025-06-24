{{-- Service-EventType Mapping Modal --}}
<div x-data="{ 
    showServiceMappingModal: @entangle('showServiceMappingModal').live
}"
    x-show="showServiceMappingModal"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div x-show="showServiceMappingModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
            @click="showServiceMappingModal = false"
            aria-hidden="true"></div>

        {{-- Modal panel --}}
        <div x-show="showServiceMappingModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            
            {{-- Header --}}
            <div class="mb-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Service mit Event Type verknüpfen
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Erstellen Sie eine Zuordnung zwischen einem Service und einem Cal.com Event Type für intelligente Terminbuchung.
                </p>
            </div>

            {{-- Form --}}
            <div class="space-y-4">
                {{-- Service Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Service auswählen
                    </label>
                    <select wire:model.live="selectedServiceId" 
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                        <option value="">Bitte wählen...</option>
                        @foreach($availableServices as $service)
                            <option value="{{ $service['id'] }}">
                                {{ $service['name'] }}
                                @if($service['branch_id'])
                                    (Filial-spezifisch)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Event Type Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Cal.com Event Type auswählen
                    </label>
                    <select wire:model.live="selectedMappingEventTypeId" 
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                        <option value="">Bitte wählen...</option>
                        @foreach($availableMappingEventTypes as $eventType)
                            <option value="{{ $eventType['id'] }}">
                                {{ $eventType['name'] }} ({{ $eventType['duration'] }} Min.)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Keywords (Optional) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Keywords für besseres Matching (optional)
                    </label>
                    <input type="text" 
                        wire:model="mappingKeywords"
                        placeholder="z.B. Beratung, Erstgespräch, Consultation"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Komma-getrennte Keywords, die bei der Suche helfen
                    </p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
                <x-filament::button
                    wire:click="createServiceMapping"
                    wire:loading.attr="disabled"
                    :disabled="!$selectedServiceId || !$selectedMappingEventTypeId"
                >
                    Zuordnung erstellen
                </x-filament::button>
                
                <x-filament::button
                    color="gray"
                    wire:click="$set('showServiceMappingModal', false)"
                >
                    Abbrechen
                </x-filament::button>
            </div>
        </div>
    </div>
</div>