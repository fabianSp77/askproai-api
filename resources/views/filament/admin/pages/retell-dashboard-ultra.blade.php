<x-filament-panels::page>
    <div class="space-y-6">
        @if($error)
            <div class="bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/20 rounded-lg p-4">
                <p class="text-danger-800 dark:text-danger-400">{{ $error }}</p>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="section-header">
                <h2 class="section-title">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h2>
                <div class="controls-bar">
                    <div class="tooltip">
                        <button wire:click="refreshLLMData" 
                                class="btn-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                        <span class="tooltip-content">Refresh all agent and LLM data</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-4 flex-wrap">
                <div class="tooltip">
                    <a href="{{ url('/automate-retell-configuration.php') }}" target="_blank"
                       class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Update All Agents & Webhooks
                    </a>
                    <span class="tooltip-content multiline">Batch update all agents with latest webhook URLs and configurations</span>
                </div>
                <div class="tooltip">
                    <a href="{{ url('/test-retell-comprehensive.php') }}" target="_blank"
                       class="btn-secondary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Test API Connections
                    </a>
                    <span class="tooltip-content multiline">Run comprehensive tests on all Retell API endpoints</span>
                </div>
                <div class="tooltip">
                    <a href="#" onclick="event.preventDefault(); alert('Coming soon: Create new agent wizard')"
                       class="btn-success flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create New Agent
                    </a>
                    <span class="tooltip-content">Create a new Retell agent</span>
                </div>
            </div>
            <div class="help-text mt-3">
                <svg class="help-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Use these quick actions to manage your Retell agents and ensure proper webhook configuration.</span>
            </div>
        </div>

        {{-- Phone Numbers Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="section-header">
                <h2 class="section-title">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Phone Numbers
                    <span class="status-indicator {{ count($phoneNumbers) > 0 ? 'active' : 'warning' }}">
                        {{ count($phoneNumbers) }} configured
                    </span>
                </h2>
                <div class="section-actions">
                    <div class="tooltip">
                        <a href="#" class="btn-icon text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </a>
                        <span class="tooltip-content">Add new phone number</span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nickname</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Webhook</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($phoneNumbers as $phone)
                            @php
                                $agentId = $phone['agent_id'] ?? ($phone['inbound_agent_id'] ?? null);
                                $agent = collect($agents)->firstWhere('agent_id', $agentId);
                                $webhookUrl = $phone['inbound_webhook_url'] ?? null;
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
                                            <span class="status-indicator active">Active</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span class="text-red-600 dark:text-red-400 font-medium">NO AGENT</span>
                                            <span class="status-indicator error">Not configured</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($webhookUrl)
                                        <span class="text-xs text-gray-600 dark:text-gray-400" title="{{ $webhookUrl }}">
                                            {{ parse_url($webhookUrl, PHP_URL_PATH) }}
                                        </span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">Not configured</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Enhanced Agent Groups --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="section-header mb-6">
                <h2 class="section-title">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Agent Details
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ count($groupedAgents) }} groups, {{ count($agents) }} versions)
                    </span>
                </h2>
                <div class="controls-bar">
                    <div class="search-container">
                        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="search" 
                               placeholder="Search agents..." 
                               class="search-input"
                               x-model="searchQuery"
                               @input="filterAgents()">
                    </div>
                    <select class="filter-select" x-model="sortBy" @change="sortAgents()">
                        <option value="name">Sort by Name</option>
                        <option value="version">Sort by Version (Newest)</option>
                        <option value="modified">Sort by Last Modified</option>
                        <option value="status">Sort by Status</option>
                    </select>
                </div>
            </div>
            
            <div class="space-y-4" x-data="retellUltraDashboard">
                @foreach($groupedAgents as $baseName => $group)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        {{-- Group Header --}}
                        <div 
                            @click="expandedGroups.includes('{{ $baseName }}') ? expandedGroups = expandedGroups.filter(g => g !== '{{ $baseName }}') : expandedGroups.push('{{ $baseName }}')"
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors bg-gray-50 dark:bg-gray-800/50"
                        >
                            <div class="flex items-center gap-3">
                                <svg x-show="!expandedGroups.includes('{{ $baseName }}')" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <svg x-show="expandedGroups.includes('{{ $baseName }}')" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                
                                <div>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                                        {{ $baseName }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $group['total_versions'] }} version{{ $group['total_versions'] > 1 ? 's' : '' }}
                                        @if(!empty($group['active_versions']))
                                            • Active: <span class="font-medium text-green-600 dark:text-green-400">{{ implode(', ', $group['active_versions']) }}</span>
                                        @else
                                            • <span class="text-orange-600 dark:text-orange-400">No active version</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Version List --}}
                        <div 
                            x-show="expandedGroups.includes('{{ $baseName }}')"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="border-t border-gray-200 dark:border-gray-700"
                        >
                            <div class="bg-gray-50/50 dark:bg-gray-900/20">
                                @foreach($group['versions'] as $idx => $version)
                                    @php
                                        $versionKey = $baseName . '_' . $idx;
                                    @endphp
                                    <div class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                        {{-- Version Header --}}
                                        <div 
                                            @click="expandedVersions['{{ $versionKey }}'] = !expandedVersions['{{ $versionKey }}']"
                                            class="p-4 cursor-pointer hover:bg-white dark:hover:bg-gray-800/50 transition-colors {{ $version['is_active'] ? 'bg-green-50/50 dark:bg-green-900/10' : '' }}"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <svg x-show="!expandedVersions['{{ $versionKey }}']" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <svg x-show="expandedVersions['{{ $versionKey }}']" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                    
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $version['version'] }}</span>
                                                            @if($version['is_active'])
                                                                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded">
                                                                    ACTIVE
                                                                </span>
                                                            @endif
                                                            @if($version['llm_model'])
                                                                <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded">
                                                                    {{ $version['llm_model'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <span>ID: {{ substr($version['agent_id'], 0, 20) }}...</span>
                                                            @if($version['last_modified'])
                                                                <span>Modified: {{ \Carbon\Carbon::parse($version['last_modified'])->diffForHumans() }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center gap-2">
                                                    @if(!empty($version['webhook_urls']))
                                                        <div class="tooltip">
                                                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100 rounded cursor-help">
                                                                Webhook ✓
                                                            </span>
                                                            <span class="tooltip-content">Webhook configured and active</span>
                                                        </div>
                                                    @else
                                                        <div class="tooltip">
                                                            <span class="text-xs px-2 py-1 bg-orange-100 text-orange-700 dark:bg-orange-800 dark:text-orange-100 rounded cursor-help">
                                                                No Webhook
                                                            </span>
                                                            <span class="tooltip-content multiline">No webhook configured. Calls won't be processed.</span>
                                                        </div>
                                                    @endif
                                                    @if(!empty($version['custom_functions']))
                                                        <div class="tooltip">
                                                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-100 rounded cursor-help">
                                                                {{ count($version['custom_functions']) }} Functions
                                                            </span>
                                                            <span class="tooltip-content">Custom functions for appointment booking</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Version Details (Expandable) --}}
                                        <div 
                                            x-show="expandedVersions['{{ $versionKey }}']"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700"
                                        >
                                            <div class="p-6 space-y-4">
                                                {{-- Basic Info --}}
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Configuration</h4>
                                                        <dl class="space-y-1 text-sm">
                                                            <div class="flex justify-between">
                                                                <dt class="text-gray-500 dark:text-gray-400">Voice:</dt>
                                                                <dd class="text-gray-900 dark:text-gray-100">{{ $version['voice_id'] ?? 'Not set' }}</dd>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <dt class="text-gray-500 dark:text-gray-400">Engine:</dt>
                                                                <dd class="text-gray-900 dark:text-gray-100">{{ $version['response_engine'] ?? 'Not set' }}</dd>
                                                            </div>
                                                            @if($version['temperature'] !== null)
                                                                <div class="flex justify-between">
                                                                    <dt class="text-gray-500 dark:text-gray-400">Temperature:</dt>
                                                                    <dd class="text-gray-900 dark:text-gray-100">{{ $version['temperature'] }}</dd>
                                                                </div>
                                                            @endif
                                                        </dl>
                                                    </div>
                                                    
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</h4>
                                                        <dl class="space-y-1 text-sm">
                                                            @if(!empty($version['assigned_phones']))
                                                                <div>
                                                                    <dt class="text-gray-500 dark:text-gray-400">Assigned to:</dt>
                                                                    @foreach($version['assigned_phones'] as $phone)
                                                                        <dd class="text-gray-900 dark:text-gray-100">{{ $phone['phone_number'] }} ({{ $phone['nickname'] ?? 'No name' }})</dd>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            @if(!empty($version['webhook_urls']))
                                                                <div>
                                                                    <dt class="text-gray-500 dark:text-gray-400">Webhooks:</dt>
                                                                    @foreach($version['webhook_urls'] as $url)
                                                                        <dd class="text-gray-900 dark:text-gray-100 text-xs truncate" title="{{ $url }}">{{ $url }}</dd>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </dl>
                                                    </div>
                                                </div>
                                                
                                                {{-- Prompt --}}
                                                @if($version['prompt'])
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prompt</h4>
                                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 max-h-40 overflow-y-auto">
                                                            <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $version['prompt'] }}</pre>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Custom Functions --}}
                                                @if(!empty($version['custom_functions']))
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Functions</h4>
                                                        <div class="space-y-2">
                                                            @foreach($version['custom_functions'] as $function)
                                                                <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                                                                    <div class="flex items-start justify-between">
                                                                        <div>
                                                                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                                                                {{ $function['name'] ?? 'Unnamed function' }}
                                                                            </p>
                                                                            @if(isset($function['description']))
                                                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                                    {{ $function['description'] }}
                                                                                </p>
                                                                            @endif
                                                                        </div>
                                                                        @if(isset($function['speak_during_execution']) && $function['speak_during_execution'])
                                                                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-100 rounded">
                                                                                Speaks
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    @if(isset($function['url']))
                                                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 font-mono">
                                                                            {{ $function['url'] }}
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Additional Settings --}}
                                                @if(!empty($version['boosted_keywords']) || !empty($version['pronunciation_guide']))
                                                    <div class="grid grid-cols-2 gap-4">
                                                        @if(!empty($version['boosted_keywords']))
                                                            <div>
                                                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boosted Keywords</h4>
                                                                <div class="flex flex-wrap gap-1">
                                                                    @foreach($version['boosted_keywords'] as $keyword)
                                                                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">{{ $keyword }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(!empty($version['pronunciation_guide']))
                                                            <div>
                                                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pronunciation Guide</h4>
                                                                <dl class="text-xs space-y-1">
                                                                    @foreach($version['pronunciation_guide'] as $guide)
                                                                        <div>
                                                                            <dt class="inline text-gray-600 dark:text-gray-400">{{ $guide['word'] ?? '' }}:</dt>
                                                                            <dd class="inline text-gray-900 dark:text-gray-100 ml-1">{{ $guide['pronunciation'] ?? '' }}</dd>
                                                                        </div>
                                                                    @endforeach
                                                                </dl>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Empty State for No Agents --}}
        @if(count($agents) === 0)
        <div class="empty-state bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12">
            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="empty-state-title">No Agents Found</h3>
            <p class="empty-state-description">Get started by creating your first Retell agent to handle incoming calls.</p>
            <div class="flex justify-center">
                <a href="#" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Your First Agent
                </a>
            </div>
        </div>
        @endif

        {{-- Summary Stats --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-100 dark:border-blue-800">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="text-base font-semibold text-blue-900 dark:text-blue-400">Dashboard Summary</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-blue-700 dark:text-blue-300">Total Agents:</span>
                    <span class="font-medium text-blue-900 dark:text-blue-100 ml-1">{{ count($agents) }}</span>
                </div>
                <div>
                    <span class="text-blue-700 dark:text-blue-300">Agent Groups:</span>
                    <span class="font-medium text-blue-900 dark:text-blue-100 ml-1">{{ count($groupedAgents) }}</span>
                </div>
                <div>
                    <span class="text-blue-700 dark:text-blue-300">Phone Numbers:</span>
                    <span class="font-medium text-blue-900 dark:text-blue-100 ml-1">{{ count($phoneNumbers) }}</span>
                </div>
                <div>
                    <span class="text-blue-700 dark:text-blue-300">LLMs Loaded:</span>
                    <span class="font-medium text-blue-900 dark:text-blue-100 ml-1">{{ count($llmConfigs) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Onboarding Tooltip --}}
    @if(count($agents) > 0 && count($agents) < 3)
    <div class="fixed bottom-4 right-4 max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 animate-slide-in" x-data="{ show: true }" x-show="show">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Pro Tip</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Create different agent versions for A/B testing. Use V1, V2, V3 naming convention for easy tracking.</p>
                </div>
            </div>
            <button @click="show = false" class="text-gray-400 hover:text-gray-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('retellUltraDashboard', () => ({
                    expandedGroups: [],
                    expandedVersions: {},
                    searchQuery: '',
                    sortBy: 'name',
                    
                    filterAgents() {
                        // This function would filter agents based on searchQuery
                        // For now, it's a placeholder for future implementation
                        console.log('Filtering agents:', this.searchQuery);
                    },
                    
                    sortAgents() {
                        // This function would sort agents based on sortBy
                        // For now, it's a placeholder for future implementation
                        console.log('Sorting by:', this.sortBy);
                    }
                }))
            })
        </script>
    @endpush
    
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/filament/admin/retell-ultimate-fixed.css') }}">
    @endpush
</x-filament-panels::page>