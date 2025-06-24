{{-- Enhanced Branch Management Section (Agent Assignment via Phone Numbers) --}}
<div class="branch-cards-container space-y-4">
    @foreach($branches as $branch)
        @php
            // Get phone numbers and their agents for this branch
            $branchPhones = array_filter($phoneNumbers, fn($phone) => $phone['branch_id'] === $branch['id']);
            $branchAgents = [];
            foreach($branchPhones as $phone) {
                if ($phone['actual_agent_id']) {
                    $branchAgents[$phone['actual_agent_id']] = true;
                }
            }
            $agentCount = count($branchAgents);
        @endphp
        
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
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                <x-heroicon-m-clock class="w-4 h-4 mr-1.5" /> Einrichtung erforderlich
                            </span>
                        @endif
                        
                        {{-- Active Toggle --}}
                        <button
                            wire:click="toggleBranchActive('{{ $branch['id'] }}')"
                            @class([
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                'bg-green-500' => $branch['is_active'],
                                'bg-gray-300 dark:bg-gray-700' => !$branch['is_active'],
                            ])
                            title="{{ $branch['is_active'] ? 'Filiale deaktivieren' : 'Filiale aktivieren' }}"
                        >
                            <span @class([
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                'translate-x-5' => $branch['is_active'],
                                'translate-x-0' => !$branch['is_active'],
                            ])></span>
                        </button>
                        
                        {{-- Settings Dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false" class="branch-action-button p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <x-heroicon-m-ellipsis-vertical class="w-5 h-5" />
                            </button>
                            
                            <div 
                                x-show="open" 
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="branch-dropdown-menu absolute right-0 mt-2 w-56 rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                style="display: none;"
                            >
                                <div class="py-1">
                                    <a href="{{ route('filament.admin.resources.branches.edit', $branch['id']) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-m-pencil-square class="w-4 h-4 inline-block mr-2" /> Bearbeiten
                                    </a>
                                    <button wire:click="duplicateBranch('{{ $branch['id'] }}')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-m-document-duplicate class="w-4 h-4 inline-block mr-2" /> Duplizieren
                                    </button>
                                    @if(!$branch['is_main'])
                                        <button wire:click="deleteBranch('{{ $branch['id'] }}')" wire:confirm="Sind Sie sicher, dass Sie diese Filiale löschen möchten?" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <x-heroicon-m-trash class="w-4 h-4 inline-block mr-2" /> Löschen
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Content Section --}}
            <div class="p-4 branch-card-content">
                {{-- Contact Information --}}
                <div class="mb-4 space-y-3">
                    {{-- Address --}}
                    @if($branchEditStates["address_{$branch['id']}"] ?? false)
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <input type="text" wire:model="branchAddresses.{{ $branch['id'] }}" class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                            <x-filament::button wire:click="saveBranchAddress({{ $branch['id'] }})" size="sm" color="success" icon="heroicon-m-check" />
                            <x-filament::button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                        </div>
                    @else
                        <div class="flex items-center gap-2 group inline-edit-field">
                            <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $branch['address'] ?? 'Keine Adresse hinterlegt' }}</span>
                            <button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" class="inline-edit-button opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <x-heroicon-m-pencil class="w-3 h-3" />
                            </button>
                        </div>
                    @endif
                    
                    {{-- Email --}}
                    @if($branchEditStates["email_{$branch['id']}"] ?? false)
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <input type="email" wire:model="branchEmails.{{ $branch['id'] }}" class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" />
                            <x-filament::button wire:click="saveBranchEmail({{ $branch['id'] }})" size="sm" color="success" icon="heroicon-m-check" />
                            <x-filament::button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" size="sm" color="gray" icon="heroicon-m-x-mark" />
                        </div>
                    @else
                        <div class="flex items-center gap-2 group inline-edit-field">
                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $branch['email'] ?? 'Keine E-Mail hinterlegt' }}</span>
                            <button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" class="inline-edit-button opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <x-heroicon-m-pencil class="w-3 h-3" />
                            </button>
                        </div>
                    @endif
                </div>
                
                {{-- Configuration Grid --}}
                <div class="branch-config-grid grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    {{-- Cal.com Event Types --}}
                    <div class="event-types-section bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-500" />
                                    Cal.com Event Types
                                    <div class="tooltip-trigger">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="tooltip-content">
                                            Event Types bestimmen die verfügbaren Terminarten und deren Dauer
                                        </div>
                                    </div>
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
                    
                    {{-- Retell.ai Agents Status --}}
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-cpu-chip class="w-4 h-4 text-gray-500" />
                                    Retell.ai Agents
                                    <div class="tooltip-trigger">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="tooltip-content">
                                            KI-Agents beantworten Anrufe und buchen Termine automatisch
                                        </div>
                                    </div>
                                </h5>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">KI-Agents über Telefonnummern</p>
                            </div>
                            @if($agentCount > 0)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    <x-heroicon-m-check class="w-3 h-3 mr-1" /> {{ $agentCount }} {{ $agentCount == 1 ? 'Agent' : 'Agents' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                    <x-heroicon-m-exclamation-triangle class="w-3 h-3 mr-1" /> Keine Agents
                                </span>
                            @endif
                        </div>
                        
                        <div class="mt-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                Agents werden über die Telefonnummern zugeordnet.
                            </p>
                            <a href="#phone-numbers-section" class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                                <x-heroicon-m-arrow-up class="w-3 h-3" />
                                Zu den Telefonnummern
                            </a>
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