@php
    $agentData = $getRecord()->retell_agent_data;
    $needsSync = $getRecord()->needsRetellSync();
@endphp

<div class="space-y-4">
    @if($agentData)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Agent Info -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Agent-Informationen
                </h4>
                
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Name:</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $agentData['name'] ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Sprache:</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            {{ $agentData['language'] ?? 'de' }} 
                            @if(($agentData['language'] ?? 'de') === 'de')
                                <span class="text-green-600">✓</span>
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Stimme:</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $agentData['voice'] ?? 'Standard' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status:</dt>
                        <dd>
                            @if($agentData['active'] ?? false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Aktiv
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    Inaktiv
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Statistics -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Statistiken (7 Tage)
                </h4>
                
                @if($agentData['statistics'] ?? false)
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Anrufe gesamt:</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $agentData['statistics']['total_calls'] ?? 0 }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Erfolgsquote:</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $agentData['statistics']['success_rate'] ?? 0 }}%
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Ø Dauer:</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ gmdate('i:s', $agentData['statistics']['average_duration'] ?? 0) }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Letzte Aktivität:</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                @if($agentData['statistics']['last_activity'] ?? false)
                                    {{ \Carbon\Carbon::parse($agentData['statistics']['last_activity'])->diffForHumans() }}
                                @else
                                    Keine
                                @endif
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Keine Statistiken verfügbar</p>
                @endif
            </div>
        </div>
        
        @if($needsSync)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Die Agent-Daten sind älter als 1 Stunde. Klicken Sie auf "Agent-Daten synchronisieren" für aktuelle Informationen.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Keine Agent-Daten verfügbar. Bitte synchronisieren Sie die Daten.
            </p>
        </div>
    @endif
</div>
