<x-filament-panels::page>
    <style>
        .diff-added {
            background-color: #d1f2d1;
            color: #0a5d0a;
        }
        .diff-removed {
            background-color: #fdd;
            color: #b00;
        }
        .diff-changed {
            background-color: #ffeaa7;
            color: #2d3436;
        }
        .version-timeline {
            position: relative;
            padding-left: 30px;
        }
        .version-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .version-dot {
            position: absolute;
            left: 6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #9ca3af;
        }
        .version-dot.published {
            background: #10b981;
        }
        .version-dot.selected {
            background: #3b82f6;
            width: 14px;
            height: 14px;
            left: 4px;
        }
        .search-highlight {
            background-color: #fef08a;
            padding: 0 2px;
            border-radius: 2px;
        }
    </style>

    @if(!$agentId)
        <div class="text-center p-8">
            <p class="text-gray-500">No agent ID provided</p>
            <a href="/admin/retell-ultimate-control-center" class="text-primary-600 hover:text-primary-500">
                Back to Control Center
            </a>
        </div>
    @else
        <div class="space-y-6">
            <!-- Enhanced Header with Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="/admin/retell-ultimate-control-center" 
                           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back
                        </a>
                        <div>
                            <h2 class="text-2xl font-bold">
                                {{ $agent['agent_name'] ?? 'Agent Editor' }}
                            </h2>
                            <p class="text-sm text-gray-500">
                                Agent ID: {{ $agentId }} • {{ count($versions) }} versions
                            </p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Search in Configuration -->
                        <div class="relative">
                            <input type="text" 
                                   id="config-search" 
                                   placeholder="Search configuration..." 
                                   class="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                   onkeyup="searchConfiguration(this.value)">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        
                        <!-- Activate/Deactivate Button -->
                        @if(!$isActive)
                            <button wire:click="activateAgent" 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Activate Agent
                            </button>
                        @else
                            <button wire:click="deactivateAgent" 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l4-4m-4 4l4 4m4-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Deactivate Agent
                            </button>
                        @endif
                        
                        <!-- Test Call Button -->
                        <button onclick="initiateTestCall()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Test Call
                        </button>
                        
                        <!-- Save Changes Button -->
                        <button onclick="saveChanges()" 
                                id="save-button"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed hidden">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                        
                        <!-- Export Button -->
                        <button onclick="exportConfiguration()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Left sidebar - Enhanced Version Timeline -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Version Timeline</h3>
                            <div class="flex items-center gap-2">
                                <!-- Expand/Collapse Toggle -->
                                <button onclick="toggleVersionList()" 
                                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1"
                                        title="Toggle version list">
                                    <svg id="version-toggle-icon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <!-- Compare Mode Toggle -->
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" id="compare-mode" class="sr-only" onchange="toggleCompareMode()">
                                    <div class="relative">
                                        <div class="block bg-gray-200 dark:bg-gray-700 w-10 h-6 rounded-full"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                    </div>
                                    <span class="ml-2 text-sm">Compare</span>
                                </label>
                            </div>
                        </div>
                        
                        @php
                            $activeVersion = null;
                            $otherVersions = [];
                            foreach($versions as $version) {
                                if ($selectedVersion == $version['version']) {
                                    $activeVersion = $version;
                                } else {
                                    $otherVersions[] = $version;
                                }
                            }
                        @endphp
                        
                        <div class="version-timeline space-y-3">
                            <!-- Active Version (Always Visible) -->
                            @if($activeVersion)
                                <div class="relative pl-6">
                                    <div class="version-dot published selected"></div>
                                    
                                    <div class="version-card p-3 rounded-lg cursor-pointer transition-all bg-blue-50 dark:bg-blue-900/20 border border-blue-300"
                                         data-version="{{ $activeVersion['version'] }}">
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-sm">v{{ $activeVersion['version'] }} (Active)</span>
                                            @if($activeVersion['is_published'])
                                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Live</span>
                                            @endif
                                        </div>
                                        
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ \Carbon\Carbon::createFromTimestampMs($activeVersion['last_modification_timestamp'] ?? 0)->diffForHumans() }}
                                        </div>
                                        
                                        <!-- Compare Checkbox (hidden by default) -->
                                        <input type="checkbox" 
                                               class="compare-checkbox hidden mt-2" 
                                               value="{{ $activeVersion['version'] }}"
                                               onclick="event.stopPropagation()">
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Other Versions (Collapsible) -->
                            <div id="other-versions" class="hidden space-y-3">
                                @foreach($otherVersions as $version)
                                    <div class="relative pl-6">
                                        <div class="version-dot {{ $version['is_published'] ? 'published' : '' }}"></div>
                                        
                                        <div class="version-card p-3 rounded-lg cursor-pointer transition-all bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600"
                                             onclick="selectVersion({{ $version['version'] }})"
                                             data-version="{{ $version['version'] }}">
                                            
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-sm">v{{ $version['version'] }}</span>
                                                @if($version['is_published'])
                                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Live</span>
                                                @endif
                                            </div>
                                            
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ \Carbon\Carbon::createFromTimestampMs($version['last_modification_timestamp'] ?? 0)->diffForHumans() }}
                                            </div>
                                            
                                            <!-- Compare Checkbox (hidden by default) -->
                                            <input type="checkbox" 
                                                   class="compare-checkbox hidden mt-2" 
                                                   value="{{ $version['version'] }}"
                                                   onclick="event.stopPropagation()">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Version Count Info -->
                        @if(count($otherVersions) > 0)
                            <div class="mt-3 text-sm text-gray-500 dark:text-gray-400 text-center" id="version-count">
                                <span id="version-count-text">{{ count($otherVersions) }} more version{{ count($otherVersions) !== 1 ? 's' : '' }}</span>
                            </div>
                        @endif
                        
                        <!-- Compare Button (hidden by default) -->
                        <button id="compare-button" 
                                class="hidden w-full mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                                onclick="compareVersions()">
                            Compare Selected
                        </button>
                    </div>
                    
                    <!-- Performance Metrics -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mt-4">
                        <h3 class="text-lg font-semibold mb-3">Performance</h3>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Success Rate</span>
                                    <span class="font-medium">92.5%</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 92.5%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Avg Duration</span>
                                    <span class="font-medium">3m 24s</span>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Total Calls</span>
                                    <span class="font-medium">1,247</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - Configuration Details -->
                <div class="lg:col-span-3">
                    <!-- Version Diff View (hidden by default) -->
                    <div id="diff-view" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Version Comparison</h3>
                            <button onclick="closeDiffView()" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div id="diff-content" class="space-y-4">
                            <!-- Diff content will be inserted here -->
                        </div>
                    </div>

                    @if($selectedVersionData)
                        <!-- Main Configuration View -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <!-- Tabs -->
                            <div class="border-b border-gray-200 dark:border-gray-700">
                                <nav class="flex -mb-px">
                                    <button onclick="showTab('overview')" class="tab-button active px-6 py-3 text-sm font-medium">
                                        Overview
                                    </button>
                                    <button onclick="showTab('voice')" class="tab-button px-6 py-3 text-sm font-medium">
                                        Voice & Language
                                    </button>
                                    <button onclick="showTab('llm')" class="tab-button px-6 py-3 text-sm font-medium">
                                        LLM & Prompts
                                    </button>
                                    <button onclick="showTab('functions')" class="tab-button px-6 py-3 text-sm font-medium">
                                        Functions
                                    </button>
                                    <button onclick="showTab('advanced')" class="tab-button px-6 py-3 text-sm font-medium">
                                        Advanced
                                    </button>
                                    <button onclick="showTab('raw')" class="tab-button px-6 py-3 text-sm font-medium">
                                        Raw JSON
                                    </button>
                                </nav>
                            </div>

                            <div class="p-6">
                                <!-- Overview Tab -->
                                <div id="tab-overview" class="tab-content">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Agent Name</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="text" 
                                                               id="agent_name" 
                                                               value="{{ $selectedVersionData['agent_name'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('agent_name', this.value)">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Version</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $selectedVersionData['version'] ?? 'N/A' }}
                                                        @if($selectedVersionData['is_published'] ?? false)
                                                            <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Published</span>
                                                        @endif
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <span class="inline-flex items-center gap-1">
                                                            <span class="w-2 h-2 rounded-full {{ ($selectedVersionData['status'] ?? 'inactive') === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                                            {{ ucfirst($selectedVersionData['status'] ?? 'inactive') }}
                                                        </span>
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Conversation Settings</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">End Call After Silence (seconds)</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="end_call_after_silence_ms" 
                                                               value="{{ ($selectedVersionData['end_call_after_silence_ms'] ?? 0) / 1000 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('end_call_after_silence_ms', this.value * 1000)"
                                                               min="0" step="1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Max Call Duration (minutes)</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="max_call_duration_ms" 
                                                               value="{{ ($selectedVersionData['max_call_duration_ms'] ?? 0) / 60000 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('max_call_duration_ms', this.value * 60000)"
                                                               min="1" step="1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Webhook URL</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="url" 
                                                               id="webhook_url" 
                                                               value="{{ $selectedVersionData['webhook_url'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 font-mono text-xs"
                                                               onchange="updateAgentField('webhook_url', this.value)"
                                                               placeholder="https://api.example.com/webhook">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Interruption Sensitivity</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="interruption_sensitivity" 
                                                               value="{{ $selectedVersionData['interruption_sensitivity'] ?? 1 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('interruption_sensitivity', parseFloat(this.value))"
                                                               min="0" max="1" step="0.1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Responsiveness</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="responsiveness" 
                                                               value="{{ $selectedVersionData['responsiveness'] ?? 1 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('responsiveness', parseFloat(this.value))"
                                                               min="0" max="1" step="0.1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Enable Voicemail Detection</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" 
                                                                   id="enable_voicemail_detection"
                                                                   {{ ($selectedVersionData['enable_voicemail_detection'] ?? false) ? 'checked' : '' }}
                                                                   onchange="updateAgentField('enable_voicemail_detection', this.checked)"
                                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                            <span class="ml-2 text-sm">Enabled</span>
                                                        </label>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Voicemail Message</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <textarea id="voicemail_message" 
                                                                  rows="3"
                                                                  class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                  onchange="updateAgentField('voicemail_message', this.value)"
                                                                  placeholder="Message to leave on voicemail...">{{ $selectedVersionData['voicemail_message'] ?? '' }}</textarea>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Post Call Analysis Model</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <select id="post_call_analysis_model" 
                                                                class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                onchange="updateAgentField('post_call_analysis_model', this.value)">
                                                            <option value="" {{ !isset($selectedVersionData['post_call_analysis_model']) ? 'selected' : '' }}>None</option>
                                                            <option value="gpt-4o" {{ ($selectedVersionData['post_call_analysis_model'] ?? '') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o</option>
                                                            <option value="gpt-4-turbo" {{ ($selectedVersionData['post_call_analysis_model'] ?? '') == 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                                                            <option value="gpt-3.5-turbo" {{ ($selectedVersionData['post_call_analysis_model'] ?? '') == 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                                        </select>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Ambient Sound</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <select id="ambient_sound" 
                                                                class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                onchange="updateAgentField('ambient_sound', this.value)">
                                                            <option value="" {{ !isset($selectedVersionData['ambient_sound']) ? 'selected' : '' }}>None</option>
                                                            <option value="office" {{ ($selectedVersionData['ambient_sound'] ?? '') == 'office' ? 'selected' : '' }}>Office</option>
                                                            <option value="cafe" {{ ($selectedVersionData['ambient_sound'] ?? '') == 'cafe' ? 'selected' : '' }}>Cafe</option>
                                                            <option value="call-center" {{ ($selectedVersionData['ambient_sound'] ?? '') == 'call-center' ? 'selected' : '' }}>Call Center</option>
                                                        </select>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Ambient Sound Volume</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="ambient_sound_volume" 
                                                               value="{{ $selectedVersionData['ambient_sound_volume'] ?? 0 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('ambient_sound_volume', parseFloat(this.value))"
                                                               min="0" max="1" step="0.1">
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <!-- Voice & Language Tab -->
                                <div id="tab-voice" class="tab-content hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Voice Configuration</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Voice ID</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="text" 
                                                               id="voice_id" 
                                                               value="{{ $selectedVersionData['voice_id'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 font-mono text-xs"
                                                               onchange="updateAgentField('voice_id', this.value)"
                                                               placeholder="e.g. eleven_turbo_v2_de_1 or custom_voice_...">
                                                        <p class="text-xs text-gray-500 mt-1">Enter voice ID directly (supports custom voices)</p>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Voice Model</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <select id="voice_model" 
                                                                class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                onchange="updateAgentField('voice_model', this.value)">
                                                            <option value="eleven_turbo_v2" {{ ($selectedVersionData['voice_model'] ?? '') == 'eleven_turbo_v2' ? 'selected' : '' }}>Eleven Turbo v2</option>
                                                            <option value="eleven_turbo_v2_5" {{ ($selectedVersionData['voice_model'] ?? '') == 'eleven_turbo_v2_5' ? 'selected' : '' }}>Eleven Turbo v2.5</option>
                                                            <option value="eleven_multilingual_v2" {{ ($selectedVersionData['voice_model'] ?? '') == 'eleven_multilingual_v2' ? 'selected' : '' }}>Eleven Multilingual v2</option>
                                                            <option value="openai_tts" {{ ($selectedVersionData['voice_model'] ?? '') == 'openai_tts' ? 'selected' : '' }}>OpenAI TTS</option>
                                                        </select>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Speed</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="voice_speed" 
                                                               value="{{ $selectedVersionData['voice_speed'] ?? '1.0' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('voice_speed', parseFloat(this.value))"
                                                               min="0.5" max="2.0" step="0.1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Temperature</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="voice_temperature" 
                                                               value="{{ $selectedVersionData['voice_temperature'] ?? '0.0' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('voice_temperature', parseFloat(this.value))"
                                                               min="0.0" max="2.0" step="0.1">
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Language Settings</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Language</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <select id="language" 
                                                                class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                onchange="updateAgentField('language', this.value)">
                                                            <option value="en-US" {{ ($selectedVersionData['language'] ?? '') == 'en-US' ? 'selected' : '' }}>English (US)</option>
                                                            <option value="de-DE" {{ ($selectedVersionData['language'] ?? '') == 'de-DE' ? 'selected' : '' }}>Deutsch</option>
                                                            <option value="es-ES" {{ ($selectedVersionData['language'] ?? '') == 'es-ES' ? 'selected' : '' }}>Español</option>
                                                        </select>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Begin Message</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <textarea id="begin_message" 
                                                                  rows="3"
                                                                  class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                  onchange="updateAgentField('begin_message', this.value)">{{ $selectedVersionData['begin_message'] ?? '' }}</textarea>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Enable Backchannel</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" 
                                                                   id="enable_backchannel"
                                                                   {{ ($selectedVersionData['enable_backchannel'] ?? false) ? 'checked' : '' }}
                                                                   onchange="updateAgentField('enable_backchannel', this.checked)"
                                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                            <span class="ml-2 text-sm">Enabled</span>
                                                        </label>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Backchannel Frequency</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="backchannel_frequency" 
                                                               value="{{ $selectedVersionData['backchannel_frequency'] ?? 0.2 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('backchannel_frequency', parseFloat(this.value))"
                                                               min="0" max="1" step="0.1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Backchannel Words</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <textarea id="backchannel_words" 
                                                                  rows="3"
                                                                  class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 text-xs"
                                                                  onchange="updateAgentField('backchannel_words', this.value.split(',').map(w => w.trim()))"
                                                                  placeholder="mhm, ach so, aha, okay, richtig">{{ isset($selectedVersionData['backchannel_words']) && is_array($selectedVersionData['backchannel_words']) ? implode(', ', $selectedVersionData['backchannel_words']) : '' }}</textarea>
                                                        <p class="text-xs text-gray-500 mt-1">Comma-separated list of backchannel words</p>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Volume</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="volume" 
                                                               value="{{ $selectedVersionData['volume'] ?? 1 }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('volume', parseFloat(this.value))"
                                                               min="0" max="2" step="0.1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Normalize for Speech</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" 
                                                                   id="normalize_for_speech"
                                                                   {{ ($selectedVersionData['normalize_for_speech'] ?? true) ? 'checked' : '' }}
                                                                   onchange="updateAgentField('normalize_for_speech', this.checked)"
                                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                            <span class="ml-2 text-sm">Enabled</span>
                                                        </label>
                                                    </dd>
                                                </div>
                                                @if(isset($selectedVersionData['pronunciation_dictionary']) && count($selectedVersionData['pronunciation_dictionary']) > 0)
                                                    <div class="searchable-content">
                                                        <dt class="text-sm font-medium text-gray-500">Pronunciation Dictionary</dt>
                                                        <dd class="mt-2 space-y-1">
                                                            @foreach($selectedVersionData['pronunciation_dictionary'] as $entry)
                                                                <div class="text-sm">
                                                                    <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $entry['word'] }}</span>
                                                                    → 
                                                                    <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $entry['pronunciation'] }}</span>
                                                                </div>
                                                            @endforeach
                                                        </dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <!-- LLM & Prompts Tab -->
                                <div id="tab-llm" class="tab-content hidden">
                                    @if(isset($selectedVersionData['response_engine']))
                                        <div class="space-y-6">
                                            <div>
                                                <h3 class="text-lg font-semibold mb-4">Response Engine</h3>
                                                <dl class="space-y-3">
                                                    <div class="searchable-content">
                                                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                                                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                            {{ $selectedVersionData['response_engine']['type'] ?? 'N/A' }}
                                                        </dd>
                                                    </div>
                                                    @if(isset($selectedVersionData['response_engine']['llm_id']))
                                                        <div class="searchable-content">
                                                            <dt class="text-sm font-medium text-gray-500">LLM ID</dt>
                                                            <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                                                {{ $selectedVersionData['response_engine']['llm_id'] }}
                                                            </dd>
                                                        </div>
                                                    @endif
                                                </dl>
                                            </div>
                                            
                                            @if(isset($selectedVersionData['llm_configuration']))
                                                <div>
                                                    <h3 class="text-lg font-semibold mb-4">LLM Configuration</h3>
                                                    <dl class="space-y-4">
                                                        <div class="searchable-content">
                                                            <dt class="text-sm font-medium text-gray-500">Model</dt>
                                                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                                <select id="llm_model" 
                                                                        class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                        onchange="updateLLMField('model', this.value)">
                                                                    <option value="gpt-4o" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o</option>
                                                                    <option value="gpt-4o-mini" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini</option>
                                                                    <option value="gpt-4-turbo" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                                                                    <option value="gpt-3.5-turbo" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                                                    <option value="claude-3-5-sonnet" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'claude-3-5-sonnet' ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                                                                    <option value="claude-3-opus" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'claude-3-opus' ? 'selected' : '' }}>Claude 3 Opus</option>
                                                                    <option value="claude-3-sonnet" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'claude-3-sonnet' ? 'selected' : '' }}>Claude 3 Sonnet</option>
                                                                    <option value="claude-3-haiku" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'claude-3-haiku' ? 'selected' : '' }}>Claude 3 Haiku</option>
                                                                    <option value="gemini-2.0-flash" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash</option>
                                                                    <option value="gemini-1.5-pro" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro</option>
                                                                    <option value="gemini-1.5-flash" {{ ($selectedVersionData['llm_configuration']['model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash</option>
                                                                </select>
                                                            </dd>
                                                        </div>
                                                        <div class="searchable-content">
                                                            <dt class="text-sm font-medium text-gray-500">Temperature</dt>
                                                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                                <input type="number" 
                                                                       id="model_temperature" 
                                                                       value="{{ $selectedVersionData['llm_configuration']['model_temperature'] ?? '0.7' }}"
                                                                       class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                       onchange="updateLLMField('model_temperature', parseFloat(this.value))"
                                                                       min="0.0" max="2.0" step="0.1">
                                                            </dd>
                                                        </div>
                                                        @if(isset($selectedVersionData['llm_configuration']['general_prompt']))
                                                            <div class="searchable-content">
                                                                <dt class="text-sm font-medium text-gray-500 mb-2">General Prompt</dt>
                                                                <dd>
                                                                    <div class="relative">
                                                                        <textarea id="general_prompt" 
                                                                                  rows="10"
                                                                                  class="w-full text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg font-mono"
                                                                                  onchange="updateAgentField('general_prompt', this.value)">{{ $selectedVersionData['llm_configuration']['general_prompt'] }}</textarea>
                                                                        <button onclick="copyToClipboard(document.getElementById('general_prompt').value)" 
                                                                                class="absolute top-2 right-2 p-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                </dd>
                                                            </div>
                                                        @endif
                                                    </dl>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-gray-500">No LLM configuration available</p>
                                    @endif
                                </div>

                                <!-- Functions Tab -->
                                <div id="tab-functions" class="tab-content hidden">
                                    @if(isset($selectedVersionData['llm_configuration']['general_tools']) && count($selectedVersionData['llm_configuration']['general_tools']) > 0)
                                        <div class="space-y-4">
                                            <h3 class="text-lg font-semibold mb-4">Available Functions ({{ count($selectedVersionData['llm_configuration']['general_tools']) }})</h3>
                                            @foreach($selectedVersionData['llm_configuration']['general_tools'] as $tool)
                                                <div class="searchable-content bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                                {{ $tool['name'] ?? 'Unknown Function' }}
                                                            </h4>
                                                            @if(isset($tool['description']))
                                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                                    {{ $tool['description'] }}
                                                                </p>
                                                            @endif
                                                            @if(isset($tool['parameters']) && isset($tool['parameters']['properties']))
                                                                <div class="mt-3">
                                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Parameters:</span>
                                                                    <div class="mt-1 space-y-1">
                                                                        @foreach($tool['parameters']['properties'] as $param => $config)
                                                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                                • {{ $param }}
                                                                                @if(isset($config['type']))
                                                                                    <span class="text-gray-500">({{ $config['type'] }})</span>
                                                                                @endif
                                                                                @if(in_array($param, $tool['parameters']['required'] ?? []))
                                                                                    <span class="text-red-600 dark:text-red-400">*</span>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button onclick="copyFunctionDefinition({{ json_encode($tool) }})" 
                                                                class="ml-4 p-2 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif(isset($selectedVersionData['custom_functions']) && count($selectedVersionData['custom_functions']) > 0)
                                        <div class="space-y-4">
                                            <h3 class="text-lg font-semibold mb-4">Custom Functions ({{ count($selectedVersionData['custom_functions']) }})</h3>
                                            @foreach($selectedVersionData['custom_functions'] as $function)
                                                <div class="searchable-content bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $function['name'] ?? 'Unknown Function' }}
                                                    </h4>
                                                    @if(isset($function['description']))
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ $function['description'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500">No functions configured</p>
                                    @endif
                                    
                                    <!-- Add/Edit Functions Section -->
                                    <div class="mt-6 border-t pt-6">
                                        <h3 class="text-lg font-semibold mb-4">Edit Functions (JSON)</h3>
                                        <div class="space-y-4">
                                            <div class="searchable-content">
                                                <dt class="text-sm font-medium text-gray-500 mb-2">Custom Functions JSON</dt>
                                                <dd>
                                                    <textarea id="custom_functions_json" 
                                                              rows="15"
                                                              class="w-full text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg font-mono"
                                                              onchange="updateAgentField('general_tools', JSON.parse(this.value))"
                                                              placeholder='[{"name": "function_name", "description": "Function description", "parameters": {"type": "object", "properties": {}, "required": []}}]'>{{ json_encode($selectedVersionData['llm_configuration']['general_tools'] ?? [], JSON_PRETTY_PRINT) }}</textarea>
                                                    <p class="text-xs text-gray-500 mt-2">Edit the JSON directly to add or modify functions. Must be valid JSON format.</p>
                                                </dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Tab -->
                                <div id="tab-advanced" class="tab-content hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Advanced Settings</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Denoising Mode</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <select id="denoising_mode" 
                                                                class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                                onchange="updateAgentField('denoising_mode', this.value)">
                                                            <option value="" {{ !isset($selectedVersionData['denoising_mode']) ? 'selected' : '' }}>None</option>
                                                            <option value="noise-cancellation" {{ ($selectedVersionData['denoising_mode'] ?? '') == 'noise-cancellation' ? 'selected' : '' }}>Noise Cancellation</option>
                                                            <option value="noise-and-background-speech-cancellation" {{ ($selectedVersionData['denoising_mode'] ?? '') == 'noise-and-background-speech-cancellation' ? 'selected' : '' }}>Noise & Background Speech Cancellation</option>
                                                        </select>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Opt Out Sensitive Data Storage</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" 
                                                                   id="opt_out_sensitive_data_storage"
                                                                   {{ ($selectedVersionData['opt_out_sensitive_data_storage'] ?? false) ? 'checked' : '' }}
                                                                   onchange="updateAgentField('opt_out_sensitive_data_storage', this.checked)"
                                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                            <span class="ml-2 text-sm">Opt Out</span>
                                                        </label>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Area Code</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="text" 
                                                               id="area_code" 
                                                               value="{{ $selectedVersionData['area_code'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('area_code', this.value)"
                                                               placeholder="e.g. +49">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Reminder Trigger (ms)</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="reminder_trigger_ms" 
                                                               value="{{ $selectedVersionData['reminder_trigger_ms'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('reminder_trigger_ms', parseInt(this.value))"
                                                               min="0" step="1000">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Reminder Max Count</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="reminder_max_count" 
                                                               value="{{ $selectedVersionData['reminder_max_count'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('reminder_max_count', parseInt(this.value))"
                                                               min="0" step="1">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Voicemail Max Duration (ms)</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="number" 
                                                               id="voicemail_max_duration_ms" 
                                                               value="{{ $selectedVersionData['voicemail_max_duration_ms'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600"
                                                               onchange="updateAgentField('voicemail_max_duration_ms', parseInt(this.value))"
                                                               min="0" step="1000">
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-lg font-semibold mb-4">Advanced Features</h3>
                                            <dl class="space-y-3">
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">LLM Websocket URL</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <input type="url" 
                                                               id="llm_websocket_url" 
                                                               value="{{ $selectedVersionData['llm_websocket_url'] ?? '' }}"
                                                               class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 font-mono text-xs"
                                                               onchange="updateAgentField('llm_websocket_url', this.value)"
                                                               placeholder="wss://example.com/websocket">
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Enable Transcription Formatting</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" 
                                                                   id="enable_transcription_formatting"
                                                                   {{ ($selectedVersionData['enable_transcription_formatting'] ?? false) ? 'checked' : '' }}
                                                                   onchange="updateAgentField('enable_transcription_formatting', this.checked)"
                                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                            <span class="ml-2 text-sm">Enabled</span>
                                                        </label>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Boosted Keywords</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <textarea id="boosted_keywords" 
                                                                  rows="3"
                                                                  class="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 text-xs"
                                                                  onchange="updateAgentField('boosted_keywords', this.value.split(',').map(w => w.trim()).filter(w => w))"
                                                                  placeholder="keyword1, keyword2, keyword3">{{ isset($selectedVersionData['boosted_keywords']) && is_array($selectedVersionData['boosted_keywords']) ? implode(', ', $selectedVersionData['boosted_keywords']) : '' }}</textarea>
                                                        <p class="text-xs text-gray-500 mt-1">Comma-separated list of keywords to boost in transcription</p>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Post Call Analysis Data</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        <textarea id="post_call_analysis_data" 
                                                                  rows="8"
                                                                  class="w-full text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded font-mono"
                                                                  onchange="updateAgentField('post_call_analysis_data', JSON.parse(this.value))"
                                                                  placeholder='[{"type": "string", "name": "field_name", "description": "Field description"}]'>{{ json_encode($selectedVersionData['post_call_analysis_data'] ?? [], JSON_PRETTY_PRINT) }}</textarea>
                                                        <p class="text-xs text-gray-500 mt-1">JSON array of post-call analysis fields</p>
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                    
                                    <!-- LLM Advanced Settings -->
                                    @if(isset($selectedVersionData['llm_configuration']))
                                    <div class="mt-6 border-t pt-6">
                                        <h3 class="text-lg font-semibold mb-4">LLM Advanced Settings</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="searchable-content">
                                                <dt class="text-sm font-medium text-gray-500">Model High Priority</dt>
                                                <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" 
                                                               id="model_high_priority"
                                                               {{ ($selectedVersionData['llm_configuration']['model_high_priority'] ?? false) ? 'checked' : '' }}
                                                               onchange="updateLLMField('model_high_priority', this.checked)"
                                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                        <span class="ml-2 text-sm">High Priority</span>
                                                    </label>
                                                </dd>
                                            </div>
                                            <div class="searchable-content">
                                                <dt class="text-sm font-medium text-gray-500">Tool Call Strict Mode</dt>
                                                <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" 
                                                               id="tool_call_strict_mode"
                                                               {{ ($selectedVersionData['llm_configuration']['tool_call_strict_mode'] ?? false) ? 'checked' : '' }}
                                                               onchange="updateLLMField('tool_call_strict_mode', this.checked)"
                                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                        <span class="ml-2 text-sm">Strict Mode</span>
                                                    </label>
                                                </dd>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Raw JSON Tab -->
                                <div id="tab-raw" class="tab-content hidden">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <h3 class="text-lg font-semibold">Full Configuration (Advanced)</h3>
                                            <div class="flex gap-2">
                                                <button onclick="formatJSON()" 
                                                        class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                                    Format JSON
                                                </button>
                                                <button onclick="validateJSON()" 
                                                        class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                                    Validate
                                                </button>
                                                <button onclick="copyToClipboard(document.getElementById('raw_json_editor').value)" 
                                                        class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700">
                                                    Copy JSON
                                                </button>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <textarea id="raw_json_editor" 
                                                      rows="25"
                                                      class="w-full text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg font-mono"
                                                      onchange="updateFullConfig(this.value)"
                                                      spellcheck="false">{{ json_encode($selectedVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                            <div id="json-error" class="hidden mt-2 text-red-600 text-sm"></div>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            <strong>Warning:</strong> Editing raw JSON is for advanced users only. Invalid JSON will be rejected. Make sure to validate before saving.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 text-center">
                            <p class="text-gray-500">Loading agent data...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <script>
        // Store current version data
        let currentVersionData = @json($selectedVersionData ?? []);
        let allVersions = @json($versions ?? []);
        let agentId = '{{ $agentId }}';
        
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
                button.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            event.target.classList.add('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
            event.target.classList.remove('text-gray-500', 'hover:text-gray-700');
        }
        
        // Version selection
        function selectVersion(version) {
            if (document.getElementById('compare-mode').checked) {
                // In compare mode, just toggle checkbox
                const card = document.querySelector(`[data-version="${version}"]`);
                const checkbox = card.querySelector('.compare-checkbox');
                checkbox.checked = !checkbox.checked;
            } else {
                // Normal mode - redirect to version
                window.location.href = `?agent_id=${agentId}&version=${version}`;
            }
        }
        
        // Version list state
        let versionListExpanded = false;
        
        // Toggle version list
        function toggleVersionList() {
            const otherVersions = document.getElementById('other-versions');
            const icon = document.getElementById('version-toggle-icon');
            const countText = document.getElementById('version-count-text');
            
            versionListExpanded = !versionListExpanded;
            
            if (versionListExpanded) {
                otherVersions.classList.remove('hidden');
                icon.classList.add('rotate-180');
                if (countText) {
                    countText.textContent = 'Collapse versions';
                }
            } else {
                otherVersions.classList.add('hidden');
                icon.classList.remove('rotate-180');
                if (countText) {
                    const count = otherVersions.querySelectorAll('.version-card').length;
                    countText.textContent = count + ' more version' + (count !== 1 ? 's' : '');
                }
            }
        }
        
        // Compare mode toggle
        function toggleCompareMode() {
            const compareMode = document.getElementById('compare-mode').checked;
            const checkboxes = document.querySelectorAll('.compare-checkbox');
            const compareButton = document.getElementById('compare-button');
            
            if (compareMode) {
                checkboxes.forEach(cb => cb.classList.remove('hidden'));
                compareButton.classList.remove('hidden');
                // Auto-expand version list in compare mode
                if (!versionListExpanded) {
                    toggleVersionList();
                }
            } else {
                checkboxes.forEach(cb => {
                    cb.classList.add('hidden');
                    cb.checked = false;
                });
                compareButton.classList.add('hidden');
                closeDiffView();
            }
        }
        
        // Compare versions
        function compareVersions() {
            const selected = Array.from(document.querySelectorAll('.compare-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length !== 2) {
                alert('Please select exactly 2 versions to compare');
                return;
            }
            
            // Show diff view
            document.getElementById('diff-view').classList.remove('hidden');
            
            // Generate diff content (simplified example)
            const diffContent = document.getElementById('diff-content');
            diffContent.innerHTML = '<p class="text-gray-500">Loading comparison...</p>';
            
            // Fetch both versions and compare
            fetch(`/api/mcp/retell/agent-compare/${agentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || '')
                },
                body: JSON.stringify({
                    version1: selected[0],
                    version2: selected[1]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.diff) {
                    let diffHtml = `
                        <div class="space-y-4">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-medium">Version ${selected[0]}</span>
                                <span class="text-sm text-gray-500">vs</span>
                                <span class="text-sm font-medium">Version ${selected[1]}</span>
                            </div>
                            <div class="space-y-2">
                    `;
                    
                    data.diff.forEach(change => {
                        if (change.type === 'added') {
                            diffHtml += `
                                <div class="diff-added p-3 rounded">
                                    <div class="font-mono text-xs">
                                        <span class="font-semibold">+ ${change.field}:</span>
                                        <span>${formatValue(change.new)}</span>
                                    </div>
                                </div>
                            `;
                        } else if (change.type === 'removed') {
                            diffHtml += `
                                <div class="diff-removed p-3 rounded">
                                    <div class="font-mono text-xs">
                                        <span class="font-semibold">- ${change.field}:</span>
                                        <span>${formatValue(change.old)}</span>
                                    </div>
                                </div>
                            `;
                        } else if (change.type === 'changed') {
                            diffHtml += `
                                <div class="diff-changed p-3 rounded">
                                    <div class="font-mono text-xs">
                                        <div class="font-semibold">~ ${change.field}:</div>
                                        <div class="ml-4">
                                            <div class="diff-removed inline">${formatValue(change.old)}</div>
                                            <span class="mx-2">→</span>
                                            <div class="diff-added inline">${formatValue(change.new)}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    });
                    
                    diffHtml += `
                            </div>
                        </div>
                    `;
                    
                    diffContent.innerHTML = diffHtml;
                } else {
                    diffContent.innerHTML = '<p class="text-red-500">Failed to load comparison</p>';
                }
            })
            .catch(error => {
                console.error('Error comparing versions:', error);
                diffContent.innerHTML = '<p class="text-red-500">Error loading comparison</p>';
            });
        }
        
        // Close diff view
        function closeDiffView() {
            document.getElementById('diff-view').classList.add('hidden');
        }
        
        // Search configuration
        function searchConfiguration(query) {
            const searchableElements = document.querySelectorAll('.searchable-content');
            
            searchableElements.forEach(element => {
                const text = element.textContent.toLowerCase();
                const regex = new RegExp(query.toLowerCase(), 'gi');
                
                if (query && text.includes(query.toLowerCase())) {
                    // Highlight matching text
                    element.innerHTML = element.innerHTML.replace(/<span class="search-highlight">|<\/span>/g, '');
                    element.innerHTML = element.innerHTML.replace(regex, match => `<span class="search-highlight">${match}</span>`);
                } else {
                    // Remove highlights
                    element.innerHTML = element.innerHTML.replace(/<span class="search-highlight">|<\/span>/g, '');
                }
            });
        }
        
        // Test call
        function initiateTestCall() {
            // Create a modal for phone number input
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.innerHTML = `
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                    <div class="mt-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Initiate Test Call</h3>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Phone Number (with country code)
                            </label>
                            <input type="tel" 
                                   id="test-phone-number" 
                                   placeholder="+49 123 456789" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600">
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Test Duration (seconds)
                                </label>
                                <select id="test-duration" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600">
                                    <option value="60">1 minute</option>
                                    <option value="120">2 minutes</option>
                                    <option value="180">3 minutes</option>
                                    <option value="300">5 minutes</option>
                                </select>
                            </div>
                            
                            <div class="mt-4">
                                <details class="text-sm">
                                    <summary class="cursor-pointer text-primary-600 hover:text-primary-700">Test Scenarios</summary>
                                    <div id="test-scenarios" class="mt-2 space-y-2 text-gray-600 dark:text-gray-400">
                                        Loading scenarios...
                                    </div>
                                </details>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6 space-x-3">
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button onclick="executeTestCall()" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Start Call
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Load test scenarios
            fetch(`/api/mcp/retell/test-scenarios/${agentId}`, {
                headers: {
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || '')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.scenarios) {
                    const scenariosHtml = data.scenarios.map(scenario => `
                        <div class="border-l-2 border-gray-300 pl-3">
                            <div class="font-medium">${scenario.name}</div>
                            <div class="text-xs">${scenario.description}</div>
                        </div>
                    `).join('');
                    document.getElementById('test-scenarios').innerHTML = scenariosHtml;
                }
            });
        }
        
        // Execute test call
        function executeTestCall() {
            const phoneNumber = document.getElementById('test-phone-number').value;
            const duration = document.getElementById('test-duration').value;
            
            if (!phoneNumber) {
                alert('Please enter a phone number');
                return;
            }
            
            // Show loading state
            const modal = document.querySelector('.fixed');
            const startButton = modal.querySelector('button[onclick="executeTestCall()"]');
            startButton.disabled = true;
            startButton.textContent = 'Initiating...';
            
            fetch('/api/mcp/retell/test-call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || '')
                },
                body: JSON.stringify({
                    agent_id: agentId,
                    phone_number: phoneNumber,
                    test_duration: parseInt(duration)
                })
            })
            .then(response => response.json())
            .then(data => {
                modal.remove();
                
                if (data.success) {
                    // Show success message with call details
                    const successModal = document.createElement('div');
                    successModal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
                    successModal.innerHTML = `
                        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                            <div class="mt-3 text-center">
                                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mt-4">Test Call Initiated!</h3>
                                <div class="mt-2 px-7 py-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Calling ${phoneNumber}...
                                    </p>
                                    ${data.call_id ? `<p class="text-xs text-gray-400 mt-2">Call ID: ${data.call_id}</p>` : ''}
                                </div>
                                <div class="mt-4">
                                    <button onclick="this.closest('.fixed').remove()" 
                                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                        OK
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(successModal);
                    
                    // Track the call status if call_id is available
                    if (data.call_id) {
                        trackCallStatus(data.call_id);
                    }
                } else {
                    alert('Failed to initiate test call: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                modal.remove();
                console.error('Error initiating test call:', error);
                alert('Error initiating test call. Please try again.');
            });
        }
        
        // Track call status
        function trackCallStatus(callId) {
            // Poll for call status updates
            const pollInterval = setInterval(() => {
                fetch(`/api/mcp/retell/test-call/${callId}/status`, {
                    headers: {
                        'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || '')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ended' || data.status === 'error') {
                        clearInterval(pollInterval);
                        
                        // Show notification
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg';
                        toast.innerHTML = `
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Test call ${data.status === 'ended' ? 'completed' : 'failed'}
                                ${data.duration ? ` (${Math.round(data.duration / 1000)}s)` : ''}
                            </div>
                        `;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 5000);
                    }
                })
                .catch(() => {
                    clearInterval(pollInterval);
                });
            }, 5000); // Check every 5 seconds
            
            // Stop polling after 10 minutes
            setTimeout(() => clearInterval(pollInterval), 600000);
        }
        
        // Export configuration
        function exportConfiguration() {
            const dataStr = JSON.stringify(currentVersionData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `agent-${agentId}-v${currentVersionData.version}-${new Date().toISOString().slice(0,10)}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg';
                toast.textContent = 'Copied to clipboard!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            });
        }
        
        // Copy function definition
        function copyFunctionDefinition(func) {
            copyToClipboard(JSON.stringify(func, null, 2));
        }
        
        // Format value for diff display
        function formatValue(value) {
            if (value === null) return 'null';
            if (value === undefined) return 'undefined';
            if (typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }
            return String(value);
        }
        
        // Track changes
        let pendingChanges = {};
        
        // Update agent field
        function updateAgentField(field, value) {
            // Store the change
            pendingChanges[field] = value;
            
            // Show save button
            document.getElementById('save-button').classList.remove('hidden');
            
            // Mark field as changed
            const fieldElement = document.getElementById(field);
            if (fieldElement) {
                fieldElement.classList.add('border-yellow-500', 'ring-1', 'ring-yellow-500');
            }
        }
        
        // Update LLM field
        function updateLLMField(field, value) {
            // LLM fields need to be prefixed for the backend
            updateAgentField(field, value);
        }
        
        // Save changes to Retell
        function saveChanges() {
            const saveButton = document.getElementById('save-button');
            
            // Disable button and show loading state
            saveButton.disabled = true;
            saveButton.innerHTML = `
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;
            
            // Send update request
            fetch(`/api/mcp/retell/update-agent/${agentId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(pendingChanges)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Changes saved successfully!', 'success');
                    
                    // Clear pending changes
                    pendingChanges = {};
                    
                    // Hide save button
                    saveButton.classList.add('hidden');
                    
                    // Remove changed styling from fields
                    document.querySelectorAll('.border-yellow-500').forEach(el => {
                        el.classList.remove('border-yellow-500', 'ring-1', 'ring-yellow-500');
                    });
                    
                    // Update current version data
                    Object.assign(currentVersionData, pendingChanges);
                    
                    // Refresh the page to get latest data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Failed to save changes: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error saving changes:', error);
                showNotification('Error saving changes. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable button
                saveButton.disabled = false;
                saveButton.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                `;
            });
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const bgColor = type === 'success' ? 'bg-green-600' : 
                           type === 'error' ? 'bg-red-600' : 
                           'bg-blue-600';
            
            const notification = document.createElement('div');
            notification.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'success' ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                          type === 'error' ?
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                    </svg>
                    ${message}
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => notification.remove(), 5000);
        }
        
        // Update full configuration from raw JSON
        function updateFullConfig(jsonString) {
            try {
                const config = JSON.parse(jsonString);
                
                // Store all fields as pending changes
                Object.keys(config).forEach(key => {
                    pendingChanges[key] = config[key];
                });
                
                // Show save button
                document.getElementById('save-button').classList.remove('hidden');
                
                // Clear error
                document.getElementById('json-error').classList.add('hidden');
            } catch (e) {
                document.getElementById('json-error').textContent = 'Invalid JSON: ' + e.message;
                document.getElementById('json-error').classList.remove('hidden');
            }
        }
        
        // Format JSON in editor
        function formatJSON() {
            const editor = document.getElementById('raw_json_editor');
            try {
                const json = JSON.parse(editor.value);
                editor.value = JSON.stringify(json, null, 2);
                showNotification('JSON formatted successfully', 'success');
            } catch (e) {
                showNotification('Invalid JSON: ' + e.message, 'error');
            }
        }
        
        // Validate JSON
        function validateJSON() {
            const editor = document.getElementById('raw_json_editor');
            try {
                JSON.parse(editor.value);
                showNotification('JSON is valid!', 'success');
                document.getElementById('json-error').classList.add('hidden');
            } catch (e) {
                showNotification('Invalid JSON: ' + e.message, 'error');
                document.getElementById('json-error').textContent = 'Invalid JSON: ' + e.message;
                document.getElementById('json-error').classList.remove('hidden');
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial active tab
            const firstTabButton = document.querySelector('.tab-button');
            if (firstTabButton) {
                firstTabButton.classList.add('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
                firstTabButton.classList.remove('text-gray-500', 'hover:text-gray-700');
            }
        });
    </script>

    <style>
        .tab-button.active {
            @apply border-b-2 border-primary-600 text-primary-600;
        }
        .tab-button:not(.active) {
            @apply text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
        }
        #compare-mode:checked ~ .dot {
            transform: translateX(100%);
            @apply bg-primary-600;
        }
    </style>
</x-filament-panels::page>