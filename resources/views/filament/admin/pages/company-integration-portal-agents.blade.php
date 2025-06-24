{{-- Retell.ai Agent Management Section --}}
<div class="space-y-6">
    {{-- Agent Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($retellAgents as $agent)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Agent Header --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <div class="flex items-center justify-between">
                        {{-- Agent Name --}}
                        @if($agentEditStates["name_{$agent['agent_id']}"] ?? false)
                            <div class="flex items-center gap-2 flex-1">
                                <input 
                                    type="text"
                                    wire:model="agentNames.{{ $agent['agent_id'] }}"
                                    class="px-2 py-1 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    wire:keydown.enter="saveAgentName('{{ $agent['agent_id'] }}')"
                                    wire:keydown.escape="toggleAgentNameInput('{{ $agent['agent_id'] }}')"
                                />
                                <button wire:click="saveAgentName('{{ $agent['agent_id'] }}')" class="text-green-600 hover:text-green-700">
                                    <x-heroicon-m-check class="w-4 h-4" />
                                </button>
                                <button wire:click="toggleAgentNameInput('{{ $agent['agent_id'] }}')" class="text-gray-400 hover:text-gray-600">
                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        @else
                            <div class="flex items-center group">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                                </h3>
                                <button 
                                    wire:click="toggleAgentNameInput('{{ $agent['agent_id'] }}')"
                                    class="ml-2 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 transition-opacity"
                                >
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                        
                        {{-- Status Badge --}}
                        @if(isset($agent['branch']) && $agent['branch'])
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Zugeordnet
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                Nicht zugeordnet
                            </span>
                        @endif
                    </div>
                </div>
                
                {{-- Agent Details --}}
                <div class="p-4 space-y-3">
                    {{-- Agent ID --}}
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Agent ID</span>
                        <p class="text-xs font-mono text-gray-700 dark:text-gray-300">{{ $agent['agent_id'] }}</p>
                    </div>
                    
                    {{-- Voice Setting --}}
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Stimme</span>
                        @if($agentEditStates["voice_{$agent['agent_id']}"] ?? false)
                            <div class="mt-1 flex items-center gap-2">
                                <select 
                                    wire:model="agentVoiceIds.{{ $agent['agent_id'] }}"
                                    class="text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    @foreach($this->getAvailableVoices() as $voiceId => $voiceName)
                                        <option value="{{ $voiceId }}">{{ $voiceName }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="saveAgentVoice('{{ $agent['agent_id'] }}')" class="text-green-600 hover:text-green-700">
                                    <x-heroicon-m-check class="w-4 h-4" />
                                </button>
                                <button wire:click="toggleAgentVoiceInput('{{ $agent['agent_id'] }}')" class="text-gray-400 hover:text-gray-600">
                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        @else
                            <div class="flex items-center group">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->getAvailableVoices()[$agent['voice_id']] ?? $agent['voice_id'] ?? 'Standard' }}
                                </p>
                                <button 
                                    wire:click="toggleAgentVoiceInput('{{ $agent['agent_id'] }}')"
                                    class="ml-2 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 transition-opacity"
                                >
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Language Setting --}}
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Sprache</span>
                        @if($agentEditStates["language_{$agent['agent_id']}"] ?? false)
                            <div class="mt-1 flex items-center gap-2">
                                <select 
                                    wire:model="agentLanguages.{{ $agent['agent_id'] }}"
                                    class="text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    @foreach($this->getAvailableLanguages() as $langCode => $langName)
                                        <option value="{{ $langCode }}">{{ $langName }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="saveAgentLanguage('{{ $agent['agent_id'] }}')" class="text-green-600 hover:text-green-700">
                                    <x-heroicon-m-check class="w-4 h-4" />
                                </button>
                                <button wire:click="toggleAgentLanguageInput('{{ $agent['agent_id'] }}')" class="text-gray-400 hover:text-gray-600">
                                    <x-heroicon-m-x-mark class="w-4 h-4" />
                                </button>
                            </div>
                        @else
                            <div class="flex items-center group">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->getAvailableLanguages()[$agent['language']] ?? $agent['language'] ?? 'de-DE' }}
                                </p>
                                <button 
                                    wire:click="toggleAgentLanguageInput('{{ $agent['agent_id'] }}')"
                                    class="ml-2 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 transition-opacity"
                                >
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Phone Numbers --}}
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Telefonnummern</span>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @if(isset($agent['phone_numbers']) && count($agent['phone_numbers']) > 0)
                                @foreach($agent['phone_numbers'] as $phone)
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ $phone['phone_number_pretty'] ?? $phone['phone_number'] }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400 italic">Keine Nummern zugeordnet</span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Branch Assignment --}}
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Filialzuordnung</span>
                        @if(isset($agent['branch']) && $agent['branch'])
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $agent['branch']['name'] }}
                                </span>
                                <button 
                                    wire:click="unassignAgentFromBranch('{{ $agent['branch']['id'] }}')"
                                    wire:confirm="Möchten Sie die Zuordnung wirklich entfernen?"
                                    class="text-xs text-red-600 hover:text-red-700"
                                >
                                    Entfernen
                                </button>
                            </div>
                        @else
                            <div class="mt-1">
                                <select 
                                    wire:change="assignAgentToBranch('{{ $agent['agent_id'] }}', $event.target.value)"
                                    class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Filiale zuordnen...</option>
                                    @foreach($branches as $branch)
                                        @if(empty($branch['retell_agent_id']))
                                            <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Begin Message --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Begrüßung</span>
                            @if(!($agentEditStates["begin_message_{$agent['agent_id']}"] ?? false))
                                <button 
                                    wire:click="toggleAgentBeginMessageInput('{{ $agent['agent_id'] }}')"
                                    class="text-xs text-primary-600 hover:text-primary-700"
                                >
                                    Bearbeiten
                                </button>
                            @endif
                        </div>
                        @if($agentEditStates["begin_message_{$agent['agent_id']}"] ?? false)
                            <div class="mt-1">
                                <textarea
                                    wire:model="agentBeginMessages.{{ $agent['agent_id'] }}"
                                    rows="3"
                                    class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    placeholder="Begrüßungsnachricht eingeben..."
                                ></textarea>
                                <div class="mt-1 flex justify-end gap-2">
                                    <button wire:click="saveAgentBeginMessage('{{ $agent['agent_id'] }}')" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                        Speichern
                                    </button>
                                    <button wire:click="toggleAgentBeginMessageInput('{{ $agent['agent_id'] }}')" class="px-2 py-1 text-xs bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                        Abbrechen
                                    </button>
                                </div>
                            </div>
                        @else
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 italic line-clamp-2">
                                {{ $agent['begin_message'] ?? 'Keine Begrüßung definiert' }}
                            </p>
                        @endif
                    </div>
                </div>
                
                {{-- Agent Actions --}}
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-2">
                        <button 
                            wire:click="showAgentDetails('{{ $agent['agent_id'] }}')"
                            class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                        >
                            Details anzeigen
                        </button>
                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="startEditingAgent('{{ $agent['agent_id'] }}', 'general_prompt')"
                                title="Prompt bearbeiten"
                                class="text-gray-600 hover:text-gray-700"
                            >
                                <x-heroicon-m-document-text class="w-4 h-4" />
                            </button>
                            <a 
                                href="https://app.retellai.com/agents/{{ $agent['agent_id'] }}"
                                target="_blank"
                                title="In Retell.ai öffnen"
                                class="text-gray-600 hover:text-gray-700"
                            >
                                <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Empty State --}}
    @if(count($retellAgents) === 0)
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
            <x-heroicon-o-cpu-chip class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">Keine Agents gefunden</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                Stellen Sie sicher, dass die Retell.ai API-Verbindung konfiguriert ist.
            </p>
            <x-filament::button
                wire:click="syncRetellAgents"
                size="sm"
                class="mt-4"
            >
                Agents synchronisieren
            </x-filament::button>
        </div>
    @endif
