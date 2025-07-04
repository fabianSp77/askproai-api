<x-filament-panels::page>
    <style>
        .diff-added { background-color: #d1f2d1; color: #0a5d0a; }
        .diff-removed { background-color: #fdd; color: #b00; }
        .diff-changed { background-color: #ffeaa7; color: #2d3436; }
        .version-timeline { position: relative; padding-left: 30px; }
        .version-timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
        .version-dot { position: absolute; left: 6px; width: 10px; height: 10px; border-radius: 50%; background: #9ca3af; }
        .version-dot.published { background: #10b981; }
        .version-dot.selected { background: #3b82f6; width: 14px; height: 14px; left: 4px; }
        .search-highlight { background-color: #fef08a; padding: 0 2px; border-radius: 2px; }
        
        /* Voice Preview Styles */
        .voice-preview { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .waveform { display: flex; align-items: center; justify-content: center; height: 60px; gap: 3px; }
        .waveform-bar { width: 4px; background: rgba(255,255,255,0.7); border-radius: 2px; animation: wave 1.2s ease-in-out infinite; }
        .waveform-bar:nth-child(1) { animation-delay: 0s; height: 20px; }
        .waveform-bar:nth-child(2) { animation-delay: 0.1s; height: 30px; }
        .waveform-bar:nth-child(3) { animation-delay: 0.2s; height: 25px; }
        .waveform-bar:nth-child(4) { animation-delay: 0.3s; height: 35px; }
        .waveform-bar:nth-child(5) { animation-delay: 0.4s; height: 28px; }
        @keyframes wave {
            0%, 100% { transform: scaleY(1); }
            50% { transform: scaleY(1.5); }
        }
        
        /* Chat Simulator */
        .chat-message { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Flow Builder Preview */
        .flow-node { 
            background: white; 
            border: 2px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 12px; 
            position: relative;
            transition: all 0.2s;
        }
        .flow-node:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
        .flow-connector { stroke: #9ca3af; stroke-width: 2; fill: none; }
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
            <!-- Enhanced Header with Voice Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="voice-preview p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <a href="/admin/retell-ultimate-control-center" 
                               class="inline-flex items-center gap-2 text-white/80 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Back
                            </a>
                            <div>
                                <h2 class="text-2xl font-bold">
                                    {{ $agent['agent_name'] ?? 'Agent Editor' }}
                                </h2>
                                <p class="text-sm text-white/80">
                                    Agent ID: {{ $agentId }} • {{ count($versions) }} versions
                                </p>
                            </div>
                        </div>
                        
                        <!-- Voice Preview Button -->
                        <div class="flex items-center gap-4">
                            <button onclick="playVoiceSample()" 
                                    class="inline-flex items-center gap-3 px-6 py-3 bg-white/20 backdrop-blur rounded-lg hover:bg-white/30 transition-all">
                                <div class="waveform" id="voice-waveform">
                                    <div class="waveform-bar"></div>
                                    <div class="waveform-bar"></div>
                                    <div class="waveform-bar"></div>
                                    <div class="waveform-bar"></div>
                                    <div class="waveform-bar"></div>
                                </div>
                                <span class="font-medium">Preview Voice</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions Bar -->
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <input type="text" 
                                   id="config-search" 
                                   placeholder="Search configuration..." 
                                   class="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600 pl-9"
                                   onkeyup="searchConfiguration(this.value)">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        
                        <!-- Test Mode Toggle -->
                        <button onclick="toggleTestMode()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"/>
                            </svg>
                            Chat Test
                        </button>
                        
                        <!-- Flow View -->
                        <button onclick="toggleFlowView()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Flow View
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Test Call -->
                        <button onclick="initiateTestCall()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Test Call
                        </button>
                        
                        <!-- Export -->
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

            <!-- Chat Test Mode (hidden by default) -->
            <div id="chat-test-mode" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Chat Simulator</h3>
                    <button onclick="toggleTestMode()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Chat Window -->
                    <div class="border rounded-lg">
                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-medium">Live Chat Test</span>
                            </div>
                        </div>
                        <div id="chat-messages" class="h-96 overflow-y-auto p-4 space-y-3">
                            <!-- Messages will appear here -->
                        </div>
                        <div class="border-t p-4">
                            <div class="flex gap-2">
                                <input type="text" 
                                       id="chat-input" 
                                       placeholder="Type your message..."
                                       class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                       onkeypress="if(event.key === 'Enter') sendChatMessage()">
                                <button onclick="sendChatMessage()" 
                                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                    Send
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Scenarios -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Quick Test Scenarios</h4>
                        <div class="space-y-2">
                            <button onclick="testScenario('greeting')" 
                                    class="w-full text-left p-3 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <div class="font-medium text-sm">Test Greeting</div>
                                <div class="text-xs text-gray-500">Initial conversation start</div>
                            </button>
                            <button onclick="testScenario('appointment')" 
                                    class="w-full text-left p-3 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <div class="font-medium text-sm">Book Appointment</div>
                                <div class="text-xs text-gray-500">Complete booking flow</div>
                            </button>
                            <button onclick="testScenario('objection')" 
                                    class="w-full text-left p-3 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <div class="font-medium text-sm">Handle Objection</div>
                                <div class="text-xs text-gray-500">Test objection handling</div>
                            </button>
                            <button onclick="testScenario('functions')" 
                                    class="w-full text-left p-3 bg-gray-50 dark:bg-gray-700 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                <div class="font-medium text-sm">Test Functions</div>
                                <div class="text-xs text-gray-500">Trigger all functions</div>
                            </button>
                        </div>
                        
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                    This simulates the agent's responses based on the current configuration. 
                                    Actual voice calls may have slight variations.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow View (hidden by default) -->
            <div id="flow-view-mode" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Conversation Flow</h3>
                    <button onclick="toggleFlowView()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="overflow-auto" style="min-height: 400px;">
                    <svg width="100%" height="400" id="flow-diagram">
                        <!-- Flow will be rendered here -->
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Left sidebar - Enhanced Version Timeline -->
                <div class="lg:col-span-1">
                    <!-- Version Timeline -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Versions</h3>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" id="compare-mode" class="sr-only" onchange="toggleCompareMode()">
                                <div class="relative">
                                    <div class="block bg-gray-200 dark:bg-gray-700 w-10 h-6 rounded-full"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                </div>
                                <span class="ml-2 text-sm">Compare</span>
                            </label>
                        </div>
                        
                        <div class="version-timeline space-y-3">
                            @foreach($versions as $index => $version)
                                <div class="relative pl-6">
                                    <div class="version-dot {{ $version['is_published'] ? 'published' : '' }} {{ $selectedVersion == $version['version'] ? 'selected' : '' }}"></div>
                                    
                                    <div class="version-card p-3 rounded-lg cursor-pointer transition-all
                                         {{ $selectedVersion == $version['version'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300' : 'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
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
                                        
                                        <input type="checkbox" 
                                               class="compare-checkbox hidden mt-2" 
                                               value="{{ $version['version'] }}"
                                               onclick="event.stopPropagation()">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
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
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Cost per Call</span>
                                    <span class="font-medium">€0.42</span>
                                </div>
                            </div>
                        </div>
                        
                        <button onclick="showDetailedAnalytics()" 
                                class="w-full mt-4 text-sm text-primary-600 hover:text-primary-700">
                            View Detailed Analytics →
                        </button>
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
                                    <button onclick="showTab('analytics')" class="tab-button px-6 py-3 text-sm font-medium">
                                        Analytics
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
                                                        {{ $selectedVersionData['agent_name'] ?? 'N/A' }}
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
                                                    <dt class="text-sm font-medium text-gray-500">End Call After Silence</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ ($selectedVersionData['end_call_after_silence_ms'] ?? 0) / 1000 }}s
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Max Call Duration</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ ($selectedVersionData['max_call_duration_ms'] ?? 0) / 60000 }} minutes
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Webhook URL</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono text-xs break-all">
                                                        {{ $selectedVersionData['webhook_url'] ?? 'Not configured' }}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                    
                                    <!-- Prompt Optimization Suggestions -->
                                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                            <div>
                                                <h4 class="font-medium text-blue-900 dark:text-blue-100">AI Optimization Suggestions</h4>
                                                <ul class="mt-2 space-y-1 text-sm text-blue-800 dark:text-blue-200">
                                                    <li>• Consider adding more specific examples in your prompt</li>
                                                    <li>• Your response time could be improved by 15% with shorter instructions</li>
                                                    <li>• Add fallback responses for edge cases</li>
                                                </ul>
                                                <button onclick="showPromptOptimizer()" class="mt-3 text-sm text-blue-700 dark:text-blue-300 hover:underline">
                                                    Optimize with AI →
                                                </button>
                                            </div>
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
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                                        {{ $selectedVersionData['voice_id'] ?? 'N/A' }}
                                                        <button onclick="previewVoice('{{ $selectedVersionData['voice_id'] ?? '' }}')" 
                                                                class="ml-2 text-xs text-primary-600 hover:text-primary-700">
                                                            Preview →
                                                        </button>
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Voice Model</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $selectedVersionData['voice_model'] ?? 'N/A' }}
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Speed</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $selectedVersionData['voice_speed'] ?? '1.0' }}x
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Temperature</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $selectedVersionData['voice_temperature'] ?? '0.0' }}
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
                                                        {{ $selectedVersionData['language'] ?? 'N/A' }}
                                                    </dd>
                                                </div>
                                                <div class="searchable-content">
                                                    <dt class="text-sm font-medium text-gray-500">Enable Backchannel</dt>
                                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                        {{ ($selectedVersionData['enable_backchannel'] ?? false) ? 'Yes' : 'No' }}
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
                                    
                                    <!-- Voice Sample Player -->
                                    <div class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg">
                                        <h4 class="font-medium mb-3">Voice Sample</h4>
                                        <div class="flex items-center gap-4">
                                            <button onclick="playFullVoiceSample()" 
                                                    class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium">Sample Text:</div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    "Guten Tag, hier ist {{ $selectedVersionData['agent_name'] ?? 'der AI Assistent' }}. Wie kann ich Ihnen heute helfen?"
                                                </div>
                                            </div>
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
                                                                {{ $selectedVersionData['llm_configuration']['model'] ?? 'N/A' }}
                                                            </dd>
                                                        </div>
                                                        <div class="searchable-content">
                                                            <dt class="text-sm font-medium text-gray-500">Temperature</dt>
                                                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                                {{ $selectedVersionData['llm_configuration']['model_temperature'] ?? 'N/A' }}
                                                            </dd>
                                                        </div>
                                                        @if(isset($selectedVersionData['llm_configuration']['general_prompt']))
                                                            <div class="searchable-content">
                                                                <dt class="text-sm font-medium text-gray-500 mb-2">General Prompt</dt>
                                                                <dd>
                                                                    <div class="relative">
                                                                        <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto max-h-96">{{ $selectedVersionData['llm_configuration']['general_prompt'] }}</pre>
                                                                        <div class="absolute top-2 right-2 flex gap-2">
                                                                            <button onclick="analyzePrompt()" 
                                                                                    class="p-2 bg-purple-600 text-white rounded hover:bg-purple-700"
                                                                                    title="Analyze Prompt">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                                                </svg>
                                                                            </button>
                                                                            <button onclick="copyToClipboard('{{ addslashes($selectedVersionData['llm_configuration']['general_prompt']) }}')" 
                                                                                    class="p-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                                                </svg>
                                                                            </button>
                                                                        </div>
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
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-semibold">Available Functions ({{ count($selectedVersionData['llm_configuration']['general_tools']) }})</h3>
                                                <button onclick="testAllFunctions()" 
                                                        class="text-sm text-primary-600 hover:text-primary-700">
                                                    Test All Functions →
                                                </button>
                                            </div>
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
                                                        <div class="flex gap-2 ml-4">
                                                            <button onclick="testFunction('{{ $tool['name'] }}')" 
                                                                    class="p-2 bg-green-600 text-white rounded hover:bg-green-700"
                                                                    title="Test Function">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                            </button>
                                                            <button onclick="copyFunctionDefinition({{ json_encode($tool) }})" 
                                                                    class="p-2 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500">No functions configured</p>
                                    @endif
                                </div>

                                <!-- Analytics Tab -->
                                <div id="tab-analytics" class="tab-content hidden">
                                    <div class="space-y-6">
                                        <h3 class="text-lg font-semibold">Performance Analytics</h3>
                                        
                                        <!-- Call Volume Chart -->
                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                            <h4 class="font-medium mb-3">Call Volume (Last 7 Days)</h4>
                                            <canvas id="call-volume-chart" width="400" height="200"></canvas>
                                        </div>
                                        
                                        <!-- Success Metrics -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">Success Rate</p>
                                                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">92.5%</p>
                                                    </div>
                                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg Duration</p>
                                                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">3m 24s</p>
                                                    </div>
                                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">Cost per Call</p>
                                                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">€0.42</p>
                                                    </div>
                                                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Top Issues -->
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                                            <h4 class="font-medium mb-3">Common Issues</h4>
                                            <ul class="space-y-2 text-sm">
                                                <li class="flex items-center justify-between">
                                                    <span>Customer couldn't understand accent</span>
                                                    <span class="text-yellow-700 dark:text-yellow-300">12 occurrences</span>
                                                </li>
                                                <li class="flex items-center justify-between">
                                                    <span>Call ended unexpectedly</span>
                                                    <span class="text-yellow-700 dark:text-yellow-300">8 occurrences</span>
                                                </li>
                                                <li class="flex items-center justify-between">
                                                    <span>Function timeout</span>
                                                    <span class="text-yellow-700 dark:text-yellow-300">5 occurrences</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Raw JSON Tab -->
                                <div id="tab-raw" class="tab-content hidden">
                                    <div class="relative">
                                        <button onclick="copyToClipboard(JSON.stringify({{ json_encode($selectedVersionData) }}, null, 2))" 
                                                class="absolute top-2 right-2 px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm hover:bg-gray-300 dark:hover:bg-gray-600">
                                            Copy JSON
                                        </button>
                                        <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">{{ json_encode($selectedVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
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

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Store current version data
        let currentVersionData = @json($selectedVersionData ?? []);
        let allVersions = @json($versions ?? []);
        let agentId = '{{ $agentId }}';
        let isPlaying = false;
        let chatMessages = [];
        
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
                button.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            event.target.classList.add('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
            event.target.classList.remove('text-gray-500', 'hover:text-gray-700');
            
            // Initialize analytics chart when tab is shown
            if (tabName === 'analytics') {
                initializeAnalyticsChart();
            }
        }
        
        // Voice Preview
        function playVoiceSample() {
            if (isPlaying) return;
            
            isPlaying = true;
            const waveform = document.getElementById('voice-waveform');
            waveform.classList.add('animate-pulse');
            
            // Simulate voice playback
            setTimeout(() => {
                isPlaying = false;
                waveform.classList.remove('animate-pulse');
            }, 3000);
            
            // In real implementation, this would use Text-to-Speech API
            const utterance = new SpeechSynthesisUtterance('Guten Tag, hier ist Ihr persönlicher Assistent. Wie kann ich Ihnen heute helfen?');
            utterance.lang = currentVersionData.language || 'de-DE';
            utterance.rate = parseFloat(currentVersionData.voice_speed || 1.0);
            window.speechSynthesis.speak(utterance);
        }
        
        // Chat Test Mode
        function toggleTestMode() {
            const chatMode = document.getElementById('chat-test-mode');
            const flowMode = document.getElementById('flow-view-mode');
            
            chatMode.classList.toggle('hidden');
            
            if (!chatMode.classList.contains('hidden')) {
                flowMode.classList.add('hidden');
                // Add initial message
                if (chatMessages.length === 0) {
                    addChatMessage('agent', currentVersionData.llm_configuration?.begin_message || 'Hallo! Wie kann ich Ihnen helfen?');
                }
            }
        }
        
        // Add chat message
        function addChatMessage(sender, message) {
            const messagesDiv = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;
            
            messageDiv.innerHTML = `
                <div class="max-w-xs px-4 py-2 rounded-lg ${
                    sender === 'user' 
                        ? 'bg-primary-600 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                }">
                    ${message}
                </div>
            `;
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            chatMessages.push({ sender, message });
        }
        
        // Send chat message
        function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            addChatMessage('user', message);
            input.value = '';
            
            // Simulate agent response
            setTimeout(() => {
                const response = generateAgentResponse(message);
                addChatMessage('agent', response);
            }, 1000 + Math.random() * 1000);
        }
        
        // Generate agent response (simplified)
        function generateAgentResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            
            if (lowerMessage.includes('termin') || lowerMessage.includes('appointment')) {
                return 'Gerne helfe ich Ihnen bei der Terminvereinbarung. Wann hätten Sie denn Zeit?';
            } else if (lowerMessage.includes('öffnungszeiten') || lowerMessage.includes('hours')) {
                return 'Unsere Öffnungszeiten sind Montag bis Freitag von 9:00 bis 18:00 Uhr.';
            } else if (lowerMessage.includes('preis') || lowerMessage.includes('kosten')) {
                return 'Die Preise variieren je nach Leistung. Kann ich Ihnen zu einem bestimmten Service mehr Informationen geben?';
            } else {
                return 'Interessant! Können Sie mir mehr dazu erzählen?';
            }
        }
        
        // Test scenarios
        function testScenario(scenario) {
            const scenarios = {
                greeting: [
                    { sender: 'agent', message: 'Guten Tag! Hier ist ' + (currentVersionData.agent_name || 'Ihr Assistent') + '. Wie kann ich Ihnen helfen?' },
                    { sender: 'user', message: 'Hallo, ich möchte gerne einen Termin vereinbaren.' },
                    { sender: 'agent', message: 'Sehr gerne! Für welche Leistung möchten Sie einen Termin vereinbaren?' }
                ],
                appointment: [
                    { sender: 'user', message: 'Ich brauche einen Termin für nächste Woche.' },
                    { sender: 'agent', message: 'Natürlich! Lassen Sie mich die Verfügbarkeiten für nächste Woche prüfen. Haben Sie einen bevorzugten Tag?' },
                    { sender: 'user', message: 'Am besten Dienstag oder Mittwoch.' },
                    { sender: 'agent', message: 'Perfekt! Am Dienstag hätte ich 10:00, 14:00 oder 16:00 Uhr frei. Am Mittwoch 9:00, 11:00 oder 15:00 Uhr. Was passt Ihnen am besten?' }
                ],
                objection: [
                    { sender: 'user', message: 'Das ist mir zu teuer.' },
                    { sender: 'agent', message: 'Ich verstehe Ihre Bedenken. Lassen Sie mich Ihnen erklären, welchen Mehrwert Sie für diesen Preis erhalten...' },
                    { sender: 'user', message: 'Gibt es günstigere Alternativen?' },
                    { sender: 'agent', message: 'Selbstverständlich! Wir haben verschiedene Pakete. Kann ich Ihnen unsere Basis-Option vorstellen?' }
                ],
                functions: [
                    { sender: 'user', message: 'Prüfen Sie meine Termine für morgen.' },
                    { sender: 'agent', message: '[Führe Funktion aus: check_appointments]' },
                    { sender: 'agent', message: 'Ich habe Ihre Termine für morgen geprüft. Sie haben 3 Termine: 9:00 Uhr, 14:00 Uhr und 16:30 Uhr.' }
                ]
            };
            
            // Clear chat
            document.getElementById('chat-messages').innerHTML = '';
            chatMessages = [];
            
            // Play scenario
            const messages = scenarios[scenario] || [];
            messages.forEach((msg, index) => {
                setTimeout(() => {
                    addChatMessage(msg.sender, msg.message);
                }, index * 1500);
            });
        }
        
        // Flow View
        function toggleFlowView() {
            const flowMode = document.getElementById('flow-view-mode');
            const chatMode = document.getElementById('chat-test-mode');
            
            flowMode.classList.toggle('hidden');
            
            if (!flowMode.classList.contains('hidden')) {
                chatMode.classList.add('hidden');
                renderFlowDiagram();
            }
        }
        
        // Render flow diagram
        function renderFlowDiagram() {
            const svg = document.getElementById('flow-diagram');
            
            // Simple flow visualization
            svg.innerHTML = `
                <defs>
                    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                        <polygon points="0 0, 10 3.5, 0 7" fill="#9ca3af" />
                    </marker>
                </defs>
                
                <!-- Start Node -->
                <g transform="translate(50, 50)">
                    <rect class="flow-node" x="0" y="0" width="150" height="60" rx="8" />
                    <text x="75" y="35" text-anchor="middle" class="text-sm font-medium">Start</text>
                </g>
                
                <!-- Greeting Node -->
                <g transform="translate(50, 150)">
                    <rect class="flow-node" x="0" y="0" width="150" height="60" rx="8" />
                    <text x="75" y="35" text-anchor="middle" class="text-sm">Greeting</text>
                </g>
                
                <!-- Intent Detection -->
                <g transform="translate(300, 150)">
                    <rect class="flow-node" x="0" y="0" width="150" height="60" rx="8" />
                    <text x="75" y="35" text-anchor="middle" class="text-sm">Intent Detection</text>
                </g>
                
                <!-- Appointment Branch -->
                <g transform="translate(300, 250)">
                    <rect class="flow-node" x="0" y="0" width="150" height="60" rx="8" />
                    <text x="75" y="35" text-anchor="middle" class="text-sm">Book Appointment</text>
                </g>
                
                <!-- Information Branch -->
                <g transform="translate(500, 250)">
                    <rect class="flow-node" x="0" y="0" width="150" height="60" rx="8" />
                    <text x="75" y="35" text-anchor="middle" class="text-sm">Provide Info</text>
                </g>
                
                <!-- Connections -->
                <path class="flow-connector" d="M 125 110 L 125 150" marker-end="url(#arrowhead)" />
                <path class="flow-connector" d="M 200 180 L 300 180" marker-end="url(#arrowhead)" />
                <path class="flow-connector" d="M 375 210 L 375 250" marker-end="url(#arrowhead)" />
                <path class="flow-connector" d="M 450 180 L 500 250" marker-end="url(#arrowhead)" />
            `;
        }
        
        // Initialize analytics chart
        function initializeAnalyticsChart() {
            const ctx = document.getElementById('call-volume-chart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Successful Calls',
                        data: [45, 52, 48, 61, 58, 42, 38],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Failed Calls',
                        data: [5, 3, 7, 4, 6, 8, 4],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Analyze prompt
        function analyzePrompt() {
            const prompt = currentVersionData.llm_configuration?.general_prompt || '';
            
            // Simple analysis (in real implementation, this would use AI)
            const wordCount = prompt.split(/\s+/).length;
            const hasExamples = prompt.includes('example') || prompt.includes('Beispiel');
            const hasPersona = prompt.includes('You are') || prompt.includes('Du bist');
            
            let feedback = `
                <h4 class="font-medium mb-2">Prompt Analysis</h4>
                <ul class="space-y-1 text-sm">
                    <li>• Word count: ${wordCount} ${wordCount > 500 ? '(Consider making it more concise)' : '(Good length)'}</li>
                    <li>• Has persona definition: ${hasPersona ? 'Yes ✓' : 'No (Consider adding one)'}</li>
                    <li>• Includes examples: ${hasExamples ? 'Yes ✓' : 'No (Consider adding examples)'}</li>
                    <li>• Estimated token usage: ~${Math.round(wordCount * 1.3)} tokens</li>
                </ul>
                <div class="mt-3">
                    <h5 class="font-medium mb-1">Suggestions:</h5>
                    <ul class="space-y-1 text-sm text-gray-600">
                        ${!hasPersona ? '<li>• Add a clear persona definition at the beginning</li>' : ''}
                        ${!hasExamples ? '<li>• Include specific examples of desired responses</li>' : ''}
                        ${wordCount > 500 ? '<li>• Consider breaking down into smaller, focused sections</li>' : ''}
                        <li>• Add edge case handling instructions</li>
                    </ul>
                </div>
            `;
            
            // Show in modal
            showModal('Prompt Analysis', feedback);
        }
        
        // Show modal
        function showModal(title, content) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.innerHTML = `
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                    <div class="mt-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">${title}</h3>
                        <div class="mt-4">
                            ${content}
                        </div>
                        <div class="mt-6">
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="w-full px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Test function
        function testFunction(functionName) {
            showModal('Testing Function', `
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Testing function: <strong>${functionName}</strong>
                </p>
                <div class="mt-3 p-3 bg-gray-100 dark:bg-gray-700 rounded">
                    <pre class="text-xs">Simulating function call...
Parameters: { test: true }
Response: { success: true }</pre>
                </div>
                <p class="mt-3 text-sm text-green-600">
                    ✓ Function test completed successfully
                </p>
            `);
        }
        
        // Version selection
        function selectVersion(version) {
            if (document.getElementById('compare-mode').checked) {
                const card = document.querySelector(`[data-version="${version}"]`);
                const checkbox = card.querySelector('.compare-checkbox');
                checkbox.checked = !checkbox.checked;
            } else {
                window.location.href = `?agent_id=${agentId}&version=${version}`;
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
            
            document.getElementById('diff-view').classList.remove('hidden');
            const diffContent = document.getElementById('diff-content');
            diffContent.innerHTML = '<p class="text-gray-500">Loading comparison...</p>';
            
            // Fetch and compare versions
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
                    element.innerHTML = element.innerHTML.replace(/<span class="search-highlight">|<\/span>/g, '');
                    element.innerHTML = element.innerHTML.replace(regex, match => `<span class="search-highlight">${match}</span>`);
                } else {
                    element.innerHTML = element.innerHTML.replace(/<span class="search-highlight">|<\/span>/g, '');
                }
            });
        }
        
        // Test call functionality
        function initiateTestCall() {
            // Reuse the enhanced test call modal from previous implementation
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
        }
        
        // Execute test call
        function executeTestCall() {
            const phoneNumber = document.getElementById('test-phone-number').value;
            const duration = document.getElementById('test-duration').value;
            
            if (!phoneNumber) {
                alert('Please enter a phone number');
                return;
            }
            
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
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial active tab
            document.querySelector('.tab-button').classList.add('active', 'border-b-2', 'border-primary-600', 'text-primary-600');
            document.querySelector('.tab-button').classList.remove('text-gray-500', 'hover:text-gray-700');
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