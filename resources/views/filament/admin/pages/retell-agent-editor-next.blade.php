<x-filament-panels::page>
    <style>
        /* Advanced Gradient Animations */
        .agent-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        .agent-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        /* Live Voice Visualizer */
        .voice-visualizer {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 80px;
            gap: 4px;
            padding: 20px;
        }
        .voice-bar {
            width: 4px;
            background: rgba(255,255,255,0.8);
            border-radius: 2px;
            transition: height 0.1s ease;
        }
        .voice-bar.active {
            animation: voiceWave 0.5s ease-in-out infinite;
        }
        @keyframes voiceWave {
            0%, 100% { height: 20px; }
            50% { height: 40px; }
        }
        
        /* Chat Interface */
        .chat-container {
            height: 500px;
            display: flex;
            flex-direction: column;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .chat-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 12px;
            animation: slideIn 0.3s ease-out;
        }
        .chat-bubble.user {
            background: #3b82f6;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble.agent {
            background: white;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 8px 12px;
        }
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Flow Builder */
        .flow-canvas {
            background: #f9fafb;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            min-height: 600px;
            position: relative;
            overflow: auto;
        }
        .flow-node {
            position: absolute;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            min-width: 200px;
            cursor: move;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .flow-node:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        .flow-node.active {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .flow-connector {
            stroke: #9ca3af;
            stroke-width: 2;
            fill: none;
            marker-end: url(#arrowhead);
        }
        .flow-port {
            position: absolute;
            width: 12px;
            height: 12px;
            background: #3b82f6;
            border: 2px solid white;
            border-radius: 50%;
            cursor: crosshair;
        }
        .flow-port.input { left: -6px; top: 50%; transform: translateY(-50%); }
        .flow-port.output { right: -6px; top: 50%; transform: translateY(-50%); }
        
        /* Performance Metrics */
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Advanced Search */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 4px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 50;
        }
        .search-result-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f3f4f6;
        }
        .search-result-item:hover {
            background: #f3f4f6;
        }
        .search-match {
            background: #fef08a;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 2px;
            background: #f3f4f6;
            padding: 4px;
            border-radius: 12px;
        }
        .tab-button {
            flex: 1;
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .tab-button:hover {
            color: #374151;
        }
        .tab-button.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 3px;
            background: #3b82f6;
            border-radius: 2px;
        }
        
        /* Prompt Optimizer */
        .optimizer-suggestion {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            position: relative;
            padding-left: 48px;
        }
        .optimizer-icon {
            position: absolute;
            left: 16px;
            top: 16px;
            width: 24px;
            height: 24px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
            <!-- Advanced Header with Live Voice Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="agent-header p-8 text-white relative">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <a href="/admin/retell-ultimate-control-center" 
                                   class="inline-flex items-center gap-2 text-white/80 hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Back
                                </a>
                                <div>
                                    <h1 class="text-3xl font-bold">
                                        {{ $agent['agent_name'] ?? 'Agent Editor' }}
                                    </h1>
                                    <p class="text-sm text-white/80 mt-1">
                                        Agent ID: {{ $agentId }} • {{ count($versions) }} versions • Last updated {{ \Carbon\Carbon::parse($versions[0]['updated_at'] ?? now())->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Live Voice Preview -->
                            <div class="bg-white/20 backdrop-blur-lg rounded-xl p-6">
                                <div class="text-center mb-3">
                                    <p class="text-sm font-medium">Voice Preview</p>
                                </div>
                                <div class="voice-visualizer" id="voiceVisualizer">
                                    @for($i = 0; $i < 15; $i++)
                                        <div class="voice-bar" style="height: {{ rand(20, 40) }}px"></div>
                                    @endfor
                                </div>
                                <button onclick="toggleVoicePreview()" 
                                        id="voicePreviewBtn"
                                        class="w-full mt-3 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors font-medium">
                                    <span class="play-text">▶ Play Voice Sample</span>
                                    <span class="stop-text hidden">⏸ Stop Preview</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Bar -->
                <div class="bg-gray-50 dark:bg-gray-900 px-8 py-4">
                    <div class="grid grid-cols-4 gap-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $performanceMetrics['total_calls'] ?? '0' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Calls</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">{{ $performanceMetrics['success_rate'] ?? '0' }}%</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Success Rate</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $performanceMetrics['avg_duration'] ?? '0' }}s</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Duration</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ $performanceMetrics['satisfaction'] ?? '0' }}/5</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Satisfaction</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Tab Navigation -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-2">
                <div class="tab-nav">
                    <button class="tab-button active" onclick="switchTab('test')" data-tab="test">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            Test & Preview
                        </span>
                    </button>
                    <button class="tab-button" onclick="switchTab('flow')" data-tab="flow">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Flow Builder
                        </span>
                    </button>
                    <button class="tab-button" onclick="switchTab('config')" data-tab="config">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Configuration
                        </span>
                    </button>
                    <button class="tab-button" onclick="switchTab('analytics')" data-tab="analytics">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Analytics
                        </span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <!-- Test & Preview Tab -->
                <div id="test-tab" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Chat Simulator -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                                Chat Simulator
                                <button onclick="resetChat()" class="text-sm text-gray-500 hover:text-gray-700">
                                    Reset Chat
                                </button>
                            </h3>
                            
                            <!-- Quick Scenarios -->
                            <div class="mb-4 flex gap-2 flex-wrap">
                                <button onclick="sendQuickMessage('Ich möchte einen Termin buchen')" 
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    Termin buchen
                                </button>
                                <button onclick="sendQuickMessage('Was kostet eine Behandlung?')" 
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    Preisanfrage
                                </button>
                                <button onclick="sendQuickMessage('Wie sind Ihre Öffnungszeiten?')" 
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    Öffnungszeiten
                                </button>
                                <button onclick="sendQuickMessage('Ich möchte meinen Termin absagen')" 
                                        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    Termin absagen
                                </button>
                            </div>
                            
                            <div class="chat-container">
                                <div class="chat-messages" id="chatMessages">
                                    <div class="text-center text-gray-500 text-sm py-8">
                                        Start a conversation to test your agent
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex gap-2">
                                    <input type="text" 
                                           id="chatInput" 
                                           class="flex-1 px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                           placeholder="Type your message..."
                                           onkeypress="if(event.key === 'Enter') sendChatMessage()">
                                    <button onclick="sendChatMessage()" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Send
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Test Call Interface -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold mb-4">Live Test Call</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone Number</label>
                                    <input type="tel" 
                                           id="testPhoneNumber" 
                                           class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                           placeholder="+49 123 456789">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Test Duration</label>
                                    <select id="testDuration" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        <option value="60">1 minute</option>
                                        <option value="120">2 minutes</option>
                                        <option value="180">3 minutes</option>
                                        <option value="300">5 minutes</option>
                                    </select>
                                </div>
                                
                                <button onclick="initiateTestCall()" 
                                        class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                                    Start Test Call
                                </button>
                                
                                <!-- Call Status -->
                                <div id="callStatus" class="hidden">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium">Call Status</span>
                                            <span id="callStatusText" class="text-sm text-blue-600">Connecting...</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div id="callProgress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Test Scenarios -->
                                <div class="mt-6">
                                    <h4 class="font-medium mb-3">Test Scenarios</h4>
                                    <div class="space-y-2">
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                            <p class="font-medium text-sm">Basic Greeting</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Test the agent's introduction and greeting</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                            <p class="font-medium text-sm">Appointment Booking</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Request to book an appointment</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                            <p class="font-medium text-sm">Edge Cases</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Test unusual requests or interruptions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flow Builder Tab -->
                <div id="flow-tab" class="tab-content hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold">Conversation Flow</h3>
                            <div class="flex gap-2">
                                <button onclick="addFlowNode()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    + Add Node
                                </button>
                                <button onclick="autoLayout()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    Auto Layout
                                </button>
                            </div>
                        </div>
                        
                        <div class="flow-canvas" id="flowCanvas">
                            <svg width="100%" height="100%" style="position: absolute; top: 0; left: 0; pointer-events: none;">
                                <defs>
                                    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#9ca3af" />
                                    </marker>
                                </defs>
                                <g id="flowConnections"></g>
                            </svg>
                            
                            <!-- Start Node -->
                            <div class="flow-node" style="left: 50px; top: 50px;" data-node-id="start">
                                <div class="font-medium mb-2">Start</div>
                                <div class="text-sm text-gray-600">Agent greets caller</div>
                                <div class="flow-port output"></div>
                            </div>
                            
                            <!-- Intent Recognition -->
                            <div class="flow-node" style="left: 300px; top: 50px;" data-node-id="intent">
                                <div class="font-medium mb-2">Intent Recognition</div>
                                <div class="text-sm text-gray-600">Identify caller's need</div>
                                <div class="flow-port input"></div>
                                <div class="flow-port output"></div>
                            </div>
                            
                            <!-- Booking Flow -->
                            <div class="flow-node" style="left: 550px; top: 20px;" data-node-id="booking">
                                <div class="font-medium mb-2">Appointment Booking</div>
                                <div class="text-sm text-gray-600">Collect details & confirm</div>
                                <div class="flow-port input"></div>
                                <div class="flow-port output"></div>
                            </div>
                            
                            <!-- Information Flow -->
                            <div class="flow-node" style="left: 550px; top: 150px;" data-node-id="info">
                                <div class="font-medium mb-2">Information Request</div>
                                <div class="text-sm text-gray-600">Provide requested info</div>
                                <div class="flow-port input"></div>
                                <div class="flow-port output"></div>
                            </div>
                            
                            <!-- End Node -->
                            <div class="flow-node" style="left: 800px; top: 85px;" data-node-id="end">
                                <div class="font-medium mb-2">End Call</div>
                                <div class="text-sm text-gray-600">Thank caller & end</div>
                                <div class="flow-port input"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Tab -->
                <div id="config-tab" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Version Timeline -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold mb-4">Version History</h3>
                                <div class="version-timeline space-y-4">
                                    @foreach($versions as $index => $version)
                                        <div class="version-item cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded-lg"
                                             onclick="loadVersion('{{ $version['version_id'] }}', this)">
                                            <div class="version-dot {{ $version['is_published'] ? 'published' : '' }} {{ $index === 0 ? 'selected' : '' }}"></div>
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium">Version {{ $version['version_id'] }}</p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ \Carbon\Carbon::parse($version['updated_at'])->format('M d, Y H:i') }}
                                                    </p>
                                                </div>
                                                @if($version['is_published'])
                                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Published</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Configuration Editor -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold">Agent Configuration</h3>
                                    <div class="flex gap-2">
                                        <button onclick="optimizePrompt()" 
                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                                            🤖 Optimize Prompt
                                        </button>
                                        <button onclick="saveConfiguration()" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>

                                <!-- Prompt Optimizer Suggestions -->
                                <div id="optimizerSuggestions" class="hidden mb-6 space-y-3">
                                    <h4 class="font-medium">AI Optimization Suggestions</h4>
                                    <div class="optimizer-suggestion">
                                        <div class="optimizer-icon">💡</div>
                                        <div>
                                            <p class="font-medium">Add emotional intelligence</p>
                                            <p class="text-sm text-gray-600">Consider adding empathy statements for better customer experience</p>
                                        </div>
                                    </div>
                                    <div class="optimizer-suggestion">
                                        <div class="optimizer-icon">⚡</div>
                                        <div>
                                            <p class="font-medium">Optimize response time</p>
                                            <p class="text-sm text-gray-600">Simplify complex instructions to reduce processing time</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuration Form -->
                                <div class="space-y-6">
                                    <!-- Basic Info -->
                                    <div>
                                        <h4 class="font-medium mb-3">Basic Information</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Agent Name</label>
                                                <input type="text" id="agentName" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" 
                                                       value="{{ $currentVersion['agent_name'] ?? '' }}">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Begin Message</label>
                                                <textarea id="beginMessage" rows="3" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">{{ $currentVersion['begin_message'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Voice Settings -->
                                    <div>
                                        <h4 class="font-medium mb-3">Voice Settings</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Voice Provider</label>
                                                <select id="voiceProvider" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                                    <option value="openai">OpenAI</option>
                                                    <option value="elevenlabs">ElevenLabs</option>
                                                    <option value="playht">PlayHT</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Voice Speed</label>
                                                <input type="range" id="voiceSpeed" min="0.5" max="2" step="0.1" value="{{ $currentVersion['voice_speed'] ?? '1' }}"
                                                       class="w-full" oninput="document.getElementById('voiceSpeedValue').textContent = this.value">
                                                <div class="text-center text-sm text-gray-600">
                                                    <span id="voiceSpeedValue">{{ $currentVersion['voice_speed'] ?? '1' }}</span>x
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- LLM Prompt -->
                                    <div>
                                        <h4 class="font-medium mb-3">System Prompt</h4>
                                        <textarea id="systemPrompt" rows="8" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 font-mono text-sm">{{ $currentVersion['general_prompt'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics-tab" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Metric Cards -->
                        <div class="metric-card">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Call Volume</h4>
                            <div class="metric-value">1,234</div>
                            <p class="text-sm text-gray-500 mt-2">+12% from last week</p>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Conversion Rate</h4>
                            <div class="metric-value">78%</div>
                            <p class="text-sm text-gray-500 mt-2">+5% from last week</p>
                        </div>
                        
                        <div class="metric-card">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Avg Handle Time</h4>
                            <div class="metric-value">3:45</div>
                            <p class="text-sm text-gray-500 mt-2">-15s from last week</p>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold mb-4">Call Volume Trend</h3>
                            <canvas id="volumeChart" height="200"></canvas>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold mb-4">Intent Distribution</h3>
                            <canvas id="intentChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Call Logs -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mt-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Calls</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Time</th>
                                        <th class="text-left py-2">Phone</th>
                                        <th class="text-left py-2">Duration</th>
                                        <th class="text-left py-2">Intent</th>
                                        <th class="text-left py-2">Status</th>
                                        <th class="text-left py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCalls ?? [] as $call)
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="py-2">{{ \Carbon\Carbon::parse($call->created_at)->format('H:i') }}</td>
                                        <td class="py-2">{{ substr($call->from_phone_number, 0, -4) . '****' }}</td>
                                        <td class="py-2">{{ gmdate('i:s', $call->duration) }}</td>
                                        <td class="py-2">{{ $call->metadata['intent'] ?? 'Unknown' }}</td>
                                        <td class="py-2">
                                            <span class="px-2 py-1 text-xs rounded {{ $call->metadata['successful'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $call->metadata['successful'] ? 'Success' : 'Failed' }}
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <button onclick="playRecording('{{ $call->recording_url }}')" class="text-blue-600 hover:text-blue-800 text-sm">
                                                Play
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let currentVersion = @json($currentVersion ?? []);
        let isVoicePlaying = false;
        let voiceInterval;
        let currentCallId = null;
        let chatContext = [];

        // Tab Switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.remove('hidden');
            
            // Initialize charts when analytics tab is shown
            if (tabName === 'analytics') {
                initializeCharts();
            }
        }

        // Voice Preview
        function toggleVoicePreview() {
            const btn = document.getElementById('voicePreviewBtn');
            const playText = btn.querySelector('.play-text');
            const stopText = btn.querySelector('.stop-text');
            const bars = document.querySelectorAll('.voice-bar');
            
            if (!isVoicePlaying) {
                isVoicePlaying = true;
                playText.classList.add('hidden');
                stopText.classList.remove('hidden');
                
                // Animate voice bars
                bars.forEach((bar, index) => {
                    bar.classList.add('active');
                    bar.style.animationDelay = `${index * 0.05}s`;
                });
                
                // Use browser's speech synthesis for preview
                const utterance = new SpeechSynthesisUtterance(currentVersion.begin_message || 'Guten Tag, hier ist Ihr persönlicher Assistent. Wie kann ich Ihnen heute helfen?');
                utterance.lang = currentVersion.language || 'de-DE';
                utterance.rate = parseFloat(currentVersion.voice_speed || 1.0);
                
                utterance.onend = () => {
                    stopVoicePreview();
                };
                
                window.speechSynthesis.speak(utterance);
                
                // Real-time voice visualization
                voiceInterval = setInterval(() => {
                    bars.forEach(bar => {
                        const height = 20 + Math.random() * 30;
                        bar.style.height = `${height}px`;
                    });
                }, 30000);
                
            } else {
                stopVoicePreview();
            }
        }

        function stopVoicePreview() {
            isVoicePlaying = false;
            const btn = document.getElementById('voicePreviewBtn');
            const playText = btn.querySelector('.play-text');
            const stopText = btn.querySelector('.stop-text');
            const bars = document.querySelectorAll('.voice-bar');
            
            playText.classList.remove('hidden');
            stopText.classList.add('hidden');
            
            bars.forEach(bar => {
                bar.classList.remove('active');
            });
            
            window.speechSynthesis.cancel();
            clearInterval(voiceInterval);
        }

        // Chat Simulator
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addChatMessage('user', message);
            input.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Simulate agent response
            setTimeout(() => {
                hideTypingIndicator();
                const response = generateAgentResponse(message);
                addChatMessage('agent', response);
            }, 1000 + Math.random() * 1000);
        }

        function sendQuickMessage(message) {
            document.getElementById('chatInput').value = message;
            sendChatMessage();
        }

        function addChatMessage(sender, message) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-bubble ${sender}`;
            messageDiv.textContent = message;
            
            // Remove initial message if present
            const initialMsg = messagesDiv.querySelector('.text-center');
            if (initialMsg) initialMsg.remove();
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            // Add to context
            chatContext.push({ sender, message });
        }

        function showTypingIndicator() {
            const messagesDiv = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat-bubble agent typing-indicator';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
            messagesDiv.appendChild(typingDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        function generateAgentResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            
            // Context-aware responses
            if (lowerMessage.includes('termin') && lowerMessage.includes('buchen')) {
                return 'Gerne helfe ich Ihnen bei der Terminbuchung. Wann hätten Sie denn Zeit?';
            } else if (lowerMessage.includes('kosten') || lowerMessage.includes('preis')) {
                return 'Die Preise variieren je nach Behandlung. Eine Standard-Konsultation kostet 80€. Möchten Sie Details zu einer bestimmten Behandlung?';
            } else if (lowerMessage.includes('öffnungszeiten')) {
                return 'Wir haben Montag bis Freitag von 8:00 bis 18:00 Uhr geöffnet, Samstags von 9:00 bis 13:00 Uhr.';
            } else if (lowerMessage.includes('absagen')) {
                return 'Ich kann Ihnen gerne bei der Terminabsage helfen. Können Sie mir bitte Ihren Namen und das Datum des Termins nennen?';
            } else {
                return 'Ich verstehe. Wie kann ich Ihnen konkret weiterhelfen?';
            }
        }

        function resetChat() {
            document.getElementById('chatMessages').innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Start a conversation to test your agent</div>';
            chatContext = [];
        }

        // Test Call Functions
        function initiateTestCall() {
            const phoneNumber = document.getElementById('testPhoneNumber').value;
            const duration = document.getElementById('testDuration').value;
            
            if (!phoneNumber) {
                alert('Please enter a phone number');
                return;
            }
            
            // Show call status
            document.getElementById('callStatus').classList.remove('hidden');
            updateCallStatus('Connecting...', 10);
            
            // Make API call
            fetch('/api/mcp/retell/test-call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    agent_id: '{{ $agentId }}',
                    phone_number: phoneNumber,
                    test_duration: duration
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentCallId = data.call_id;
                    updateCallStatus('Call initiated', 30);
                    trackCallProgress();
                } else {
                    updateCallStatus('Failed: ' + (data.details || 'Unknown error'), 0);
                }
            })
            .catch(error => {
                updateCallStatus('Error: ' + error.message, 0);
            });
        }

        function updateCallStatus(status, progress) {
            document.getElementById('callStatusText').textContent = status;
            document.getElementById('callProgress').style.width = progress + '%';
        }

        function trackCallProgress() {
            if (!currentCallId) return;
            
            const checkStatus = setInterval(() => {
                fetch(`/api/mcp/retell/test-call/${currentCallId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'ended') {
                            updateCallStatus('Call completed', 30000);
                            clearInterval(checkStatus);
                        } else if (data.status === 'ongoing') {
                            const progress = Math.min(90, 30 + (data.duration / 3) * 60);
                            updateCallStatus('Call in progress...', progress);
                        }
                    });
            }, 2000);
        }

        // Flow Builder Functions
        let selectedNode = null;
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };

        function initializeFlowBuilder() {
            const flowCanvas = document.getElementById('flowCanvas');
            if (!flowCanvas) {
                console.log('Flow canvas not found, skipping flow builder initialization');
                return;
            }
            
            const nodes = flowCanvas.querySelectorAll('.flow-node');
            
            nodes.forEach(node => {
                node.addEventListener('mousedown', startDrag);
            });
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
            
            // Draw initial connections
            drawConnections();
        }

        function startDrag(e) {
            if (e.target.classList.contains('flow-port')) return;
            
            selectedNode = e.currentTarget;
            isDragging = true;
            
            const rect = selectedNode.getBoundingClientRect();
            const canvasRect = document.getElementById('flowCanvas').getBoundingClientRect();
            
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            selectedNode.classList.add('active');
        }

        function drag(e) {
            if (!isDragging || !selectedNode) return;
            
            const flowCanvas = document.getElementById('flowCanvas');
            if (!flowCanvas) return;
            
            const canvasRect = flowCanvas.getBoundingClientRect();
            const x = e.clientX - canvasRect.left - dragOffset.x;
            const y = e.clientY - canvasRect.top - dragOffset.y;
            
            selectedNode.style.left = Math.max(0, x) + 'px';
            selectedNode.style.top = Math.max(0, y) + 'px';
            
            drawConnections();
        }

        function stopDrag() {
            if (selectedNode) {
                selectedNode.classList.remove('active');
            }
            isDragging = false;
            selectedNode = null;
        }

        function drawConnections() {
            const connections = [
                { from: 'start', to: 'intent' },
                { from: 'intent', to: 'booking' },
                { from: 'intent', to: 'info' },
                { from: 'booking', to: 'end' },
                { from: 'info', to: 'end' }
            ];
            
            const svg = document.getElementById('flowConnections');
            const flowCanvas = document.getElementById('flowCanvas');
            
            if (!svg || !flowCanvas) {
                console.log('Flow connections or canvas not found');
                return;
            }
            
            svg.innerHTML = '';
            
            connections.forEach(conn => {
                const fromNode = document.querySelector(`[data-node-id="${conn.from}"]`);
                const toNode = document.querySelector(`[data-node-id="${conn.to}"]`);
                
                if (!fromNode || !toNode) return;
                
                const fromRect = fromNode.getBoundingClientRect();
                const toRect = toNode.getBoundingClientRect();
                const canvasRect = flowCanvas.getBoundingClientRect();
                
                const x1 = fromRect.right - canvasRect.left;
                const y1 = fromRect.top + fromRect.height / 2 - canvasRect.top;
                const x2 = toRect.left - canvasRect.left;
                const y2 = toRect.top + toRect.height / 2 - canvasRect.top;
                
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                const midX = (x1 + x2) / 2;
                path.setAttribute('d', `M ${x1} ${y1} C ${midX} ${y1}, ${midX} ${y2}, ${x2} ${y2}`);
                path.setAttribute('class', 'flow-connector');
                
                svg.appendChild(path);
            });
        }

        function addFlowNode() {
            const flowCanvas = document.getElementById('flowCanvas');
            const nodeId = 'node-' + Date.now();
            
            const nodeDiv = document.createElement('div');
            nodeDiv.className = 'flow-node';
            nodeDiv.style.left = '400px';
            nodeDiv.style.top = '300px';
            nodeDiv.setAttribute('data-node-id', nodeId);
            nodeDiv.innerHTML = `
                <div class="font-medium mb-2">New Node</div>
                <div class="text-sm text-gray-600">Configure this node</div>
                <div class="flow-port input"></div>
                <div class="flow-port output"></div>
            `;
            
            flowCanvas.appendChild(nodeDiv);
            nodeDiv.addEventListener('mousedown', startDrag);
        }

        function autoLayout() {
            // Simple auto-layout algorithm
            const nodes = document.querySelectorAll('.flow-node');
            const spacing = { x: 250, y: 130 };
            let x = 50, y = 50;
            
            nodes.forEach((node, index) => {
                node.style.left = x + 'px';
                node.style.top = y + 'px';
                
                x += spacing.x;
                if ((index + 1) % 3 === 0) {
                    x = 50;
                    y += spacing.y;
                }
            });
            
            drawConnections();
        }

        // Prompt Optimizer
        function optimizePrompt() {
            const suggestionsDiv = document.getElementById('optimizerSuggestions');
            suggestionsDiv.classList.remove('hidden');
            
            // Simulate AI analysis
            setTimeout(() => {
                // Add animation to suggestions
                suggestionsDiv.querySelectorAll('.optimizer-suggestion').forEach((suggestion, index) => {
                    suggestion.style.opacity = '0';
                    suggestion.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        suggestion.style.transition = 'all 0.3s ease';
                        suggestion.style.opacity = '1';
                        suggestion.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 100);
        }

        // Charts
        function initializeCharts() {
            // Volume Chart
            const volumeCtx = document.getElementById('volumeChart');
            if (volumeCtx && !volumeCtx.chart) {
                volumeCtx.chart = new Chart(volumeCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Call Volume',
                            data: [65, 78, 82, 91, 88, 45, 32],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
            
            // Intent Chart
            const intentCtx = document.getElementById('intentChart');
            if (intentCtx && !intentCtx.chart) {
                intentCtx.chart = new Chart(intentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Booking', 'Information', 'Cancellation', 'Other'],
                        datasets: [{
                            data: [45, 30, 15, 10],
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            try {
                initializeFlowBuilder();
            } catch (e) {
                console.error('Error initializing flow builder:', e);
            }
        });

        // Save Configuration
        function saveConfiguration() {
            // Collect all configuration data
            const configData = {
                agent_name: document.getElementById('agentName').value,
                begin_message: document.getElementById('beginMessage').value,
                voice_provider: document.getElementById('voiceProvider').value,
                voice_speed: document.getElementById('voiceSpeed').value,
                general_prompt: document.getElementById('systemPrompt').value
            };
            
            // Show saving state
            const saveBtn = event.target;
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                saveBtn.textContent = '✓ Saved';
                setTimeout(() => {
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                }, 2000);
            }, 1000);
        }

        // Load Version
        function loadVersion(versionId, element) {
            // Update UI to show loading state
            document.querySelectorAll('.version-dot').forEach(dot => {
                dot.classList.remove('selected');
            });
            
            // Find the clicked element's version dot
            const clickedElement = element || event.currentTarget;
            if (clickedElement) {
                const versionDot = clickedElement.querySelector('.version-dot');
                if (versionDot) {
                    versionDot.classList.add('selected');
                }
            }
            
            // Here you would load the version data via API
            console.log('Loading version:', versionId);
        }
    </script>
</x-filament-panels::page>