{{-- Compact Retell.ai Agent Management Section --}}
<div class="space-y-6">
    {{-- Branch-centered view with Agent Management --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($branches as $branch)
            @php
                $branchAgentId = $branch['retell_agent_id'] ?? null;
                $assignedAgent = null;
                
                // Find the assigned agent for this branch
                foreach($retellAgents as $agent) {
                    if ($agent['agent_id'] === $branchAgentId) {
                        $assignedAgent = $agent;
                        break;
                    }
                    // Also check if agent has a branch assignment
                    if (isset($agent['branch']) && $agent['branch']['id'] === $branch['id']) {
                        $assignedAgent = $agent;
                        break;
                    }
                }
                
                // Get phone numbers for this branch
                $branchPhones = array_filter($phoneNumbers, fn($phone) => $phone['branch_id'] === $branch['id']);
            @endphp
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200">
                {{-- Branch Header --}}
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                <x-heroicon-o-building-office-2 class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $branch['name'] }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ count($branchPhones) }} Telefonnummer(n)</p>
                            </div>
                        </div>
                        @if($branch['is_active'])
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Aktiv
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                Inaktiv
                            </span>
                        @endif
                    </div>
                </div>
                
                {{-- Agent Selection and Configuration --}}
                <div class="p-6 space-y-4">
                    {{-- Agent Dropdown --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <x-heroicon-m-cpu-chip class="w-4 h-4 inline-block mr-1" />
                            AI Agent Zuordnung
                        </label>
                        
                        <select 
                            wire:change="assignAgentToBranch($event.target.value, '{{ $branch['id'] }}')"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">-- Kein Agent zugeordnet --</option>
                            @foreach($retellAgents as $agent)
                                @php
                                    $isAssignedElsewhere = false;
                                    foreach($branches as $otherBranch) {
                                        if ($otherBranch['id'] !== $branch['id'] && $otherBranch['retell_agent_id'] === $agent['agent_id']) {
                                            $isAssignedElsewhere = true;
                                            break;
                                        }
                                    }
                                @endphp
                                
                                <option 
                                    value="{{ $agent['agent_id'] }}" 
                                    @if($assignedAgent && $assignedAgent['agent_id'] === $agent['agent_id']) selected @endif
                                    @if($isAssignedElsewhere) disabled @endif
                                >
                                    {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                                    ({{ $agent['voice_id'] ?? 'Standard' }})
                                    @if($isAssignedElsewhere) - Bereits zugeordnet @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- Agent Details (if assigned) --}}
                    @if($assignedAgent)
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-3">
                            {{-- Agent Status --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Agent aktiv: {{ $assignedAgent['agent_name'] ?? 'Unnamed' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if(count($branchPhones) > 0)
                                        @php
                                            $hasVersionControl = false;
                                            foreach($branchPhones as $phone) {
                                                if(isset($agentVersions[$assignedAgent['agent_id']]) && count($agentVersions[$assignedAgent['agent_id']]) > 1) {
                                                    $hasVersionControl = true;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        @if($hasVersionControl)
                                            <span class="text-xs bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded-full">
                                                {{ count($agentVersions[$assignedAgent['agent_id']] ?? []) }} Versionen
                                            </span>
                                        @endif
                                    @endif
                                    <button 
                                        wire:click="showAgentDetails('{{ $assignedAgent['agent_id'] }}')"
                                        class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                                    >
                                        Details anzeigen
                                    </button>
                                </div>
                            </div>
                            
                            {{-- Quick Info --}}
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Stimme:</span>
                                    <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $assignedAgent['voice_id'] ?? 'Standard' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Sprache:</span>
                                    <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $assignedAgent['language'] ?? 'de-DE' }}</span>
                                </div>
                            </div>
                            
                            {{-- Phone Numbers Status --}}
                            @if(count($branchPhones) > 0)
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Verknüpfte Nummern:</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($branchPhones as $phone)
                                            @php
                                                $phoneHasAgent = isset($phoneAgentMapping[$phone['number']]);
                                            @endphp
                                            <span @class([
                                                'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full',
                                                'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' => $phoneHasAgent,
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' => !$phoneHasAgent,
                                            ])>
                                                <x-heroicon-m-phone class="w-3 h-3 mr-1" />
                                                {{ $phone['formatted'] ?? $phone['number'] }}
                                                @if(!$phoneHasAgent)
                                                    <x-heroicon-m-exclamation-triangle class="w-3 h-3 ml-1" />
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Quick Actions --}}
                            <div class="flex items-center gap-2 pt-2">
                                <button 
                                    wire:click="startEditingAgent('{{ $assignedAgent['agent_id'] }}', 'begin_message')"
                                    class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
                                >
                                    <x-heroicon-m-chat-bubble-left-right class="w-3 h-3 inline-block mr-1" />
                                    Begrüßung
                                </button>
                                <button 
                                    wire:click="startEditingAgent('{{ $assignedAgent['agent_id'] }}', 'general_prompt')"
                                    class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
                                >
                                    <x-heroicon-m-document-text class="w-3 h-3 inline-block mr-1" />
                                    Prompt
                                </button>
                                @if(isset($agentVersions[$assignedAgent['agent_id']]) && count($agentVersions[$assignedAgent['agent_id']]) > 0)
                                    <button 
                                        wire:click="showVersionHistory('{{ $assignedAgent['agent_id'] }}')"
                                        class="text-xs px-2 py-1 bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded hover:bg-primary-200 dark:hover:bg-primary-800/30"
                                    >
                                        <x-heroicon-m-clock class="w-3 h-3 inline-block mr-1" />
                                        Versionen
                                    </button>
                                @endif
                                <a 
                                    href="https://app.retellai.com/agents/{{ $assignedAgent['agent_id'] }}"
                                    target="_blank"
                                    class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
                                >
                                    <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3 inline-block mr-1" />
                                    Retell.ai
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- No Agent Assigned --}}
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <div class="flex items-start gap-2">
                                <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" />
                                <div class="text-sm">
                                    <p class="font-medium text-yellow-800 dark:text-yellow-200">Kein Agent zugeordnet</p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                        Wählen Sie einen Agent aus der Liste oder erstellen Sie einen neuen in Retell.ai.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Verification Status --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Webhook URL:</span>
                            @if($assignedAgent && !empty($assignedAgent['webhook_url']))
                                <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                                    <x-heroicon-m-check-circle class="w-3 h-3" />
                                    Konfiguriert
                                </span>
                            @else
                                <span class="text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <x-heroicon-m-x-circle class="w-3 h-3" />
                                    Nicht konfiguriert
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Cal.com Event Type:</span>
                            @if($branch['calcom_event_type_id'])
                                <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                                    <x-heroicon-m-check-circle class="w-3 h-3" />
                                    Verknüpft
                                </span>
                            @else
                                <span class="text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <x-heroicon-m-x-circle class="w-3 h-3" />
                                    Fehlt
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Unassigned Agents Section --}}
    @php
        $unassignedAgents = array_filter($retellAgents, function($agent) use ($branches) {
            foreach($branches as $branch) {
                if ($branch['retell_agent_id'] === $agent['agent_id']) {
                    return false;
                }
            }
            return true;
        });
    @endphp
    
    @if(count($unassignedAgents) > 0)
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                <x-heroicon-m-cpu-chip class="w-4 h-4 inline-block mr-1" />
                Nicht zugeordnete Agents ({{ count($unassignedAgents) }})
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($unassignedAgents as $agent)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $agent['voice_id'] ?? 'Standard' }} • {{ $agent['language'] ?? 'de-DE' }}
                                </p>
                            </div>
                            <button 
                                wire:click="showAgentDetails('{{ $agent['agent_id'] }}')"
                                class="text-xs text-primary-600 hover:text-primary-700"
                            >
                                Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Agent Details Modal (reuse existing modal code) --}}
