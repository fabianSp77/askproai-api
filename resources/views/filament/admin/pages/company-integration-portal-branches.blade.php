{{-- Enhanced Branch Management Section --}}
<div class="branch-cards-container space-y-4">
    @foreach($branches as $branch)
        <div class="branch-card border-2 {{ $branch['is_active'] ? 'border-gray-200 dark:border-gray-700' : 'border-gray-300 dark:border-gray-600' }} rounded-xl hover:shadow-lg transition-all duration-200">
            {{-- Header Section --}}
            <div class="bg-gradient-to-r {{ $branch['is_active'] ? 'from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750' : 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-650' }} p-4">
                <div class="branch-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3 flex-1">
                        {{-- Branch Name --}}
                        @if($branchEditStates["name_{$branch['id']}"] ?? false)
                            <div class="flex items-center gap-2 flex-1">
                                <input 
                                    type="text"
                                    wire:model="branchNames.{{ $branch['id'] }}"
                                    class="px-3 py-1.5 text-lg font-semibold border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 flex-1"
                                    wire:keydown.enter="saveBranchName({{ $branch['id'] }})"
                                    wire:keydown.escape="toggleBranchNameInput({{ $branch['id'] }})"
                                />
                                <x-filament::button wire:click="saveBranchName({{ $branch['id'] }})" size="sm" color="success">
                                    <x-heroicon-m-check class="w-4 h-4" />
                                </x-filament::button>
                                <x-filament::button wire:click="toggleBranchNameInput({{ $branch['id'] }})" size="sm" color="gray">
                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                </x-filament::button>
                            </div>
                        @else
                            <div class="flex items-center gap-2 group">
                                <x-heroicon-o-building-storefront class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $branch['name'] }}
                                </h3>
                                @if($branch['is_main'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900/20 dark:text-primary-400">
                                        <x-heroicon-m-star class="w-3 h-3 mr-1" /> Hauptfiliale
                                    </span>
                                @endif
                                <button wire:click="toggleBranchNameInput({{ $branch['id'] }})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-opacity">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    <div class="branch-actions flex items-center gap-3">
                        {{-- Status Badge --}}
                        @if($branch['is_configured'])
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                <x-heroicon-m-check-badge class="w-4 h-4 mr-1.5" /> Bereit
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                                <x-heroicon-m-exclamation-triangle class="w-4 h-4 mr-1.5" /> Unvollständig
                            </span>
                        @endif
                        
                        {{-- Active Toggle --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm {{ $branch['is_active'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $branch['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <button
                                wire:click="toggleBranchActiveState({{ $branch['id'] }})"
                                @class([
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                    'bg-green-500' => $branch['is_active'],
                                    'bg-gray-300 dark:bg-gray-700' => !$branch['is_active'],
                                ])
                            >
                                <span @class([
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    'translate-x-5' => $branch['is_active'],
                                    'translate-x-0' => !$branch['is_active'],
                                ])></span>
                            </button>
                        </div>
                        
                        {{-- Actions Menu --}}
                        <div class="branch-dropdown-container" x-data="smartDropdown">
                            <button x-ref="button" @click="toggle()" @click.away="open = false" class="branch-action-button p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                <x-heroicon-m-ellipsis-vertical class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            </button>
                            <div x-ref="dropdown" x-show="open" x-transition class="branch-dropdown-menu bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700" :class="{ 'dropdown-open': open }">
                                <a href="{{ route('filament.admin.resources.branches.edit', $branch['id']) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <x-heroicon-m-cog-6-tooth class="w-4 h-4" /> Erweiterte Einstellungen
                                </a>
                                @if(!$branch['is_main'])
                                    <button wire:click="deleteBranch({{ $branch['id'] }})" wire:confirm="Sind Sie sicher?" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full text-left">
                                        <x-heroicon-m-trash class="w-4 h-4" /> Filiale löschen
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Content Grid --}}
            <div class="branch-card-content p-6 space-y-6">
                {{-- Contact Information Row --}}
                <div class="branch-info-grid">
                    {{-- Address --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 block">Adresse</label>
                        @if($branchEditStates["address_{$branch['id']}"] ?? false)
                            <div class="flex gap-2">
                                <input type="text" wire:model="branchAddresses.{{ $branch['id'] }}" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                                <x-filament::button wire:click="saveBranchAddress({{ $branch['id'] }})" wire:loading.attr="disabled" size="sm" color="success" icon="heroicon-m-check" />
                                <x-filament::button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                            </div>
                        @else
                            <div class="inline-edit-field flex items-center justify-between group">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $branch['address'] ?? 'Keine Adresse hinterlegt' }}</span>
                                <button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" class="inline-edit-button">
                                    <x-heroicon-m-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Email --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 block">E-Mail</label>
                        @if($branchEditStates["email_{$branch['id']}"] ?? false)
                            <div class="flex gap-2">
                                <input type="email" wire:model="branchEmails.{{ $branch['id'] }}" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                                <x-filament::button wire:click="saveBranchEmail({{ $branch['id'] }})" size="sm" color="success" icon="heroicon-m-check" />
                                <x-filament::button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                            </div>
                        @else
                            <div class="inline-edit-field flex items-center justify-between group">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $branch['email'] ?? 'Keine E-Mail hinterlegt' }}</span>
                                <button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" class="inline-edit-button">
                                    <x-heroicon-m-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Phone --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 block">Telefon</label>
                        @if($branchEditStates["phone_{$branch['id']}"] ?? false)
                            <div class="flex gap-2">
                                <input type="tel" wire:model="branchPhoneNumbers.{{ $branch['id'] }}" placeholder="+49 30 12345678" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                                <x-filament::button wire:click="saveBranchPhoneNumber({{ $branch['id'] }})" size="sm" color="success" icon="heroicon-m-check" />
                                <x-filament::button wire:click="toggleBranchPhoneNumberInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                            </div>
                        @else
                            <div class="inline-edit-field flex items-center justify-between group">
                                <div class="flex items-center gap-2">
                                    @if($branch['has_phone'])
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                        <span class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $branch['phone_number'] }}</span>
                                    @else
                                        <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                        <span class="text-sm text-red-600 dark:text-red-400">Keine Telefonnummer</span>
                                    @endif
                                </div>
                                <button wire:click="toggleBranchPhoneNumberInput({{ $branch['id'] }})" class="inline-edit-button">
                                    <x-heroicon-m-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Integration Configuration --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-gray-500" />
                        Integration Konfiguration
                    </h4>
                    
                    <div class="branch-config-grid grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Cal.com Event Types --}}
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                        <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-500" />
                                        Cal.com Event Types
                                    </h5>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Zugeordnete Termintypen</p>
                                </div>
                                @if($branch['event_type_count'] > 0)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        <x-heroicon-m-check class="w-3 h-3 mr-1" /> {{ $branch['event_type_count'] }} {{ $branch['event_type_count'] == 1 ? 'Typ' : 'Typen' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                        <x-heroicon-m-x-mark class="w-3 h-3 mr-1" /> Keine
                                    </span>
                                @endif
                            </div>
                            
                            <div class="mt-3 space-y-2">
                                @if($branch['event_type_count'] > 0)
                                    <div class="flex items-center justify-between">
                                        <div>
                                            @if($branch['primary_event_type_name'])
                                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-primary-100 text-primary-800 dark:bg-primary-900/20 dark:text-primary-400">
                                                        Primary
                                                    </span>
                                                    {{ $branch['primary_event_type_name'] }}
                                                </p>
                                            @endif
                                            @if($branch['event_type_count'] > 1)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    +{{ $branch['event_type_count'] - 1 }} weitere
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Keine Event Types zugeordnet
                                    </p>
                                @endif
                                
                                <button 
                                    wire:click="manageBranchEventTypes('{{ $branch['id'] }}')" 
                                    class="w-full mt-2 px-3 py-2 text-sm font-medium text-primary-600 dark:text-primary-400 border border-primary-600 dark:border-primary-400 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                                >
                                    <x-heroicon-m-cog-6-tooth class="w-4 h-4 inline mr-1" />
                                    Event Types verwalten
                                </button>
                            </div>
                        </div>
                        
                        {{-- Retell Agent --}}
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                        <x-heroicon-o-phone class="w-4 h-4 text-gray-500" />
                                        Retell.ai Agent
                                    </h5>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">KI-Agent für Anrufe</p>
                                </div>
                                @if($branch['has_retell'])
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        <x-heroicon-m-check class="w-3 h-3 mr-1" /> Eigener Agent
                                    </span>
                                @elseif($branch['uses_master_retell'])
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                        <x-heroicon-m-arrow-up class="w-3 h-3 mr-1" /> Master Agent
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                        <x-heroicon-m-x-mark class="w-3 h-3 mr-1" /> Kein Agent
                                    </span>
                                @endif
                            </div>
                            
                            @if($branchEditStates["retell_{$branch['id']}"] ?? false)
                                <div class="flex gap-2 mt-3">
                                    <input type="text" wire:model="branchRetellAgentIds.{{ $branch['id'] }}" placeholder="agent_xxx oder leer für Master" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                                    <x-filament::button wire:click="saveBranchRetellAgent({{ $branch['id'] }})" size="sm" color="success" icon="heroicon-m-check" />
                                    <x-filament::button wire:click="toggleBranchRetellAgentInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                                </div>
                            @else
                                <div class="flex items-center justify-between mt-3 group">
                                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">
                                        @if($branch['has_retell'])
                                            {{ substr($branch['retell_agent_id'], 0, 15) }}...
                                        @elseif($branch['uses_master_retell'])
                                            Nutzt Master Agent
                                        @else
                                            Nicht konfiguriert
                                        @endif
                                    </span>
                                    <button wire:click="toggleBranchRetellAgentInput({{ $branch['id'] }})" class="branch-config-button text-primary-600 hover:text-primary-700 text-sm font-medium">
                                        {{ $branch['has_retell'] ? 'Ändern' : 'Konfigurieren' }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Quick Stats --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1">
                                <x-heroicon-m-users class="w-4 h-4" />
                                {{ $branch['staff_count'] }} Mitarbeiter
                            </span>
                            <span class="flex items-center gap-1">
                                <x-heroicon-m-phone class="w-4 h-4" />
                                {{ $branch['phone_count'] }} {{ $branch['phone_count'] == 1 ? 'Nummer' : 'Nummern' }}
                            </span>
                        </div>
                        <a href="{{ route('filament.admin.resources.branches.edit', $branch['id']) }}" class="text-primary-600 hover:text-primary-700 font-medium">
                            Weitere Einstellungen →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    
    {{-- Add New Branch Button --}}
    <div class="text-center py-8">
        <x-filament::button
            href="{{ route('filament.admin.resources.branches.create') }}"
            tag="a"
            size="lg"
            icon="heroicon-m-plus-circle"
        >
            Neue Filiale hinzufügen
        </x-filament::button>
    </div>
</div>

{{-- Event Type Management Modal --}}
<x-filament::modal id="event-type-modal" wire:model="showEventTypeModal" width="3xl">
    <x-slot name="heading">
        Event Types verwalten
    </x-slot>

    @if($currentBranchId && isset($branchEventTypes[$currentBranchId]))
        <div class="space-y-6">
            {{-- Current Event Types --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Zugeordnete Event Types
                </h3>
                
                @if(count($branchEventTypes[$currentBranchId]) > 0)
                    <div class="space-y-2">
                        @foreach($branchEventTypes[$currentBranchId] as $eventType)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center gap-3">
                                    @if($eventType['is_primary'])
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900/20 dark:text-primary-400">
                                            Primary
                                        </span>
                                    @else
                                        <button 
                                            wire:click="setPrimaryEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
                                        >
                                            Als Primary setzen
                                        </button>
                                    @endif
                                    
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $eventType['name'] }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            ID: {{ $eventType['calcom_id'] }} · {{ $eventType['duration'] }} Min.
                                        </p>
                                    </div>
                                </div>
                                
                                @if(!$eventType['is_primary'] || count($branchEventTypes[$currentBranchId]) > 1)
                                    <x-filament::icon-button
                                        icon="heroicon-m-trash"
                                        color="danger"
                                        size="sm"
                                        wire:click="removeBranchEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                        wire:confirm="Möchten Sie diesen Event Type wirklich entfernen?"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">
                        Keine Event Types zugeordnet
                    </p>
                @endif
            </div>
            
            {{-- Available Event Types --}}
            @if(count($availableEventTypes) > 0)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Verfügbare Event Types
                    </h3>
                    
                    <div class="space-y-2">
                        @foreach($availableEventTypes as $eventType)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $eventType['name'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        ID: {{ $eventType['calcom_id'] }} · {{ $eventType['duration'] }} Min.
                                    </p>
                                </div>
                                
                                <x-filament::button
                                    size="sm"
                                    wire:click="addBranchEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                >
                                    <x-heroicon-m-plus class="w-4 h-4 mr-1" />
                                    Hinzufügen
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <x-slot name="footer">
        <x-filament::button color="gray" wire:click="closeEventTypeModal">
            Schließen
        </x-filament::button>
    </x-slot>
</x-filament::modal>