<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Error/Success Messages --}}
        @if($error)
            <div class="p-4 bg-danger-50 dark:bg-danger-900/10 text-danger-600 dark:text-danger-400 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5 mr-2" />
                    {{ $error }}
                </div>
            </div>
        @endif
        
        @if($successMessage)
            <div class="p-4 bg-success-50 dark:bg-success-900/10 text-success-600 dark:text-success-400 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-check-circle class="w-5 h-5 mr-2" />
                    {{ $successMessage }}
                </div>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Retell Configuration</h2>
                <x-filament::button wire:click="refresh" wire:loading.attr="disabled">
                    <x-heroicon-o-arrow-path class="w-5 h-5 mr-2 -ml-1" wire:loading.class="animate-spin" />
                    Refresh
                </x-filament::button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Agents</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($agents) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Phone Numbers</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($phoneNumbers) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                    <div class="text-sm font-medium {{ isset($service) && $service ? 'text-success-600' : 'text-danger-600' }}">
                        {{ isset($service) && $service ? 'Connected' : 'Not Connected' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Phone Numbers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Phone Numbers ({{ count($phoneNumbers) }})
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nickname</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Webhook</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($phoneNumbers as $phone)
                            @php
                                $agentId = $phone['agent_id'] ?? ($phone['inbound_agent_id'] ?? null);
                                $agent = collect($agents)->firstWhere('agent_id', $agentId);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $phone['phone_number'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $phone['nickname'] ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($agent)
                                        <span class="text-green-600 dark:text-green-400">{{ $agent['agent_name'] }}</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">NOT SET</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($phone['inbound_webhook_url'] ?? false)
                                        <span class="text-green-600">✓ Set</span>
                                    @else
                                        <span class="text-red-600">✗ Missing</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Agents --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Agents ({{ count($agents) }})
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($agents as $agent)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow {{ $selectedAgent && $selectedAgent['agent_id'] === $agent['agent_id'] ? 'ring-2 ring-primary-500' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    ID: {{ substr($agent['agent_id'], 0, 8) }}...
                                </p>
                                @if(isset($agent['voice_id']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Voice: {{ $agent['voice_id'] }}
                                    </p>
                                @endif
                            </div>
                            <x-filament::button 
                                wire:click="selectAgent('{{ $agent['agent_id'] }}')"
                                size="sm"
                                color="gray"
                            >
                                View
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Selected Agent Details --}}
        @if($selectedAgent)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium mb-4">Agent Details: {{ $selectedAgent['agent_name'] }}</h3>
                <div class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $selectedAgent['agent_id'] }}</dd>
                    </div>
                    @if(isset($selectedAgent['voice_id']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAgent['voice_id'] }}</dd>
                        </div>
                    @endif
                    @if(isset($selectedAgent['language']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Language</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAgent['language'] }}</dd>
                        </div>
                    @endif
                    @if(isset($selectedAgent['response_engine']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Engine</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAgent['response_engine']['type'] ?? 'Unknown' }}</dd>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        {{-- Summary --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-400 mb-2">Configuration Summary</h3>
            <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                <li>• Total Agents: {{ count($agents) }}</li>
                <li>• Total Phone Numbers: {{ count($phoneNumbers) }}</li>
                <li>• Webhook URL: https://api.askproai.de/api/retell/webhook</li>
                <li>• Required Events: call_started, call_ended, call_analyzed</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>