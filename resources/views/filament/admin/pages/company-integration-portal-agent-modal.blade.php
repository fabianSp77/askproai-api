{{-- Agent Details Modal --}}
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
                    
                    {{-- Phone Numbers --}}
                    @if(isset($selectedAgentDetails['phone_numbers']) && count($selectedAgentDetails['phone_numbers']) > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Verknüpfte Telefonnummern</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedAgentDetails['phone_numbers'] as $phone)
                                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                            <x-heroicon-m-phone class="w-4 h-4 mr-1" />
                                            {{ $phone['phone_number_pretty'] ?? $phone['phone_number'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    
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