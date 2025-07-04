<x-filament-panels::page>
    <style>
        .version-button {
            transition: all 0.2s ease;
        }
        
        .version-button:hover {
            transform: translateX(4px);
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .config-section {
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .config-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
    </style>
    
    <!-- Header with back button -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="/admin/retell-ultimate-control-center" 
               class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Control Center
            </a>
        </div>
        
        @if($agent)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Agent ID: {{ $agentId }}
            </div>
        @endif
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Version List (Left Side) -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Versions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ count($versions) }} version(s) available
                    </p>
                </div>
                
                <div class="p-2 space-y-1 max-h-[calc(100vh-300px)] overflow-y-auto">
                    @forelse($versions as $version)
                        <button
                            wire:click="selectVersion('{{ $version['version'] }}')"
                            class="version-button w-full text-left p-3 rounded-lg transition-colors {{ $selectedVersion === $version['version'] ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }} border {{ $selectedVersion === $version['version'] ? 'border-primary-300 dark:border-primary-700' : 'border-transparent' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                        v{{ $version['version'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        @if(isset($version['last_modification_timestamp']))
                                            {{ \Carbon\Carbon::createFromTimestampMs($version['last_modification_timestamp'])->format('M d, Y H:i') }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                @if(isset($version['is_published']) && $version['is_published'])
                                    <span class="ml-2 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400 rounded-full">
                                        Published
                                    </span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 p-4 text-center">
                            No versions found
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
        
        <!-- Agent Details (Right Side) -->
        <div class="lg:col-span-3">
            @if($selectedVersionData)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $selectedVersionData['agent_name'] ?? 'Unnamed Agent' }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Agent ID: {{ $agentId }} | Version: {{ $selectedVersion }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Activate Button --}}
                                @if(!$isActive)
                                    <x-filament::button
                                        wire:click="activateAgent"
                                        color="success"
                                        size="sm"
                                        icon="heroicon-o-check-circle"
                                    >
                                        Activate Agent
                                    </x-filament::button>
                                @else
                                    <x-filament::button
                                        wire:click="deactivateAgent"
                                        color="danger"
                                        size="sm"
                                        icon="heroicon-o-x-circle"
                                    >
                                        Deactivate Agent
                                    </x-filament::button>
                                @endif
                                
                                @if(!isset($selectedVersionData['is_published']) || !$selectedVersionData['is_published'])
                                    <x-filament::button
                                        wire:click="publishVersion('{{ $selectedVersion }}')"
                                        color="info"
                                        size="sm"
                                    >
                                        Publish This Version
                                    </x-filament::button>
                                @endif
                                <x-filament::button
                                    wire:click="exportConfiguration"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-arrow-down-tray"
                                >
                                    Export
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuration Sections -->
                    <div class="p-6 space-y-6 max-h-[calc(100vh-300px)] overflow-y-auto">
                        <!-- Basic Information -->
                        <div class="config-section">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Agent Type:</span>
                                    <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                        {{ ucfirst($selectedVersionData['agent_type'] ?? 'voice_agent') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Last Modified:</span>
                                    <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                        {{ isset($selectedVersionData['last_modification_timestamp']) ? \Carbon\Carbon::createFromTimestampMs($selectedVersionData['last_modification_timestamp'])->format('M d, Y H:i') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Response Engine -->
                        <div class="config-section">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Response Engine</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-3">
                                @if(isset($selectedVersionData['response_engine']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Type:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['response_engine']['type'] ?? 'N/A' }}
                                        </span>
                                    </div>
                                    
                                    @if(isset($selectedVersionData['response_engine']['llm_id']))
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">LLM ID:</span>
                                            <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                                {{ $selectedVersionData['response_engine']['llm_id'] }}
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        <!-- Voice Settings -->
                        <div class="config-section">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Voice Settings</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-3">
                                @if(isset($selectedVersionData['voice_id']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Voice ID:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['voice_id'] }}
                                        </span>
                                    </div>
                                @endif
                                
                                @if(isset($selectedVersionData['voice_temperature']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Temperature:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['voice_temperature'] }}
                                        </span>
                                    </div>
                                @endif
                                
                                @if(isset($selectedVersionData['voice_speed']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Speed:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['voice_speed'] }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Language & Pronunciation -->
                        <div class="config-section">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Language & Pronunciation</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-3">
                                @if(isset($selectedVersionData['language']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Language:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['language'] }}
                                        </span>
                                    </div>
                                @endif
                                
                                @if(isset($selectedVersionData['pronunciation_dictionary']) && is_array($selectedVersionData['pronunciation_dictionary']))
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">Pronunciation Dictionary:</span>
                                        <div class="space-y-1">
                                            @foreach($selectedVersionData['pronunciation_dictionary'] as $entry)
                                                <div class="text-sm">
                                                    <span class="font-mono bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                                                        {{ $entry['word'] ?? '' }}
                                                    </span>
                                                    →
                                                    <span class="font-mono bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                                                        {{ $entry['pronunciation'] ?? '' }}
                                                    </span>
                                                    @if(isset($entry['case_sensitive']) && $entry['case_sensitive'])
                                                        <span class="ml-2 text-xs text-amber-600 dark:text-amber-400">(case sensitive)</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Conversation Settings -->
                        <div class="config-section">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Conversation Settings</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 space-y-3">
                                @foreach(['ambient_sound', 'enable_voicemail_detection', 'voicemail_message', 'end_call_after_silence_ms', 'max_call_duration_ms'] as $field)
                                    @if(isset($selectedVersionData[$field]))
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ str_replace('_', ' ', ucfirst($field)) }}:
                                            </span>
                                            <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                                                @if(is_bool($selectedVersionData[$field]))
                                                    {{ $selectedVersionData[$field] ? 'Yes' : 'No' }}
                                                @else
                                                    {{ $selectedVersionData[$field] }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Webhook URL -->
                        @if(isset($selectedVersionData['webhook_url']))
                            <div class="config-section">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Webhook Configuration</h3>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">URL:</span>
                                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100 font-mono">
                                            {{ $selectedVersionData['webhook_url'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Custom Functions -->
                        @if(isset($selectedVersionData['custom_functions']) && is_array($selectedVersionData['custom_functions']) && count($selectedVersionData['custom_functions']) > 0)
                            <div class="config-section">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Custom Functions</h3>
                                <div class="space-y-3">
                                    @foreach($selectedVersionData['custom_functions'] as $function)
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $function['name'] ?? 'Unnamed Function' }}
                                            </h4>
                                            @if(isset($function['description']))
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ $function['description'] }}
                                                </p>
                                            @endif
                                            @if(isset($function['parameters']) && is_array($function['parameters']))
                                                <div class="mt-3">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Parameters:</span>
                                                    <div class="mt-1 space-y-1">
                                                        @foreach($function['parameters'] as $param => $config)
                                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                • {{ $param }}
                                                                @if(isset($config['required']) && $config['required'])
                                                                    <span class="text-red-600 dark:text-red-400">(required)</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Raw JSON (Collapsible) -->
                        <div x-data="{ open: false }" class="config-section">
                            <button
                                @click="open = !open"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                Raw Configuration JSON
                            </button>
                            
                            <div x-show="open" x-collapse class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-300 font-mono">{{ json_encode($selectedVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Select a version from the left to view details
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>