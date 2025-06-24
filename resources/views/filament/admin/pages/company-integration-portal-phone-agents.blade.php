{{-- Phone Numbers Agent Assignment Section --}}
<div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Telefonnummern & Agent-Zuordnung
    </h3>
    
    @if(count($phoneNumbers) > 0)
        <div class="space-y-3">
            @foreach($phoneNumbers as $phone)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $phone['number'] ?? $phone->number ?? '' }}
                                </span>
                                @if(($phone['is_primary'] ?? $phone->is_primary ?? false))
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 rounded-full">
                                        <x-heroicon-m-star class="w-3 h-3" />
                                        Primär
                                    </span>
                                @endif
                                @if(($phone['is_active'] ?? $phone->is_active ?? false))
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400 rounded-full">
                                        <x-heroicon-m-check-circle class="w-3 h-3" />
                                        Aktiv
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 rounded-full">
                                        <x-heroicon-m-x-circle class="w-3 h-3" />
                                        Inaktiv
                                    </span>
                                @endif
                            </div>
                            
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Filiale: {{ $phone['branch']['name'] ?? $phone->branch->name ?? 'Nicht zugeordnet' }}
                            </div>
                        </div>
                        
                        <div class="ml-4">
                            {{-- Agent Assignment --}}
                            <div class="flex items-center gap-2">
                                @if($phoneAgentEditStates[$phone['id'] ?? $phone->id ?? ''] ?? false)
                                    <select
                                        wire:model="selectedPhoneAgents.{{ $phone['id'] ?? $phone->id ?? '' }}"
                                        class="text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    >
                                        <option value="">Kein Agent</option>
                                        @foreach($retellAgents as $agent)
                                            <option value="{{ $agent['agent_id'] ?? $agent['id'] }}">
                                                {{ $agent['agent_name'] ?? $agent['name'] ?? $agent['agent_id'] ?? $agent['id'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-filament::icon-button
                                        icon="heroicon-m-check"
                                        wire:click="savePhoneAgent('{{ $phone['id'] ?? $phone->id ?? '' }}')"
                                        color="success"
                                        size="sm"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-m-x-mark"
                                        wire:click="cancelPhoneAgentEdit('{{ $phone['id'] ?? $phone->id ?? '' }}')"
                                        color="gray"
                                        size="sm"
                                    />
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            Agent: 
                                            @if($phone['retell_agent_id'] ?? $phone->retell_agent_id ?? null)
                                                @php
                                                    $agentName = null;
                                                    foreach($retellAgents as $agent) {
                                                        if (($agent['agent_id'] ?? $agent['id']) == ($phone['retell_agent_id'] ?? $phone->retell_agent_id ?? null)) {
                                                            $agentName = $agent['agent_name'] ?? $agent['name'] ?? null;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                <span class="font-medium">
                                                    {{ $agentName ?? $phone['retell_agent_id'] ?? $phone->retell_agent_id ?? '' }}
                                                </span>
                                            @else
                                                <span class="italic">Nicht zugeordnet</span>
                                            @endif
                                        </span>
                                        <x-filament::icon-button
                                            icon="heroicon-m-pencil"
                                            wire:click="editPhoneAgent('{{ $phone['id'] ?? $phone->id ?? '' }}')"
                                            color="gray"
                                            size="sm"
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Phone Test Button --}}
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                ID: {{ $phone['id'] ?? $phone->id ?? '' }}
                            </div>
                            <x-filament::button
                                wire:click="testPhoneResolution('{{ $phone['number'] ?? $phone->number ?? '' }}')"
                                size="xs"
                                color="gray"
                            >
                                <x-heroicon-m-beaker class="w-3 h-3 mr-1" />
                                Test Resolution
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6 text-center">
            <x-heroicon-o-phone class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400">
                Keine Telefonnummern konfiguriert.
            </p>
            <x-filament::button
                wire:click="$set('showAddPhoneModal', true)"
                size="sm"
                color="primary"
                class="mt-3"
            >
                Telefonnummer hinzufügen
            </x-filament::button>
        </div>
    @endif
</div>