</div>

{{-- Agent Details Modal --}}
@if($showAgentModal && $selectedAgentDetails)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            {{-- Background overlay --}}
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                wire:click="closeAgentModal"
            ></div>
            
            {{-- Modal panel --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-4xl w-full">
                {{-- Modal header --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Agent Details: {{ $selectedAgentDetails['agent_name'] ?? 'Unnamed Agent' }}
                        </h3>
                        <button
                            wire:click="closeAgentModal"
                            class="text-gray-400 hover:text-gray-500"
                        >
                            <x-heroicon-m-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                
                {{-- Modal body --}}
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="space-y-6">
                        {{-- General Information --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Allgemeine Informationen</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Agent ID</dt>
                                    <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['agent_id'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['agent_name'] ?? 'Nicht definiert' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Stimme</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['voice_id'] ?? 'Standard' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Sprache</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['language'] ?? 'de-DE' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Response Delay (ms)</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['response_waiting_time'] ?? '0' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Webhook URL</dt>
                                    <dd class="mt-1 text-xs font-mono text-gray-900 dark:text-gray-100 truncate">{{ $selectedAgentDetails['webhook_url'] ?? 'Nicht konfiguriert' }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        {{-- Begin Message --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Begrüßungsnachricht</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $selectedAgentDetails['begin_message'] ?? 'Keine Begrüßungsnachricht definiert' }}</p>
                            </div>
                        </div>
                        
                        {{-- General Prompt --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">System Prompt</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ $selectedAgentDetails['general_prompt'] ?? 'Kein Prompt definiert' }}</pre>
                            </div>
                        </div>
                        
                        {{-- LLM Settings --}}
                        @if(isset($selectedAgentDetails['llm_websocket_url']) || isset($selectedAgentDetails['model']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">LLM Einstellungen</h4>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if(isset($selectedAgentDetails['model']))
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Model</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['model'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($selectedAgentDetails['temperature']))
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Temperature</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['temperature'] }}</dd>
                                        </div>
                                    @endif
                                    @if(isset($selectedAgentDetails['max_tokens']))
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Max Tokens</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $selectedAgentDetails['max_tokens'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                        
                        {{-- Metadata --}}
                        @if(isset($selectedAgentDetails['metadata']) && !empty($selectedAgentDetails['metadata']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Metadata</h4>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <pre class="text-xs text-gray-700 dark:text-gray-300 font-mono">{{ json_encode($selectedAgentDetails['metadata'], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Modal footer --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <a 
                            href="https://app.retellai.com/agents/{{ $selectedAgentDetails['agent_id'] }}"
                            target="_blank"
                            class="text-sm text-primary-600 hover:text-primary-700"
                        >
                            In Retell.ai bearbeiten →
                        </a>
                        <x-filament::button
                            wire:click="closeAgentModal"
                            color="gray"
                        >
                            Schließen
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif