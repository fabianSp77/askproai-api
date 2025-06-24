<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Error Display --}}
        @if($error)
            <div class="bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/20 rounded-lg p-4">
                <p class="text-danger-800 dark:text-danger-400">{{ $error }}</p>
            </div>
        @endif

        {{-- Loading State --}}
        @if($loading)
            <div class="flex items-center justify-center p-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            </div>
        @endif

        {{-- Phone Numbers Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Phone Numbers Configuration
                </h2>
                
                <div class="space-y-4">
                    @foreach($phoneNumbers as $phone)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $phone['phone_number'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $phone['nickname'] ?? 'No nickname' }}
                                    </p>
                                    
                                    @php
                                        $currentAgentId = $phone['agent_id'] ?? ($phone['inbound_agent_id'] ?? null);
                                        $currentAgent = collect($agents)->firstWhere('agent_id', $currentAgentId);
                                    @endphp
                                    
                                    <div class="mt-2">
                                        <p class="text-sm">
                                            <span class="text-gray-500">Current Agent:</span>
                                            <span class="font-medium {{ $currentAgent ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $currentAgent ? $currentAgent['agent_name'] : 'NOT SET' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-4">
                                    <select 
                                        wire:change="updatePhoneAgent('{{ $phone['phone_number'] }}', $event.target.value)"
                                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option value="">Select Agent...</option>
                                        @foreach($agents as $agent)
                                            <option 
                                                value="{{ $agent['agent_id'] }}" 
                                                {{ ($currentAgentId === $agent['agent_id']) ? 'selected' : '' }}
                                            >
                                                {{ $agent['agent_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Agents Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Agents Configuration
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($agents as $agent)
                        @if(str_contains($agent['agent_name'], 'Musterfriseur') || str_contains($agent['agent_name'], 'Rechtliches'))
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    {{ $agent['agent_name'] }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    ID: {{ $agent['agent_id'] }}
                                </p>
                                
                                <div class="mt-3 space-y-2">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Webhook URL:</p>
                                        <p class="text-xs font-mono {{ $agent['webhook_url'] ?? false ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $agent['webhook_url'] ?? 'NOT SET' }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Webhook Events:</p>
                                        <p class="text-xs {{ !empty($agent['webhook_events']) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ !empty($agent['webhook_events']) ? implode(', ', $agent['webhook_events']) : 'NONE' }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Custom Functions:</p>
                                        <p class="text-xs {{ !empty($agent['custom_functions']) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ !empty($agent['custom_functions']) ? count($agent['custom_functions']) . ' configured' : 'NONE' }}
                                        </p>
                                    </div>
                                    
                                    @if(!$agent['webhook_url'] || empty($agent['webhook_events']) || empty($agent['custom_functions']))
                                        <button 
                                            wire:click="updateAgentWebhook('{{ $agent['agent_id'] }}', 'https://api.askproai.de/api/retell/webhook', ['call_started', 'call_ended', 'call_analyzed'])"
                                            class="mt-2 px-3 py-1 bg-primary-600 text-white text-sm rounded hover:bg-primary-700"
                                        >
                                            Fix Configuration
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Refresh Button --}}
        <div class="flex justify-end">
            <button 
                wire:click="loadData" 
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
            >
                <span wire:loading.remove>Refresh Data</span>
                <span wire:loading>Loading...</span>
            </button>
        </div>
    </div>
</x-filament-panels::page>