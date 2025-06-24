{{-- Agent Details Modal --}}
<div 
    x-show="$wire.showAgentModal"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="agent-modal-title"
    role="dialog"
    aria-modal="true"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div 
            x-show="$wire.showAgentModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="$wire.closeAgentModal()"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            aria-hidden="true"
        ></div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal panel --}}
        <div 
            x-show="$wire.showAgentModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full sm:p-6"
        >
            @if($selectedAgentDetails)
                {{-- Modal Header --}}
                <div class="mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="agent-modal-title">
                                Agent Details: {{ $selectedAgentDetails['agent_name'] ?? 'Unnamed Agent' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                ID: {{ $selectedAgentDetails['agent_id'] ?? 'Unknown' }}
                            </p>
                        </div>
                        <button 
                            @click="$wire.closeAgentModal()"
                            class="ml-3 bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <span class="sr-only">Close</span>
                            <x-heroicon-m-x-mark class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                {{-- Modal Content --}}
                <div class="space-y-6">
                    {{-- Basic Information --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Grundeinstellungen</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Stimme:</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                    {{ $selectedAgentDetails['voice_id'] ?? 'Standard' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Sprache:</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                    {{ $selectedAgentDetails['language'] ?? 'de-DE' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Antwort-Modell:</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                    {{ $selectedAgentDetails['response_engine']['model'] ?? 'gpt-4' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Response Delay:</span>
                                <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                    {{ $selectedAgentDetails['boosted_keywords']['words'][0] ?? '0' }}ms
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Begin Message --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Begrüßungsnachricht</h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $selectedAgentDetails['begin_message'] ?? 'Keine Begrüßungsnachricht konfiguriert.' }}</p>
                        </div>
                    </div>

                    {{-- General Prompt --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">System Prompt</h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 max-h-60 overflow-y-auto">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono text-xs">{{ $selectedAgentDetails['general_prompt'] ?? 'Kein Prompt konfiguriert.' }}</p>
                        </div>
                    </div>

                    {{-- Webhook Configuration --}}
                    @if(isset($selectedAgentDetails['webhook_url']) && $selectedAgentDetails['webhook_url'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Webhook</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                <p class="text-sm text-gray-700 dark:text-gray-300 break-all font-mono text-xs">
                                    {{ $selectedAgentDetails['webhook_url'] }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Phone Numbers --}}
                    @php
                        $agentPhones = array_filter($phoneNumbers, fn($p) => $p['retell_agent_id'] === $selectedAgentDetails['agent_id']);
                    @endphp
                    @if(count($agentPhones) > 0)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Zugeordnete Telefonnummern</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                <div class="space-y-2">
                                    @foreach($agentPhones as $phone)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-phone class="w-4 h-4 text-gray-400" />
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ $phone['formatted'] ?? $phone['number'] }}
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    ({{ $phone['branch'] }})
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Version: {{ $phone['active_version'] ?? 'Aktuell' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="mt-6 flex justify-between">
                    <x-filament::button
                        href="https://app.retellai.com/agents/{{ $selectedAgentDetails['agent_id'] }}"
                        tag="a"
                        target="_blank"
                        color="gray"
                        icon="heroicon-m-arrow-top-right-on-square"
                    >
                        In Retell.ai öffnen
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="closeAgentModal"
                        color="primary"
                    >
                        Schließen
                    </x-filament::button>
                </div>
            @else
                {{-- Loading State --}}
                <div class="text-center py-8">
                    <x-filament::loading-indicator class="w-8 h-8 mx-auto" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Lade Agent-Details...</p>
                </div>
            @endif
        </div>
    </div>
</div>