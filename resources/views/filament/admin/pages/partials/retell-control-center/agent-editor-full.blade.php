{{-- Full Agent Editor Modal --}}
<div 
    x-data="{
        activeTab: @entangle('editorActiveTab').live,
        voiceSearchTerm: '',
        selectedVoice: @entangle('editingAgentFull.voice_id').live,
        isLoading: @entangle('isLoading').live,
        editingAgent: @entangle('editingAgentFull').live,
        editingLLM: @entangle('editingLLM').live,
        editingFunctions: @entangle('editingFunctions').live,
        editingPostCallAnalysis: @entangle('editingPostCallAnalysis').live,
        
        voices: [
            // OpenAI Voices
            { id: 'openai-Alloy', name: 'Alloy', provider: 'OpenAI', language: 'en-US', gender: 'neutral', style: 'neutral' },
            { id: 'openai-Echo', name: 'Echo', provider: 'OpenAI', language: 'en-US', gender: 'male', style: 'neutral' },
            { id: 'openai-Fable', name: 'Fable', provider: 'OpenAI', language: 'en-US', gender: 'neutral', style: 'neutral' },
            { id: 'openai-Onyx', name: 'Onyx', provider: 'OpenAI', language: 'en-US', gender: 'male', style: 'neutral' },
            { id: 'openai-Nova', name: 'Nova', provider: 'OpenAI', language: 'en-US', gender: 'female', style: 'neutral' },
            { id: 'openai-Shimmer', name: 'Shimmer', provider: 'OpenAI', language: 'en-US', gender: 'female', style: 'neutral' },
            
            // ElevenLabs German Voices
            { id: 'elevenlabs-Matilda', name: 'Matilda', provider: 'ElevenLabs', language: 'de-DE', gender: 'female', style: 'professional' },
            { id: 'elevenlabs-Wilhelm', name: 'Wilhelm', provider: 'ElevenLabs', language: 'de-DE', gender: 'male', style: 'professional' },
            
            // Add more voices as needed
        ],
        
        ambientSounds: [
            { id: null, name: 'None' },
            { id: 'office', name: 'Office' },
            { id: 'cafe', name: 'Cafe' },
            { id: 'restaurant', name: 'Restaurant' },
            { id: 'traffic', name: 'Traffic' },
            { id: 'rain', name: 'Rain' },
            { id: 'birds', name: 'Birds' }
        ],
        
        languages: [
            { code: 'de-DE', name: 'German (Germany)' },
            { code: 'en-US', name: 'English (US)' },
            { code: 'en-GB', name: 'English (UK)' },
            { code: 'es-ES', name: 'Spanish (Spain)' },
            { code: 'fr-FR', name: 'French (France)' },
            { code: 'it-IT', name: 'Italian (Italy)' },
            { code: 'pt-BR', name: 'Portuguese (Brazil)' },
            { code: 'nl-NL', name: 'Dutch (Netherlands)' }
        ],
        
        models: [
            { id: 'gpt-4-turbo', name: 'GPT-4 Turbo (Recommended)' },
            { id: 'gpt-4', name: 'GPT-4' },
            { id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo (Faster)' },
            { id: 'claude-3-opus', name: 'Claude 3 Opus' },
            { id: 'claude-3-sonnet', name: 'Claude 3 Sonnet' }
        ],
        
        tabs: [
            { id: 'basic', name: 'Basic Settings', icon: 'heroicon-o-cog' },
            { id: 'voice', name: 'Voice & Speech', icon: 'heroicon-o-microphone' },
            { id: 'behavior', name: 'Behavior', icon: 'heroicon-o-sparkles' },
            { id: 'llm', name: 'LLM Configuration', icon: 'heroicon-o-cpu-chip' },
            { id: 'functions', name: 'Functions & Tools', icon: 'heroicon-o-puzzle-piece' },
            { id: 'postcall', name: 'Post-Call Analysis', icon: 'heroicon-o-chart-bar' },
            { id: 'advanced', name: 'Advanced', icon: 'heroicon-o-adjustments-horizontal' }
        ],
        
        get filteredVoices() {
            if (!this.voiceSearchTerm) return this.voices;
            const search = this.voiceSearchTerm.toLowerCase();
            return this.voices.filter(voice => 
                voice.name.toLowerCase().includes(search) ||
                voice.language.toLowerCase().includes(search) ||
                voice.provider.toLowerCase().includes(search)
            );
        },
        
        addPostCallField() {
            this.editingPostCallAnalysis.push({
                name: '',
                type: 'string',
                description: ''
            });
        },
        
        removePostCallField(index) {
            this.editingPostCallAnalysis.splice(index, 1);
        },
        
        addFunction() {
            this.editingFunctions.push({
                name: '',
                type: 'webhook',
                url: '',
                description: '',
                speak_after_execution: true,
                execution_plan: 'stable'
            });
        },
        
        removeFunction(index) {
            this.editingFunctions.splice(index, 1);
        }
    }"
    style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    " 
    wire:click="closeAgentEditor">
    
    <div style="
        background: white;
        border-radius: 1rem;
        width: 100%;
        max-width: 1400px;
        height: 95vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    " wire:click.stop>
        
        {{-- Header --}}
        <div style="
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        ">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                    Agent Configuration Editor
                </h2>
                <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                    {{ $editingAgentFull['agent_name'] ?? 'New Agent' }} 
                    <span style="color: #9ca3af;">•</span> 
                    {{ $editingAgentFull['agent_id'] ?? 'Not created yet' }}
                </p>
                @if($editingAgentFull['version'] ?? null)
                    <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                        Version {{ $editingAgentFull['version'] }} 
                        @if($editingAgentFull['version_title'] ?? null)
                            - {{ $editingAgentFull['version_title'] }}
                        @endif
                        @if($editingAgentFull['is_published'] ?? false)
                            <span style="color: #10b981;">(Published)</span>
                        @else
                            <span style="color: #f59e0b;">(Draft)</span>
                        @endif
                    </div>
                @endif
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                @if($error)
                    <div style="padding: 0.5rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; color: #dc2626; font-size: 0.875rem;">
                        {{ $error }}
                    </div>
                @endif
                
                <button 
                    type="button"
                    wire:click="closeAgentEditor"
                    style="
                        padding: 0.5rem;
                        border-radius: 0.5rem;
                        border: none;
                        background: #f3f4f6;
                        color: #6b7280;
                        cursor: pointer;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.background='#e5e7eb'"
                    onmouseout="this.style.background='#f3f4f6'">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Tab Navigation --}}
        <div style="
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.5rem 0;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
            flex-shrink: 0;
        ">
            <template x-for="tab in tabs" :key="tab.id">
                <button 
                    @click="activeTab = tab.id"
                    :class="{
                        'border-b-2 border-indigo-500 text-indigo-600': activeTab === tab.id,
                        'text-gray-500 hover:text-gray-700': activeTab !== tab.id
                    }"
                    style="
                        padding: 0.75rem 1rem 1rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        white-space: nowrap;
                        background: transparent;
                        border: none;
                        border-bottom-width: 2px;
                        border-bottom-style: solid;
                        border-bottom-color: transparent;
                        cursor: pointer;
                        transition: all 0.2s;
                    ">
                    <span x-text="tab.name"></span>
                </button>
            </template>
        </div>
        
        {{-- Content Area --}}
        <div style="
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        ">
            {{-- Basic Settings Tab --}}
            <div x-show="activeTab === 'basic'" x-transition>
                <div style="max-width: 800px;">
                    {{-- Agent Name --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Agent Name
                        </label>
                        <input 
                            type="text"
                            wire:model="editingAgentFull.agent_name"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                outline: none;
                                transition: all 0.2s;
                            "
                            onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)';"
                            onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';">
                    </div>
                    
                    {{-- Language --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Language
                        </label>
                        <select 
                            wire:model="editingAgentFull.language"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                background: white;
                                outline: none;
                                cursor: pointer;
                            ">
                            <template x-for="lang in languages" :key="lang.code">
                                <option :value="lang.code" x-text="lang.name"></option>
                            </template>
                        </select>
                    </div>
                    
                    {{-- First Message --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            First Message (Begin Message)
                        </label>
                        <textarea 
                            wire:model="editingLLM.begin_message"
                            rows="3"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                outline: none;
                                resize: vertical;
                            "
                            placeholder="e.g., Hello! Thank you for calling. How can I help you today?"></textarea>
                    </div>
                    
                    {{-- Webhook URL --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Webhook URL
                        </label>
                        <input 
                            type="url"
                            wire:model="editingWebhookSettings.url"
                            placeholder="https://api.example.com/webhook"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                outline: none;
                            ">
                    </div>
                </div>
            </div>
            
            {{-- Voice & Speech Tab --}}
            <div x-show="activeTab === 'voice'" x-transition>
                <div style="max-width: 800px;">
                    {{-- Voice Selection --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Voice
                        </label>
                        <input 
                            type="text"
                            x-model="voiceSearchTerm"
                            placeholder="Search voices..."
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                margin-bottom: 0.5rem;
                            ">
                        <div style="
                            max-height: 200px;
                            overflow-y: auto;
                            border: 1px solid #d1d5db;
                            border-radius: 0.375rem;
                        ">
                            <template x-for="voice in filteredVoices" :key="voice.id">
                                <div 
                                    @click="selectedVoice = voice.id; editingAgentFull.voice_id = voice.id; $wire.set('editingAgentFull.voice_id', voice.id)"
                                    :class="{
                                        'bg-indigo-50 border-indigo-200': selectedVoice === voice.id,
                                        'hover:bg-gray-50': selectedVoice !== voice.id
                                    }"
                                    style="
                                        padding: 0.75rem;
                                        border-bottom: 1px solid #e5e7eb;
                                        cursor: pointer;
                                        transition: all 0.2s;
                                    ">
                                    <div style="font-weight: 500; color: #111827;" x-text="voice.name"></div>
                                    <div style="font-size: 0.75rem; color: #6b7280;">
                                        <span x-text="voice.provider"></span> • 
                                        <span x-text="voice.language"></span> • 
                                        <span x-text="voice.gender"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Voice Speed --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Voice Speed: <span x-text="editingAgentFull.voice_speed || 1.0"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingAgentFull.voice_speed"
                            min="0.5"
                            max="2.0"
                            step="0.1"
                            style="width: 100%;">
                    </div>
                    
                    {{-- Voice Temperature --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Voice Temperature: <span x-text="editingAgentFull.voice_temperature || 0.0"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingAgentFull.voice_temperature"
                            min="-1.0"
                            max="1.0"
                            step="0.1"
                            style="width: 100%;">
                    </div>
                    
                    {{-- Ambient Sound --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Ambient Sound
                        </label>
                        <select 
                            wire:model="editingAgentFull.ambient_sound"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                background: white;
                            ">
                            <template x-for="sound in ambientSounds" :key="sound.id">
                                <option :value="sound.id" x-text="sound.name"></option>
                            </template>
                        </select>
                    </div>
                    
                    {{-- Ambient Sound Volume --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Ambient Sound Volume: <span x-text="editingAgentFull.ambient_sound_volume || 1.0"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingAgentFull.ambient_sound_volume"
                            min="0.0"
                            max="2.0"
                            step="0.1"
                            style="width: 100%;">
                    </div>
                </div>
            </div>
            
            {{-- Behavior Tab --}}
            <div x-show="activeTab === 'behavior'" x-transition>
                <div style="max-width: 800px;">
                    {{-- Interruption Sensitivity --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Interruption Sensitivity: <span x-text="editingAgentFull.interruption_sensitivity || 1"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingAgentFull.interruption_sensitivity"
                            min="0"
                            max="2"
                            step="1"
                            style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                            <span>Low (0)</span>
                            <span>Normal (1)</span>
                            <span>High (2)</span>
                        </div>
                    </div>
                    
                    {{-- Responsiveness --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Responsiveness: <span x-text="editingAgentFull.responsiveness || 1"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingAgentFull.responsiveness"
                            min="0"
                            max="2"
                            step="1"
                            style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                            <span>Low (0)</span>
                            <span>Normal (1)</span>
                            <span>High (2)</span>
                        </div>
                    </div>
                    
                    {{-- Enable Backchannel --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input 
                                type="checkbox"
                                wire:model="editingAgentFull.enable_backchannel"
                                style="width: 1rem; height: 1rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                Enable Backchannel (uh-huh, yeah, etc.)
                            </span>
                        </label>
                    </div>
                    
                    {{-- Reminder Settings --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Reminder Trigger (ms)
                        </label>
                        <input 
                            type="number"
                            wire:model="editingAgentFull.reminder_trigger_ms"
                            min="0"
                            step="1000"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                            ">
                    </div>
                    
                    {{-- End Call After Silence --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            End Call After Silence (ms)
                        </label>
                        <input 
                            type="number"
                            wire:model="editingAgentFull.end_call_after_silence_ms"
                            min="0"
                            step="1000"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                            ">
                    </div>
                    
                    {{-- Max Call Duration --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Max Call Duration (ms)
                        </label>
                        <input 
                            type="number"
                            wire:model="editingAgentFull.max_call_duration_ms"
                            min="0"
                            step="60000"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                            ">
                    </div>
                </div>
            </div>
            
            {{-- LLM Configuration Tab --}}
            <div x-show="activeTab === 'llm'" x-transition>
                <div style="max-width: 800px;">
                    {{-- Model Selection --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Model
                        </label>
                        <select 
                            wire:model="editingLLM.model"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                background: white;
                            ">
                            <template x-for="model in models" :key="model.id">
                                <option :value="model.id" x-text="model.name"></option>
                            </template>
                        </select>
                    </div>
                    
                    {{-- Temperature --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Temperature: <span x-text="editingLLM.temperature || 0.7"></span>
                        </label>
                        <input 
                            type="range"
                            wire:model.live="editingLLM.temperature"
                            min="0"
                            max="2"
                            step="0.1"
                            style="width: 100%;">
                    </div>
                    
                    {{-- Max Tokens --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Max Tokens
                        </label>
                        <input 
                            type="number"
                            wire:model="editingLLM.max_tokens"
                            min="50"
                            max="4000"
                            step="50"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                            ">
                    </div>
                    
                    {{-- General Prompt --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            System Prompt
                        </label>
                        <textarea 
                            wire:model="editingLLM.general_prompt"
                            rows="10"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                font-family: monospace;
                                resize: vertical;
                            "
                            placeholder="Enter the system prompt for the agent..."></textarea>
                    </div>
                </div>
            </div>
            
            {{-- Functions & Tools Tab --}}
            <div x-show="activeTab === 'functions'" x-transition>
                <div style="max-width: 1000px;">
                    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">Custom Functions</h3>
                        <button 
                            type="button"
                            @click="addFunction()"
                            style="
                                padding: 0.5rem 1rem;
                                background: #6366f1;
                                color: white;
                                border: none;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                cursor: pointer;
                            ">
                            Add Function
                        </button>
                    </div>
                    
                    <template x-for="(func, index) in editingFunctions" :key="index">
                        <div style="
                            padding: 1rem;
                            margin-bottom: 1rem;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            background: #f9fafb;
                        ">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                        Function Name
                                    </label>
                                    <input 
                                        type="text"
                                        x-model="func.name"
                                        style="
                                            width: 100%;
                                            padding: 0.375rem 0.5rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.25rem;
                                            font-size: 0.875rem;
                                        ">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                        Type
                                    </label>
                                    <select 
                                        x-model="func.type"
                                        style="
                                            width: 100%;
                                            padding: 0.375rem 0.5rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.25rem;
                                            font-size: 0.875rem;
                                            background: white;
                                        ">
                                        <option value="webhook">Webhook</option>
                                        <option value="mcp">MCP Tool</option>
                                    </select>
                                </div>
                            </div>
                            <div style="margin-top: 0.75rem;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                    URL
                                </label>
                                <input 
                                    type="url"
                                    x-model="func.url"
                                    style="
                                        width: 100%;
                                        padding: 0.375rem 0.5rem;
                                        border: 1px solid #d1d5db;
                                        border-radius: 0.25rem;
                                        font-size: 0.875rem;
                                    ">
                            </div>
                            <div style="margin-top: 0.75rem;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                    Description
                                </label>
                                <input 
                                    type="text"
                                    x-model="func.description"
                                    style="
                                        width: 100%;
                                        padding: 0.375rem 0.5rem;
                                        border: 1px solid #d1d5db;
                                        border-radius: 0.25rem;
                                        font-size: 0.875rem;
                                    ">
                            </div>
                            <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input 
                                        type="checkbox"
                                        x-model="func.speak_after_execution"
                                        style="width: 0.875rem; height: 0.875rem;">
                                    <span style="font-size: 0.75rem; color: #6b7280;">
                                        Speak after execution
                                    </span>
                                </label>
                                <button 
                                    type="button"
                                    @click="removeFunction(index)"
                                    style="
                                        padding: 0.25rem 0.5rem;
                                        background: #fee2e2;
                                        color: #dc2626;
                                        border: none;
                                        border-radius: 0.25rem;
                                        font-size: 0.75rem;
                                        cursor: pointer;
                                    ">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Post-Call Analysis Tab --}}
            <div x-show="activeTab === 'postcall'" x-transition>
                <div style="max-width: 1000px;">
                    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">Post-Call Analysis Fields</h3>
                        <button 
                            type="button"
                            @click="addPostCallField()"
                            style="
                                padding: 0.5rem 1rem;
                                background: #6366f1;
                                color: white;
                                border: none;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                cursor: pointer;
                            ">
                            Add Field
                        </button>
                    </div>
                    
                    <template x-for="(field, index) in editingPostCallAnalysis" :key="index">
                        <div style="
                            padding: 1rem;
                            margin-bottom: 1rem;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            background: #f9fafb;
                        ">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 1rem; align-items: end;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                        Field Name
                                    </label>
                                    <input 
                                        type="text"
                                        x-model="field.name"
                                        style="
                                            width: 100%;
                                            padding: 0.375rem 0.5rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.25rem;
                                            font-size: 0.875rem;
                                        ">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                        Type
                                    </label>
                                    <select 
                                        x-model="field.type"
                                        style="
                                            width: 100%;
                                            padding: 0.375rem 0.5rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.25rem;
                                            font-size: 0.875rem;
                                            background: white;
                                        ">
                                        <option value="string">String</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="number">Number</option>
                                        <option value="array">Array</option>
                                        <option value="object">Object</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">
                                        Description
                                    </label>
                                    <input 
                                        type="text"
                                        x-model="field.description"
                                        style="
                                            width: 100%;
                                            padding: 0.375rem 0.5rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.25rem;
                                            font-size: 0.875rem;
                                        ">
                                </div>
                                <button 
                                    type="button"
                                    @click="removePostCallField(index)"
                                    style="
                                        padding: 0.375rem 0.75rem;
                                        background: #fee2e2;
                                        color: #dc2626;
                                        border: none;
                                        border-radius: 0.25rem;
                                        font-size: 0.75rem;
                                        cursor: pointer;
                                    ">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Advanced Tab --}}
            <div x-show="activeTab === 'advanced'" x-transition>
                <div style="max-width: 800px;">
                    {{-- Voicemail Detection --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Voicemail Detection Timeout (ms)
                        </label>
                        <input 
                            type="number"
                            wire:model="editingAgentFull.voicemail_detection_timeout_ms"
                            min="0"
                            step="1000"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                            ">
                    </div>
                    
                    {{-- Voicemail Message --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Voicemail Message
                        </label>
                        <textarea 
                            wire:model="editingAgentFull.voicemail_message"
                            rows="3"
                            style="
                                width: 100%;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                resize: vertical;
                            "
                            placeholder="Message to leave on voicemail..."></textarea>
                    </div>
                    
                    {{-- Normalize for Speech --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input 
                                type="checkbox"
                                wire:model="editingAgentFull.normalize_for_speech"
                                style="width: 1rem; height: 1rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                Normalize for Speech
                            </span>
                        </label>
                    </div>
                    
                    {{-- Opt Out Sensitive Data Storage --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input 
                                type="checkbox"
                                wire:model="editingAgentFull.opt_out_sensitive_data_storage"
                                style="width: 1rem; height: 1rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                Opt Out of Sensitive Data Storage
                            </span>
                        </label>
                    </div>
                    
                    {{-- Response Engine Type --}}
                    <div style="
                        padding: 1rem;
                        background: #f9fafb;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.5rem;
                    ">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            Response Engine Configuration
                        </h4>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            <div>Type: <span style="font-weight: 500; color: #111827;">{{ $editingAgentFull['response_engine']['type'] ?? 'retell-llm' }}</span></div>
                            <div>LLM ID: <span style="font-family: monospace; font-size: 0.75rem; color: #6366f1;">{{ $editingAgentFull['response_engine']['llm_id'] ?? 'Not set' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer Actions --}}
        <div style="
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        ">
            <div style="font-size: 0.875rem; color: #6b7280;">
                <span x-show="!isLoading">Last saved: Never</span>
                <span x-show="isLoading" style="color: #6366f1;">Saving changes...</span>
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <button 
                    type="button"
                    wire:click="closeAgentEditor"
                    style="
                        padding: 0.625rem 1.25rem;
                        background: white;
                        color: #374151;
                        border: 1px solid #d1d5db;
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                    ">
                    Cancel
                </button>
                
                <button 
                    type="button"
                    wire:click="saveAgentFull"
                    :disabled="isLoading"
                    style="
                        padding: 0.625rem 1.25rem;
                        background: #6366f1;
                        color: white;
                        border: none;
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s;
                        opacity: isLoading ? 0.5 : 1;
                    "
                    :style="{ cursor: isLoading ? 'not-allowed' : 'pointer' }">
                    <span x-show="!isLoading">Save All Changes</span>
                    <span x-show="isLoading">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>