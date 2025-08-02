<x-filament-panels::page>
    <style>
        /* Unified Editor Styles */
        .editor-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 100%;
        }
        
        .editor-main {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .editor-left {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }
        
        .editor-center {
            flex: 2;
            min-width: 500px;
        }
        
        .editor-right {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }
        
        @media (max-width: 1400px) {
            .editor-main {
                flex-direction: column;
            }
            .editor-left, .editor-center, .editor-right {
                max-width: 100%;
                width: 100%;
            }
        }
        
        /* Flow Builder Canvas */
        .flow-canvas {
            background: #f9fafb;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            position: relative;
            overflow: auto;
            min-height: 600px;
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .flow-node:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .flow-node.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .flow-node.editing {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
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
        
        /* Config Editor */
        .config-editor {
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }
        
        .config-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .config-section:last-child {
            border-bottom: none;
        }
        
        /* Live Preview */
        .live-preview {
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 20px;
            border-radius: 12px;
            height: 300px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .live-preview .user {
            color: #3b82f6;
        }
        
        .live-preview .agent {
            color: #10b981;
        }
        
        /* Save Indicator */
        .save-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            z-index: 50;
        }
        
        .save-indicator.saving {
            background: #3b82f6;
            color: white;
        }
        
        .save-indicator.saved {
            background: #10b981;
            color: white;
        }
        
        .save-indicator.error {
            background: #ef4444;
            color: white;
        }
        
        /* Inline Editing */
        .editable {
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .editable:hover {
            background: #f3f4f6;
            cursor: text;
        }
        
        .editable:focus {
            background: white;
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Node Types */
        .flow-node.greeting { border-color: #10b981; }
        .flow-node.question { border-color: #3b82f6; }
        .flow-node.action { border-color: #f59e0b; }
        .flow-node.end { border-color: #ef4444; }
    </style>

    @if(!$agentId)
        <div class="text-center p-8">
            <p class="text-gray-500">No agent ID provided</p>
            <a href="/admin/retell-ultimate-control-center" class="text-primary-600 hover:text-primary-500">
                Back to Control Center
            </a>
        </div>
    @else
        <!-- Save Indicator -->
        <div id="save-indicator" class="save-indicator" style="display: none;">
            <div class="spinner" id="save-spinner" style="display: none;">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <span id="save-text">All changes saved</span>
        </div>

        <!-- Header with Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
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
                        <h2 class="text-2xl font-bold editable" 
                            contenteditable="true" 
                            data-field="agent_name"
                            onblur="updateField('agent_name', this.textContent)">
                            {{ $agent['agent_name'] ?? 'Agent Editor' }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            Agent ID: {{ $agentId }} • Auto-save enabled
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <button onclick="testAgent()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Test Call
                    </button>
                    <button onclick="publishVersion()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Publish
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Editor Container -->
        <div class="editor-container">
            <div class="editor-main">
                <!-- Left Panel: Configuration -->
                <div class="editor-left">
                    <div class="config-editor bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Configuration</h3>
                
                <!-- Voice Settings -->
                <div class="config-section">
                    <h4 class="font-medium mb-3">Voice Settings</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Voice Provider</label>
                            <select class="w-full px-3 py-2 border rounded-lg" 
                                    onchange="updateField('voice_provider', this.value)">
                                <option value="openai" {{ ($currentVersion['voice_provider'] ?? '') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                                <option value="elevenlabs" {{ ($currentVersion['voice_provider'] ?? '') == 'elevenlabs' ? 'selected' : '' }}>ElevenLabs</option>
                                <option value="playht" {{ ($currentVersion['voice_provider'] ?? '') == 'playht' ? 'selected' : '' }}>PlayHT</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Voice Speed</label>
                            <input type="range" 
                                   min="0.5" 
                                   max="2" 
                                   step="0.1" 
                                   value="{{ $currentVersion['voice_speed'] ?? '1' }}"
                                   class="w-full"
                                   oninput="updateField('voice_speed', this.value); document.getElementById('speed-value').textContent = this.value">
                            <div class="text-center text-sm text-gray-600">
                                <span id="speed-value">{{ $currentVersion['voice_speed'] ?? '1' }}</span>x
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Language</label>
                            <select class="w-full px-3 py-2 border rounded-lg" 
                                    onchange="updateField('language', this.value)">
                                <option value="de-DE" {{ ($currentVersion['language'] ?? '') == 'de-DE' ? 'selected' : '' }}>Deutsch</option>
                                <option value="en-US" {{ ($currentVersion['language'] ?? '') == 'en-US' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Greeting -->
                <div class="config-section">
                    <h4 class="font-medium mb-3">Greeting Message</h4>
                    <textarea class="w-full px-3 py-2 border rounded-lg" 
                              rows="3"
                              onblur="updateField('begin_message', this.value)"
                              placeholder="How the agent greets callers...">{{ $currentVersion['begin_message'] ?? '' }}</textarea>
                </div>
                
                <!-- System Prompt -->
                <div class="config-section">
                    <h4 class="font-medium mb-3">System Prompt</h4>
                    <textarea class="w-full px-3 py-2 border rounded-lg font-mono text-sm" 
                              rows="8"
                              onblur="updateField('general_prompt', this.value)"
                              placeholder="Agent behavior instructions...">{{ $currentVersion['general_prompt'] ?? '' }}</textarea>
                </div>
                
                <!-- Functions -->
                <div class="config-section">
                    <h4 class="font-medium mb-3">Custom Functions</h4>
                    <div id="functions-list" class="space-y-2">
                        @if(isset($currentVersion['custom_functions']) && is_array($currentVersion['custom_functions']))
                            @foreach($currentVersion['custom_functions'] as $index => $function)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="font-medium">{{ $function['name'] ?? 'Function' }}</div>
                                    <div class="text-sm text-gray-600">{{ $function['description'] ?? '' }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button onclick="addFunction()" 
                            class="mt-3 text-sm text-blue-600 hover:text-blue-800">
                        + Add Function
                    </button>
                    </div>
                </div>

                <!-- Center: Flow Builder -->
                <div class="editor-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Conversation Flow</h3>
                        <div class="flex gap-2">
                            <button onclick="addFlowNode('greeting')" 
                                    class="px-3 py-1 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 text-sm">
                                + Greeting
                            </button>
                            <button onclick="addFlowNode('question')" 
                                    class="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 text-sm">
                                + Question
                            </button>
                            <button onclick="addFlowNode('action')" 
                                    class="px-3 py-1 bg-orange-100 text-orange-800 rounded-lg hover:bg-orange-200 text-sm">
                                + Action
                            </button>
                            <button onclick="autoLayout()" 
                                    class="px-3 py-1 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 text-sm">
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
                        
                        <!-- Initial Flow Nodes -->
                        <div class="flow-node greeting" style="left: 50px; top: 50px;" data-node-id="start">
                            <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">Greeting</div>
                            <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">{{ $currentVersion['begin_message'] ?? 'Agent greets caller' }}</div>
                            <div class="flow-port output"></div>
                        </div>
                        
                        <div class="flow-node question" style="left: 300px; top: 50px;" data-node-id="intent">
                            <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">Intent Recognition</div>
                            <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">Identify what the caller needs</div>
                            <div class="flow-port input"></div>
                            <div class="flow-port output"></div>
                        </div>
                        
                        <div class="flow-node action" style="left: 550px; top: 50px;" data-node-id="booking">
                            <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">Book Appointment</div>
                            <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">Collect details and confirm</div>
                            <div class="flow-port input"></div>
                            <div class="flow-port output"></div>
                        </div>
                        
                        <div class="flow-node end" style="left: 800px; top: 50px;" data-node-id="end">
                            <div class="font-medium mb-2">End Call</div>
                            <div class="text-sm text-gray-600">Thank caller and end</div>
                            <div class="flow-port input"></div>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Right Panel: Live Preview & Test -->
                <div class="editor-right">
                    <div class="space-y-4">
                <!-- Live Preview -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <h3 class="text-lg font-semibold mb-4">Live Preview</h3>
                    
                    <div class="live-preview" id="livePreview">
                        <div class="agent">Agent: {{ $currentVersion['begin_message'] ?? 'Hello, how can I help you today?' }}</div>
                        <div class="user">User: I'd like to book an appointment</div>
                        <div class="agent">Agent: Of course! I'd be happy to help you book an appointment...</div>
                    </div>
                    
                    <div class="mt-4">
                        <input type="text" 
                               id="testInput" 
                               class="w-full px-3 py-2 border rounded-lg"
                               placeholder="Type a test message..."
                               onkeypress="if(event.key === 'Enter') sendTestMessage()">
                        <button onclick="sendTestMessage()" 
                                class="mt-2 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Send Test Message
                        </button>
                    </div>
                </div>
                
                <!-- Quick Test Scenarios -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <h3 class="text-lg font-semibold mb-4">Test Scenarios</h3>
                    
                    <div class="space-y-2">
                        <button onclick="testScenario('greeting')" 
                                class="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-medium text-sm">Test Greeting</div>
                            <div class="text-xs text-gray-600">How the agent introduces itself</div>
                        </button>
                        
                        <button onclick="testScenario('booking')" 
                                class="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-medium text-sm">Test Booking Flow</div>
                            <div class="text-xs text-gray-600">Complete appointment booking</div>
                        </button>
                        
                        <button onclick="testScenario('error')" 
                                class="w-full text-left px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-medium text-sm">Test Error Handling</div>
                            <div class="text-xs text-gray-600">How agent handles confusion</div>
                        </button>
                    </div>
                </div>
                
                <!-- Version Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <h3 class="text-lg font-semibold mb-2">Version Info</h3>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Version:</span>
                            <span class="font-medium">v{{ $selectedVersion ?? 'latest' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="font-medium">{{ \Carbon\Carbon::parse($currentVersion['updated_at'] ?? now())->diffForHumans() }}</span>
                        </div>
                        @if($publishedVersion)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Published Version:</span>
                                <span class="font-medium text-green-600">v{{ $publishedVersion }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            </div>
        </div>
    @endif

    <script>
        let currentAgentData = @json($currentVersion ?? []);
        let isDragging = false;
        let selectedNode = null;
        let dragOffset = { x: 0, y: 0 };
        let saveTimeout = null;
        let pendingUpdates = {};
        let flowConnections = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeFlowBuilder();
            initializeEditables();
            setupAutoSave();
        });
        
        // Initialize editable fields
        function initializeEditables() {
            document.querySelectorAll('.editable').forEach(element => {
                element.addEventListener('focus', function() {
                    this.dataset.originalValue = this.textContent;
                });
            });
        }
        
        // Auto-save setup
        function setupAutoSave() {
            // Save every 2 seconds if there are pending changes
            setInterval(() => {
                if (Object.keys(pendingUpdates).length > 0) {
                    saveChanges();
                }
            }, 30000);
        }
        
        // Update field and mark for saving
        function updateField(field, value) {
            pendingUpdates[field] = value;
            showSaveIndicator('pending');
            
            // Update local data
            if (field.includes('.')) {
                // Handle nested fields
                const parts = field.split('.');
                let obj = currentAgentData;
                for (let i = 0; i < parts.length - 1; i++) {
                    if (!obj[parts[i]]) obj[parts[i]] = {};
                    obj = obj[parts[i]];
                }
                obj[parts[parts.length - 1]] = value;
            } else {
                currentAgentData[field] = value;
            }
            
            // Update preview if needed
            if (field === 'begin_message') {
                updateLivePreview();
            }
        }
        
        // Save changes to Retell
        async function saveChanges() {
            showSaveIndicator('saving');
            
            try {
                const response = await fetch('/api/mcp/retell/update-agent/{{ $agentId }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(pendingUpdates)
                });
                
                if (response.ok) {
                    pendingUpdates = {};
                    showSaveIndicator('saved');
                } else {
                    throw new Error('Save failed');
                }
            } catch (error) {
                console.error('Save error:', error);
                showSaveIndicator('error');
            }
        }
        
        // Save indicator
        function showSaveIndicator(status) {
            const indicator = document.getElementById('save-indicator');
            const spinner = document.getElementById('save-spinner');
            const text = document.getElementById('save-text');
            
            indicator.style.display = 'flex';
            indicator.className = 'save-indicator ' + status;
            
            switch(status) {
                case 'pending':
                    spinner.style.display = 'none';
                    text.textContent = 'Changes pending...';
                    break;
                case 'saving':
                    spinner.style.display = 'block';
                    text.textContent = 'Saving...';
                    break;
                case 'saved':
                    spinner.style.display = 'none';
                    text.textContent = 'All changes saved';
                    setTimeout(() => {
                        if (Object.keys(pendingUpdates).length === 0) {
                            indicator.style.display = 'none';
                        }
                    }, 3000);
                    break;
                case 'error':
                    spinner.style.display = 'none';
                    text.textContent = 'Error saving changes';
                    break;
            }
        }
        
        // Flow Builder Functions
        function initializeFlowBuilder() {
            const flowCanvas = document.getElementById('flowCanvas');
            if (!flowCanvas) return;
            
            const nodes = flowCanvas.querySelectorAll('.flow-node');
            nodes.forEach(node => {
                node.addEventListener('mousedown', startDrag);
                node.addEventListener('dblclick', editNode);
            });
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
            
            // Initialize connections
            flowConnections = [
                { from: 'start', to: 'intent' },
                { from: 'intent', to: 'booking' },
                { from: 'booking', to: 'end' }
            ];
            
            drawConnections();
        }
        
        function startDrag(e) {
            if (e.target.classList.contains('flow-port') || e.target.contentEditable === 'true') return;
            
            selectedNode = e.currentTarget;
            isDragging = true;
            
            const rect = selectedNode.getBoundingClientRect();
            const canvasRect = document.getElementById('flowCanvas').getBoundingClientRect();
            
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            selectedNode.classList.add('selected');
        }
        
        function drag(e) {
            if (!isDragging || !selectedNode) return;
            
            const flowCanvas = document.getElementById('flowCanvas');
            const canvasRect = flowCanvas.getBoundingClientRect();
            
            const x = e.clientX - canvasRect.left - dragOffset.x;
            const y = e.clientY - canvasRect.top - dragOffset.y;
            
            selectedNode.style.left = Math.max(0, x) + 'px';
            selectedNode.style.top = Math.max(0, y) + 'px';
            
            drawConnections();
        }
        
        function stopDrag() {
            if (selectedNode) {
                selectedNode.classList.remove('selected');
                // Save node position
                updateFlowStructure();
            }
            isDragging = false;
            selectedNode = null;
        }
        
        function drawConnections() {
            const svg = document.getElementById('flowConnections');
            const flowCanvas = document.getElementById('flowCanvas');
            
            if (!svg || !flowCanvas) return;
            
            svg.innerHTML = '';
            
            flowConnections.forEach(conn => {
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
        
        function addFlowNode(type) {
            const flowCanvas = document.getElementById('flowCanvas');
            const nodeId = type + '-' + Date.now();
            
            const nodeDiv = document.createElement('div');
            nodeDiv.className = 'flow-node ' + type;
            nodeDiv.style.left = '400px';
            nodeDiv.style.top = '200px';
            nodeDiv.setAttribute('data-node-id', nodeId);
            
            let nodeContent = '';
            switch(type) {
                case 'greeting':
                    nodeContent = `
                        <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">New Greeting</div>
                        <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">Enter greeting text</div>
                    `;
                    break;
                case 'question':
                    nodeContent = `
                        <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">New Question</div>
                        <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">What to ask the caller</div>
                    `;
                    break;
                case 'action':
                    nodeContent = `
                        <div class="font-medium mb-2 editable" contenteditable="true" onblur="updateNode(this)">New Action</div>
                        <div class="text-sm text-gray-600 editable" contenteditable="true" onblur="updateNode(this)">Action to perform</div>
                    `;
                    break;
            }
            
            nodeDiv.innerHTML = nodeContent + `
                <div class="flow-port input"></div>
                <div class="flow-port output"></div>
            `;
            
            flowCanvas.appendChild(nodeDiv);
            
            // Make it draggable
            nodeDiv.addEventListener('mousedown', startDrag);
            nodeDiv.addEventListener('dblclick', editNode);
            
            // Save flow structure
            updateFlowStructure();
        }
        
        function editNode(e) {
            const node = e.currentTarget;
            node.classList.add('editing');
            
            // Find editable elements
            const editables = node.querySelectorAll('.editable');
            if (editables.length > 0) {
                editables[0].focus();
            }
        }
        
        function updateNode(element) {
            const node = element.closest('.flow-node');
            if (node) {
                node.classList.remove('editing');
                updateFlowStructure();
            }
        }
        
        function updateFlowStructure() {
            // Collect all nodes and their positions
            const nodes = [];
            document.querySelectorAll('.flow-node').forEach(node => {
                nodes.push({
                    id: node.dataset.nodeId,
                    type: node.className.replace('flow-node', '').trim(),
                    position: {
                        x: parseInt(node.style.left),
                        y: parseInt(node.style.top)
                    },
                    title: node.querySelector('.font-medium')?.textContent || '',
                    description: node.querySelector('.text-sm')?.textContent || ''
                });
            });
            
            // Save as custom metadata
            updateField('flow_structure', {
                nodes: nodes,
                connections: flowConnections
            });
        }
        
        function autoLayout() {
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
            updateFlowStructure();
        }
        
        // Live Preview
        function updateLivePreview() {
            const preview = document.getElementById('livePreview');
            const greeting = currentAgentData.begin_message || 'Hello, how can I help you today?';
            
            preview.innerHTML = `<div class="agent">Agent: ${greeting}</div>`;
        }
        
        function sendTestMessage() {
            const input = document.getElementById('testInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const preview = document.getElementById('livePreview');
            preview.innerHTML += `<div class="user">User: ${message}</div>`;
            
            // Simulate agent response
            setTimeout(() => {
                const response = generateMockResponse(message);
                preview.innerHTML += `<div class="agent">Agent: ${response}</div>`;
                preview.scrollTop = preview.scrollHeight;
            }, 1000);
            
            input.value = '';
        }
        
        function generateMockResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            
            if (lowerMessage.includes('appointment') || lowerMessage.includes('termin')) {
                return 'I\'d be happy to help you book an appointment. When would you like to come in?';
            } else if (lowerMessage.includes('cancel') || lowerMessage.includes('absagen')) {
                return 'I can help you cancel your appointment. Can you please provide your appointment details?';
            } else {
                return 'I understand. How can I assist you with that?';
            }
        }
        
        function testScenario(scenario) {
            const preview = document.getElementById('livePreview');
            
            switch(scenario) {
                case 'greeting':
                    preview.innerHTML = `<div class="agent">Agent: ${currentAgentData.begin_message || 'Hello!'}</div>`;
                    break;
                case 'booking':
                    preview.innerHTML = `
                        <div class="agent">Agent: ${currentAgentData.begin_message || 'Hello!'}</div>
                        <div class="user">User: I'd like to book an appointment</div>
                        <div class="agent">Agent: Of course! I'd be happy to help you book an appointment. What service are you interested in?</div>
                        <div class="user">User: A regular consultation</div>
                        <div class="agent">Agent: Perfect! When would you prefer to come in? We have availability this week.</div>
                    `;
                    break;
                case 'error':
                    preview.innerHTML = `
                        <div class="agent">Agent: ${currentAgentData.begin_message || 'Hello!'}</div>
                        <div class="user">User: ajshdkajshd kajshd</div>
                        <div class="agent">Agent: I'm sorry, I didn't quite understand that. Could you please tell me how I can help you today?</div>
                    `;
                    break;
            }
            
            preview.scrollTop = preview.scrollHeight;
        }
        
        // Test Call
        async function testAgent() {
            try {
                const response = await fetch('/api/mcp/retell/test-call', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        agent_id: '{{ $agentId }}',
                        phone_number: prompt('Enter your phone number (with country code):'),
                        test_duration: 60
                    })
                });
                
                if (response.ok) {
                    alert('Test call initiated! You should receive a call shortly.');
                } else {
                    alert('Failed to initiate test call');
                }
            } catch (error) {
                console.error('Test call error:', error);
                alert('Error initiating test call');
            }
        }
        
        // Publish Version
        async function publishVersion() {
            if (!confirm('Publish this version as the live agent?')) return;
            
            try {
                const response = await fetch('/api/mcp/retell/publish-agent/{{ $agentId }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        version: '{{ $selectedVersion }}'
                    })
                });
                
                if (response.ok) {
                    alert('Version published successfully!');
                    location.reload();
                } else {
                    alert('Failed to publish version');
                }
            } catch (error) {
                console.error('Publish error:', error);
                alert('Error publishing version');
            }
        }
        
        // Add Function
        function addFunction() {
            const functionsList = document.getElementById('functions-list');
            const newFunction = document.createElement('div');
            newFunction.className = 'p-3 bg-gray-50 rounded-lg';
            newFunction.innerHTML = `
                <input type="text" class="font-medium w-full mb-1" placeholder="Function name" onblur="updateFunctions()">
                <input type="text" class="text-sm text-gray-600 w-full" placeholder="Description" onblur="updateFunctions()">
                <button onclick="this.parentElement.remove(); updateFunctions();" class="text-red-600 text-sm mt-1">Remove</button>
            `;
            functionsList.appendChild(newFunction);
        }
        
        function updateFunctions() {
            const functions = [];
            document.querySelectorAll('#functions-list > div').forEach(div => {
                const name = div.querySelector('input:first-child').value;
                const description = div.querySelector('input:nth-child(2)').value;
                if (name) {
                    functions.push({ name, description });
                }
            });
            updateField('custom_functions', functions);
        }
    </script>
</x-filament-panels::page>