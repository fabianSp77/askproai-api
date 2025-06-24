{{-- Branch Agent Overview (Read-Only Display) --}}
<div class="space-y-6">
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <x-heroicon-m-information-circle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
            <div class="text-sm">
                <p class="font-medium text-amber-800 dark:text-amber-200 mb-1">
                    Agent-Konfiguration erfolgt über Telefonnummern
                </p>
                <p class="text-amber-700 dark:text-amber-300">
                    Die Zuordnung von Agents und Versionen erfolgt direkt bei den Telefonnummern (siehe oben). 
                    Hier sehen Sie eine Übersicht der konfigurierten Agents pro Filiale.
                </p>
            </div>
        </div>
    </div>

    {{-- Branch Cards with Agent Overview --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($branches as $branch)
            @php
                // Get all phone numbers for this branch
                $branchPhones = array_filter($phoneNumbers, fn($phone) => $phone['branch_id'] === $branch['id']);
                
                // Collect unique agents used by this branch
                $branchAgents = [];
                foreach($branchPhones as $phone) {
                    if (!empty($phone['retell_agent_id'])) {
                        $agentId = $phone['retell_agent_id'];
                        
                        // Find agent info from retellAgents array
                        $agentInfo = collect($retellAgents)->firstWhere('agent_id', $agentId);
                        
                        if ($agentInfo) {
                            if (!isset($branchAgents[$agentId])) {
                                $branchAgents[$agentId] = [
                                    'info' => $agentInfo,
                                    'phones' => []
                                ];
                            }
                            
                            $branchAgents[$agentId]['phones'][] = [
                                'number' => $phone['number'],
                                'formatted' => $phone['formatted'] ?? $phone['number'],
                                'version' => $phone['active_version'] ?? 'current'
                            ];
                        }
                    }
                }
            @endphp
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Branch Header --}}
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                <x-heroicon-o-building-office-2 class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $branch['name'] }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ count($branchPhones) }} Telefonnummer(n) • 
                                    {{ count($branchAgents) }} Agent(s)
                                </p>
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
                
                <div class="p-6">
                    @if(count($branchAgents) > 0)
                        {{-- Agents Overview --}}
                        <div class="space-y-4">
                            @foreach($branchAgents as $agentId => $agentData)
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    {{-- Agent Header --}}
                                    <div class="flex items-start justify-between mb-3">
                                        <button 
                                            wire:click="showAgentDetails('{{ $agentId }}')"
                                            class="flex items-center gap-2 hover:opacity-80 transition-opacity text-left"
                                        >
                                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                            <h4 class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                                {{ $agentData['info']['agent_name'] ?? 'Unnamed Agent' }}
                                            </h4>
                                            <x-heroicon-m-information-circle class="w-4 h-4 text-gray-400" />
                                        </button>
                                        <a 
                                            href="https://app.retellai.com/agents/{{ $agentId }}"
                                            target="_blank"
                                            class="text-xs text-primary-600 hover:text-primary-700"
                                            title="In Retell.ai öffnen"
                                        >
                                            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                        </a>
                                    </div>
                                    
                                    {{-- Agent Details --}}
                                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Stimme:</span>
                                            <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $agentData['info']['voice_id'] ?? 'Standard' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Sprache:</span>
                                            <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $agentData['info']['language'] ?? 'de-DE' }}</span>
                                        </div>
                                    </div>
                                    
                                    {{-- Phone Numbers with Versions --}}
                                    <div class="space-y-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Zugeordnete Nummern:</p>
                                        @foreach($agentData['phones'] as $phoneInfo)
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded px-2 py-1">
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-m-phone class="w-3 h-3 text-gray-400" />
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $phoneInfo['formatted'] }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    Version: {{ $phoneInfo['version'] === 'current' ? 'Aktuell' : $phoneInfo['version'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Configuration Status --}}
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Cal.com Event Type:</span>
                                    @if($branch['calcom_event_type_id'])
                                        <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                                            <x-heroicon-m-check-circle class="w-3 h-3" />
                                            Konfiguriert
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
                    @else
                        {{-- No Agents Assigned --}}
                        <div class="text-center py-8">
                            <x-heroicon-o-cpu-chip class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                Keine Agents konfiguriert
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Ordnen Sie Agents den Telefonnummern dieser Filiale zu.
                            </p>
                            <a 
                                href="#phone-numbers-section"
                                class="inline-flex items-center gap-1 mt-3 text-xs text-primary-600 hover:text-primary-700"
                            >
                                <x-heroicon-m-arrow-up class="w-3 h-3" />
                                Zu den Telefonnummern
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Global Agent Statistics --}}
    @php
        $uniqueAgentIds = array_unique(array_filter(array_column($phoneNumbers, 'retell_agent_id')));
        $totalAgents = count($uniqueAgentIds);
        $totalPhones = count($phoneNumbers);
        $configuredPhones = count(array_filter($phoneNumbers, fn($p) => !empty($p['retell_agent_id'])));
    @endphp
    
    @if($totalAgents > 0)
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Agent-Übersicht</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalAgents }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Aktive Agents</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $configuredPhones }}/{{ $totalPhones }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Konfigurierte Nummern</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($branches) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Filialen</div>
                </div>
            </div>
        </div>
    @endif
</div>