@if($showAgentModal && $selectedAgentDetails)
    @include('filament.admin.pages.company-integration-portal-agent-modal')
@endif

{{-- Inline Edit Modal for Agent Fields --}}
@if($editingAgentId && $editingAgentField)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                wire:click="cancelEditingAgent"
            ></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-2xl w-full">
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($editingAgentField === 'begin_message')
                            Begrüßungsnachricht bearbeiten
                        @elseif($editingAgentField === 'general_prompt')
                            System Prompt bearbeiten
                        @else
                            {{ ucfirst(str_replace('_', ' ', $editingAgentField)) }} bearbeiten
                        @endif
                    </h3>
                </div>
                
                <div class="px-6 py-4">
                    @if($editingAgentField === 'begin_message' || $editingAgentField === 'general_prompt')
                        <textarea
                            wire:model="editingAgentValue"
                            rows="10"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                            placeholder="Text eingeben..."
                        ></textarea>
                    @else
                        <input 
                            type="text"
                            wire:model="editingAgentValue"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                        />
                    @endif
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-end gap-3">
                        <x-filament::button
                            wire:click="cancelEditingAgent"
                            color="gray"
                        >
                            Abbrechen
                        </x-filament::button>
                        <x-filament::button
                            wire:click="saveEditingAgent"
                        >
                            Speichern
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif