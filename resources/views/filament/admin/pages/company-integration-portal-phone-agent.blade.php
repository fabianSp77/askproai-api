{{-- Enhanced Phone Number Agent Assignment --}}
<div class="space-y-2">
    {{-- Agent Selection Dropdown --}}
    @if($phoneAgentEditStates["agent_{$phone['id']}"] ?? false)
        <div class="flex items-center gap-2">
            <select 
                wire:model="selectedPhoneAgents.{{ $phone['id'] }}"
                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            >
                <option value="">-- Kein Agent --</option>
                @foreach($retellAgents as $agent)
                    <option value="{{ $agent['agent_id'] }}">
                        {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                        ({{ $agent['voice_id'] ?? 'Standard' }})
                    </option>
                @endforeach
            </select>
            <x-filament::button 
                wire:click="savePhoneAgent('{{ $phone['id'] }}')" 
                size="sm" 
                color="success"
                icon="heroicon-m-check"
            />
            <x-filament::button 
                wire:click="togglePhoneAgentEdit('{{ $phone['id'] }}')" 
                size="sm" 
                color="gray"
                icon="heroicon-m-x-mark"
            />
        </div>
    @else
        <button 
            wire:click="togglePhoneAgentEdit('{{ $phone['id'] }}')"
            class="group inline-flex items-center gap-2 text-sm"
        >
            @php
                // Find agent by ID from retellAgents array
                $assignedAgent = null;
                if($phone['retell_agent_id']) {
                    $assignedAgent = collect($retellAgents)->firstWhere('agent_id', $phone['retell_agent_id']);
                }
            @endphp
            
            @if($assignedAgent)
                <div class="flex items-center gap-2">
                    <x-heroicon-m-cpu-chip class="w-4 h-4 text-gray-500" />
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ $assignedAgent['agent_name'] ?? 'Unnamed Agent' }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        ({{ $assignedAgent['voice_id'] ?? 'Standard' }})
                    </span>
                </div>
            @else
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-cpu-chip class="w-4 h-4" />
                    <span>Kein Agent zugeordnet</span>
                </div>
            @endif
            <x-heroicon-m-pencil class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
        </button>
    @endif
    
    {{-- Version Selection (only if agent assigned) --}}
    @if($phone['retell_agent_id'])
        <div class="ml-6">
            @if($phoneVersionEditStates["version_{$phone['id']}"] ?? false)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Version:</span>
                    <select 
                        wire:model="selectedPhoneVersions.{{ $phone['id'] }}"
                        class="flex-1 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
                    >
                        <option value="current">Aktuelle Version</option>
                        @if(isset($agentVersions[$phone['retell_agent_id']]))
                            @foreach($agentVersions[$phone['retell_agent_id']] as $version)
                                <option value="{{ $version['version_id'] }}">
                                    {{ $version['version_name'] ?? $version['version_id'] }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <x-filament::button 
                        wire:click="savePhoneAgentVersion('{{ $phone['id'] }}')" 
                        size="xs" 
                        color="success"
                        icon="heroicon-m-check"
                    />
                    <x-filament::button 
                        wire:click="togglePhoneVersionDropdown('{{ $phone['id'] }}')" 
                        size="xs" 
                        color="gray"
                        icon="heroicon-m-x-mark"
                    />
                </div>
            @else
                <button 
                    wire:click="togglePhoneVersionDropdown('{{ $phone['id'] }}')"
                    class="group inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-primary-600"
                >
                    <x-heroicon-m-clock class="w-3 h-3" />
                    Version: 
                    <span class="font-medium">
                        @if($phone['retell_agent_version'])
                            {{ $phone['retell_agent_version'] }}
                        @else
                            Aktuelle
                        @endif
                    </span>
                    <x-heroicon-m-pencil class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                </button>
            @endif
        </div>
    @endif
</div>