<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Progress Steps -->
        <div class="flex items-center justify-center space-x-4">
            @for ($i = 1; $i <= 4; $i++)
                <div class="flex items-center">
                    <div @class([
                        'w-10 h-10 rounded-full flex items-center justify-center font-semibold',
                        'bg-primary-600 text-white' => $i <= $currentStep,
                        'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $i > $currentStep,
                    ])>
                        {{ $i }}
                    </div>
                    @if ($i < 4)
                        <div @class([
                            'w-20 h-0.5 mx-2',
                            'bg-primary-600' => $i < $currentStep,
                            'bg-gray-200 dark:bg-gray-700' => $i >= $currentStep,
                        ])></div>
                    @endif
                </div>
            @endfor
        </div>
        
        <!-- Step Title -->
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100">
            {{ $this->stepTitle }}
        </h2>
        
        <!-- Step Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @if ($currentStep === 1)
                <!-- Step 1: Company & Branch Selection -->
                <form wire:submit.prevent="">
                    {{ $this->form }}
                </form>
                
            @elseif ($currentStep === 2)
                <!-- Step 2: Event-Types Preview -->
                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>Gefundene Event-Types: <strong>{{ count($eventTypesPreview) }}</strong></p>
                        <p class="mt-2">Event-Types werden nach dem Schema "Filial-Unternehmen-Dienstleistung" analysiert.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <input type="checkbox" 
                                               wire:click="toggleAllSelections"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                               @if(count(array_filter($importSelections)) === count($eventTypesPreview) && count($eventTypesPreview) > 0) checked @endif>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Original Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Analyse
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($eventTypesPreview as $index => $preview)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="checkbox" 
                                                   wire:model.live="importSelections.{{ $index }}"
                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                   @if($preview['suggested_action'] === 'skip') disabled @endif>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $preview['original']['title'] ?? $preview['original']['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Dauer: {{ $preview['original']['length'] ?? 30 }} Min.
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($preview['parsed']['success'])
                                                <div class="text-sm">
                                                    <div>Filiale: <span class="font-medium">{{ $preview['parsed']['branch_name'] }}</span></div>
                                                    <div>Service: <span class="font-medium">{{ $preview['parsed']['service_name'] }}</span></div>
                                                </div>
                                            @else
                                                <div class="text-sm text-red-600 dark:text-red-400">
                                                    {{ $preview['parsed']['error'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($preview['matches_branch'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Passt zur Filiale
                                                </span>
                                            @elseif($preview['suggested_action'] === 'manual')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                    Manuelle Zuordnung
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    Andere Filiale
                                                </span>
                                            @endif
                                            
                                            @if($preview['warning'])
                                                <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                                    {{ $preview['warning'] }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
            @elseif ($currentStep === 3)
                <!-- Step 3: Mapping Correction -->
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Überprüfen und korrigieren Sie bei Bedarf die Service-Namen. Diese werden für die Benennung in AskProAI verwendet.
                    </p>
                    
                    <div class="space-y-3">
                        @foreach($mappings as $index => $mapping)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="grid grid-cols-3 gap-4 items-center">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Original</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $mapping['original_name'] }}</p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Service Name</label>
                                        <input type="text" 
                                               wire:model="mappings.{{ $index }}.service_name"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm">
                                    </div>
                                    
                                    <div class="flex items-center justify-end">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   wire:model="mappings.{{ $index }}.import"
                                                   class="rounded mr-2">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Importieren</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Wird importiert als: <strong>{{ $this->branch_id ? \App\Models\Branch::find($this->branch_id)->name . '-' . \App\Models\Company::find($this->company_id)->name . '-' . $mapping['service_name'] : '' }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
            @elseif ($currentStep === 4)
                <!-- Step 4: Import Confirmation -->
                <div class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">Import-Zusammenfassung</h3>
                        
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Unternehmen:</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $importSummary['company'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Zielfiliale:</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $importSummary['branch'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Gefundene Event-Types:</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $importSummary['total_found'] ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Zu importieren:</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $importSummary['total_mapped'] ?? 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Folgende Event-Types werden importiert:</h4>
                        <ul class="space-y-2">
                            @foreach($importSummary['event_types'] ?? [] as $eventType)
                                <li class="flex items-center text-sm">
                                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                    <span class="text-gray-900 dark:text-gray-100">
                                        {{ $eventType['name'] }} 
                                        <span class="text-gray-500 dark:text-gray-400">({{ $eventType['duration'] }} Min.)</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <div class="bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>Hinweis:</strong> Nach dem Import werden die Event-Types der ausgewählten Filiale zugeordnet. 
                            Mitarbeiter-Zuordnungen können Sie anschließend über die Mitarbeiter-Zuordnungsseite vornehmen.
                        </p>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Navigation Buttons -->
        <div class="flex justify-between">
            <!-- Debug Info -->
            @if(config('app.debug'))
            <div class="mb-4 text-xs text-gray-500">
                Debug: company_id = {{ $data['company_id'] ?? 'null' }}, branch_id = {{ $data['branch_id'] ?? 'null' }}, canProceed = {{ $this->canProceed ? 'true' : 'false' }}
                @if($currentStep === 2)
                    <br>Selected count: {{ count(array_filter($importSelections)) }}, Total: {{ count($importSelections) }}
                @endif
            </div>
            @endif
            
            <div>
                @if($currentStep > 1)
                    <x-filament::button
                        wire:click="previousStep"
                        color="gray"
                        icon="heroicon-o-arrow-left"
                    >
                        Zurück
                    </x-filament::button>
                @endif
            </div>
            
            <div>
                @if($currentStep < 4)
                    <x-filament::button
                        wire:click="nextStep"
                        :disabled="!$this->canProceed"
                        icon-position="after"
                        icon="heroicon-o-arrow-right"
                    >
                        Weiter
                    </x-filament::button>
                @else
                    <x-filament::button
                        wire:click="executeImport"
                        wire:loading.attr="disabled"
                        color="success"
                        icon="heroicon-o-check"
                    >
                        <span wire:loading.remove>Import starten</span>
                        <span wire:loading>Importiere...</span>
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>