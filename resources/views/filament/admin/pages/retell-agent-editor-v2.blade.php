<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left sidebar - Version list -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Agent Versions</h3>
                
                @if(count($versions) > 0)
                    <div class="space-y-2">
                        @foreach($versions as $version)
                            <button 
                                wire:click="selectVersion({{ $version['version'] }})"
                                class="w-full text-left p-3 rounded-lg transition-all duration-200
                                    {{ $selectedVersion == $version['version'] 
                                        ? 'bg-primary-100 dark:bg-primary-900 border-primary-500 border-2' 
                                        : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-sm">
                                        Version {{ $version['version'] }}
                                    </span>
                                    @if($version['is_published'])
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Published</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ \Carbon\Carbon::createFromTimestampMs($version['created_at'])->format('M d, Y H:i') }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No versions found</p>
                @endif
            </div>
        </div>

        <!-- Right side - Agent details -->
        <div class="lg:col-span-3">
            @if($selectedVersionData)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $selectedVersionData['agent_name'] ?? 'Unnamed Agent' }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Version {{ $selectedVersion }} • Agent ID: {{ $agentId }}
                                </p>
                            </div>
                            <div class="flex gap-3">
                                @if(!($versions[array_search($selectedVersion, array_column($versions, 'version'))]['is_published'] ?? false))
                                    <button 
                                        wire:click="publishVersion"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                    >
                                        Publish This Version
                                    </button>
                                @endif
                                <button 
                                    wire:click="exportAgent"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Export Configuration
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Content sections -->
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Basic Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ ($selectedVersionData['status'] ?? 'inactive') === 'active' ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Channel</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $selectedVersionData['channel'] ?? 'voice' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Settings -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Voice Settings</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice ID</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                        {{ $selectedVersionData['voice_id'] ?? 'Not set' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice Model</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['voice_model'] ?? 'Not set' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice Speed</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['voice_speed'] ?? '1.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Voice Temperature</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['voice_temperature'] ?? '0.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Volume</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['volume'] ?? '1.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ambient Sound</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['ambient_sound'] ?? 'None' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Language Settings -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Language Settings</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Language</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['language'] ?? 'Not set' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Normalize for Speech</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ ($selectedVersionData['normalize_for_speech'] ?? false) ? 'Yes' : 'No' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation Settings -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Conversation Settings</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Enable Backchannel</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ ($selectedVersionData['enable_backchannel'] ?? false) ? 'Yes' : 'No' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Backchannel Frequency</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['backchannel_frequency'] ?? '0.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Interruption Sensitivity</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['interruption_sensitivity'] ?? '0.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Responsiveness</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['responsiveness'] ?? '1.0' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Call After Silence (ms)</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['end_call_after_silence_ms'] ?? 'Not set' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Max Call Duration (ms)</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['max_call_duration_ms'] ?? 'Not set' }}
                                    </p>
                                </div>
                            </div>
                            
                            @if(isset($selectedVersionData['backchannel_words']) && is_array($selectedVersionData['backchannel_words']))
                                <div class="mt-4">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Backchannel Words</label>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach($selectedVersionData['backchannel_words'] as $word)
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm">{{ $word }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Response Engine -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Response Engine</h3>
                            @if(isset($selectedVersionData['response_engine']))
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ $selectedVersionData['response_engine']['type'] ?? 'Not set' }}
                                        </p>
                                    </div>
                                    @if(isset($selectedVersionData['response_engine']['llm_id']))
                                        <div>
                                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">LLM ID</label>
                                            <p class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                                {{ $selectedVersionData['response_engine']['llm_id'] }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- LLM Configuration if available -->
                                @if(isset($selectedVersionData['llm_configuration']))
                                    <div class="mt-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                        <h4 class="text-md font-medium mb-3 text-gray-900 dark:text-gray-100">LLM Configuration</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Model</label>
                                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $selectedVersionData['llm_configuration']['model'] ?? 'Not set' }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Temperature</label>
                                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $selectedVersionData['llm_configuration']['model_temperature'] ?? 'Not set' }}
                                                </p>
                                            </div>
                                            @if(isset($selectedVersionData['llm_configuration']['general_prompt']))
                                                <div>
                                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">General Prompt</label>
                                                    <div class="mt-1 bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600">
                                                        <pre class="text-xs text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $selectedVersionData['llm_configuration']['general_prompt'] }}</pre>
                                                    </div>
                                                </div>
                                            @endif
                                            @if(isset($selectedVersionData['llm_configuration']['begin_message']))
                                                <div>
                                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Begin Message</label>
                                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $selectedVersionData['llm_configuration']['begin_message'] }}
                                                    </p>
                                                </div>
                                            @endif
                                            @if(isset($selectedVersionData['llm_configuration']['general_tools']) && is_array($selectedVersionData['llm_configuration']['general_tools']))
                                                <div>
                                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Tools/Functions ({{ count($selectedVersionData['llm_configuration']['general_tools']) }})</label>
                                                    <div class="mt-2 space-y-2">
                                                        @foreach($selectedVersionData['llm_configuration']['general_tools'] as $tool)
                                                            <div class="bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-600">
                                                                <div class="font-medium text-sm">{{ $tool['name'] ?? 'Unknown Tool' }}</div>
                                                                @if(isset($tool['description']))
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $tool['description'] }}</div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No response engine configured</p>
                            @endif
                        </div>

                        <!-- Webhook Configuration -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Webhook Configuration</h3>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Webhook URL</label>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-mono break-all">
                                    {{ $selectedVersionData['webhook_url'] ?? 'Not set' }}
                                </p>
                            </div>
                        </div>

                        <!-- Post Call Analysis -->
                        @if(isset($selectedVersionData['post_call_analysis_data']) && is_array($selectedVersionData['post_call_analysis_data']))
                            <div class="border-b border-gray-100 dark:border-gray-700 pb-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Post Call Analysis Fields</h3>
                                <div class="space-y-2">
                                    @foreach($selectedVersionData['post_call_analysis_data'] as $field)
                                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                            <div class="font-medium text-sm">{{ $field['name'] ?? 'Unknown' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Type: {{ $field['type'] ?? 'unknown' }} • {{ $field['description'] ?? 'No description' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Raw JSON (Collapsible) -->
                        <div x-data="{ showJson: false }">
                            <button 
                                @click="showJson = !showJson"
                                class="flex items-center justify-between w-full text-left"
                            >
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Raw Configuration</h3>
                                <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': showJson }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="showJson" x-transition class="mt-4">
                                <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-xs">{{ json_encode($selectedVersionData, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Version Selected</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Select a version from the list to view its configuration</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>