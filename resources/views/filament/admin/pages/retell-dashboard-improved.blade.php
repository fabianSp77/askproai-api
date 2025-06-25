<x-filament-panels::page>
    <div class="space-y-6">
        @if($error)
            <div class="bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/20 rounded-lg p-4">
                <p class="text-danger-800 dark:text-danger-400">{{ $error }}</p>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="flex gap-4 flex-wrap">
                <a href="{{ url('/fix-retell-complete-setup.php') }}" target="_blank" 
                   class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Fix All Configurations
                </a>
                <a href="{{ url('/automate-retell-configuration.php') }}" target="_blank"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Update All Agents & Webhooks
                </a>
                <a href="{{ url('/check-current-webhook-config.php') }}" target="_blank"
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Check Webhook Status
                </a>
            </div>
        </div>

        {{-- Phone Numbers Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Active Phone Numbers ({{ count($phoneNumbers) }})
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nickname</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active Agent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($phoneNumbers as $phone)
                            @php
                                $agentId = $phone['agent_id'] ?? ($phone['inbound_agent_id'] ?? null);
                                $agent = collect($agents)->firstWhere('agent_id', $agentId);
                                $hasWebhook = !empty($phone['inbound_webhook_url']);
                                $isConfigured = $agent && $hasWebhook;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $phone['phone_number'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $phone['nickname'] ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($agent)
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600 dark:text-green-400">{{ $agent['agent_name'] }}</span>
                                        </div>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-medium">NO AGENT</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($isConfigured)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            ✓ Configured
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            ✗ Needs Setup
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grouped Agents --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                Agent Groups ({{ count($groupedAgents) }} unique agents with {{ count($agents) }} total versions)
            </h2>
            
            <div class="space-y-4" x-data="{ expandedGroups: [] }">
                @foreach($groupedAgents as $baseName => $group)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        {{-- Group Header --}}
                        <div 
                            @click="expandedGroups.includes('{{ $baseName }}') ? expandedGroups = expandedGroups.filter(g => g !== '{{ $baseName }}') : expandedGroups.push('{{ $baseName }}')"
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <svg x-show="!expandedGroups.includes('{{ $baseName }}')" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <svg x-show="expandedGroups.includes('{{ $baseName }}')" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                
                                <div>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                                        {{ $baseName }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $group['total_versions'] }} version{{ $group['total_versions'] > 1 ? 's' : '' }} 
                                        @if($group['active_version'])
                                            • Active: <span class="font-medium text-green-600 dark:text-green-400">{{ $group['active_version'] }}</span>
                                        @else
                                            • <span class="text-orange-600 dark:text-orange-400">No active version</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 rounded {{ $group['has_webhook'] ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100' }}">
                                    Webhook {{ $group['has_webhook'] ? '✓' : '✗' }}
                                </span>
                                <span class="text-xs px-2 py-1 rounded {{ $group['has_events'] ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100' }}">
                                    Events {{ $group['has_events'] ? '✓' : '✗' }}
                                </span>
                                <span class="text-xs px-2 py-1 rounded {{ $group['has_functions'] ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100' }}">
                                    Functions {{ $group['has_functions'] ? '✓' : '✗' }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Version Details (Expandable) --}}
                        <div 
                            x-show="expandedGroups.includes('{{ $baseName }}')"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                            class="border-t border-gray-200 dark:border-gray-700"
                        >
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/30">
                                <div class="space-y-3">
                                    @foreach($group['versions'] as $version)
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 {{ $version['is_active'] ? 'ring-2 ring-green-500' : '' }}">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3">
                                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                                            {{ $version['version'] }}
                                                        </h4>
                                                        @if($version['is_active'])
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                                ACTIVE
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                                        ID: {{ substr($version['agent_id'], 0, 25) }}...
                                                    </p>
                                                    
                                                    <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                                        <div>
                                                            <span class="text-gray-600 dark:text-gray-400">Webhook:</span>
                                                            @if($version['webhook_url'])
                                                                <p class="text-xs text-green-600 dark:text-green-400 truncate" title="{{ $version['webhook_url'] }}">
                                                                    {{ parse_url($version['webhook_url'], PHP_URL_PATH) ?? $version['webhook_url'] }}
                                                                </p>
                                                            @else
                                                                <p class="text-xs text-red-600 dark:text-red-400">Not configured</p>
                                                            @endif
                                                        </div>
                                                        
                                                        <div>
                                                            <span class="text-gray-600 dark:text-gray-400">Events:</span>
                                                            @if(!empty($version['webhook_events']))
                                                                <p class="text-xs text-green-600 dark:text-green-400">
                                                                    {{ count($version['webhook_events']) }} configured
                                                                </p>
                                                            @else
                                                                <p class="text-xs text-red-600 dark:text-red-400">None</p>
                                                            @endif
                                                        </div>
                                                        
                                                        <div>
                                                            <span class="text-gray-600 dark:text-gray-400">Functions:</span>
                                                            @if(!empty($version['custom_functions']))
                                                                <p class="text-xs text-green-600 dark:text-green-400">
                                                                    {{ count($version['custom_functions']) }} functions
                                                                </p>
                                                            @else
                                                                <p class="text-xs text-red-600 dark:text-red-400">None</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                @if(!$version['is_active'])
                                                    <button 
                                                        class="ml-4 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                        onclick="alert('Switch to version {{ $version['version'] }} - Coming soon!')"
                                                    >
                                                        Activate
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Configuration Summary --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-400 mb-2">Configuration Requirements</h3>
            <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                <li>• Webhook URL: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">https://api.askproai.de/api/retell/webhook</code></li>
                <li>• Required Events: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">call_started</code>, <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">call_ended</code>, <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">call_analyzed</code></li>
                <li>• Custom Functions: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">collect_appointment_data</code>, <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">current_time_berlin</code></li>
            </ul>
        </div>
    </div>

    {{-- AlpineJS for expandable sections --}}
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('retellDashboard', () => ({
                    expandedGroups: []
                }))
            })
        </script>
    @endpush
</x-filament-panels::page>