{{-- Agent Editor Modal --}}
@if($showAgentEditor)
<div 
    x-data="{
        activeTab: 'general',
        versionMode: 'edit', // edit, create_new, duplicate
        showVersionDialog: false,
        voiceSearchTerm: '',
        selectedVoice: @entangle('editingAgent.voice_id'),
        
        voices: [
            // English Voices
            { id: 'openai-Alloy', name: 'Alloy', provider: 'OpenAI', language: 'en-US', gender: 'neutral', style: 'neutral' },
            { id: 'openai-Echo', name: 'Echo', provider: 'OpenAI', language: 'en-US', gender: 'male', style: 'neutral' },
            { id: 'openai-Fable', name: 'Fable', provider: 'OpenAI', language: 'en-US', gender: 'neutral', style: 'neutral' },
            { id: 'openai-Onyx', name: 'Onyx', provider: 'OpenAI', language: 'en-US', gender: 'male', style: 'neutral' },
            { id: 'openai-Nova', name: 'Nova', provider: 'OpenAI', language: 'en-US', gender: 'female', style: 'neutral' },
            { id: 'openai-Shimmer', name: 'Shimmer', provider: 'OpenAI', language: 'en-US', gender: 'female', style: 'neutral' },
            
            // German Voices
            { id: 'elevenlabs-Matilda', name: 'Matilda', provider: 'ElevenLabs', language: 'de-DE', gender: 'female', style: 'professional' },
            { id: 'elevenlabs-Wilhelm', name: 'Wilhelm', provider: 'ElevenLabs', language: 'de-DE', gender: 'male', style: 'professional' },
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
        
        getVoiceIcon(provider) {
            switch(provider) {
                case 'OpenAI': return '🎯';
                case 'ElevenLabs': return '🎙️';
                case 'PlayHT': return '🎵';
                default: return '🔊';
            }
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
        max-width: 1200px;
        height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    " @click.stop>
        
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
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827;">
                    Edit Agent: {{ $editingAgent['agent_name'] ?? 'New Agent' }}
                </h2>
                <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                    Agent ID: {{ $editingAgent['agent_id'] ?? 'Not created yet' }}
                </p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                {{-- Version Badge --}}
                @if(isset($editingAgent['version']))
                    <span style="
                        padding: 0.375rem 0.75rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        background: #eef2ff;
                        color: #6366f1;
                        border-radius: 9999px;
                    ">
                        {{ $editingAgent['version'] }}
                    </span>
                @endif
                
                {{-- Close Button --}}
                <button 
                    wire:click="closeAgentEditor"
                    style="
                        padding: 0.5rem;
                        background: transparent;
                        border: none;
                        color: #6b7280;
                        cursor: pointer;
                        border-radius: 0.375rem;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.backgroundColor='#f3f4f6'"
                    onmouseout="this.style.backgroundColor='transparent'">
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
            padding: 0 1.5rem;
            margin-top: 1rem;
            border-bottom: 1px solid #e5e7eb;
        ">
            <button 
                @click="activeTab = 'general'"
                :class="activeTab === 'general' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                style="
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                :style="activeTab === 'general' && { borderBottomColor: '#6366f1', color: '#6366f1' }">
                General Settings
            </button>
            
            <button 
                @click="activeTab = 'voice'"
                :class="activeTab === 'voice' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                style="
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                :style="activeTab === 'voice' && { borderBottomColor: '#6366f1', color: '#6366f1' }">
                Voice & Language
            </button>
            
            <button 
                @click="activeTab = 'prompt'"
                :class="activeTab === 'prompt' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                style="
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                :style="activeTab === 'prompt' && { borderBottomColor: '#6366f1', color: '#6366f1' }">
                System Prompt
            </button>
            
            <button 
                @click="activeTab = 'advanced'"
                :class="activeTab === 'advanced' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                style="
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                :style="activeTab === 'advanced' && { borderBottomColor: '#6366f1', color: '#6366f1' }">
                Advanced Settings
            </button>
            
            <button 
                @click="activeTab = 'version'"
                :class="activeTab === 'version' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                style="
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                :style="activeTab === 'version' && { borderBottomColor: '#6366f1', color: '#6366f1' }">
                Version Management
            </button>
        </div>
        
        {{-- Content Area --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.5rem;">
            {{-- General Settings Tab --}}
            <div x-show="activeTab === 'general'" x-transition>
                <div style="max-width: 800px; margin: 0 auto;">
                    <div style="space-y: 1.5rem;">
                        {{-- Agent Name --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Agent Name
                            </label>
                            <input 
                                type="text"
                                wire:model="editingAgent.agent_name"
                                placeholder="e.g., Customer Service Agent"
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                    transition: all 0.2s ease;
                                "
                                onfocus="this.style.borderColor='#6366f1'"
                                onblur="this.style.borderColor='#d1d5db'">
                        </div>
                        
                        {{-- Description --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Description
                            </label>
                            <textarea 
                                wire:model="editingAgent.description"
                                placeholder="Describe what this agent does..."
                                style="
                                    width: 100%;
                                    min-height: 100px;
                                    padding: 0.75rem 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                    resize: vertical;
                                    transition: all 0.2s ease;
                                "
                                onfocus="this.style.borderColor='#6366f1'"
                                onblur="this.style.borderColor='#d1d5db'"
                                rows="4"></textarea>
                        </div>
                        
                        {{-- Begin Message --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Greeting Message
                            </label>
                            <input 
                                type="text"
                                wire:model="editingAgent.begin_message"
                                placeholder="e.g., Hello! Thank you for calling. How can I help you today?"
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                    transition: all 0.2s ease;
                                "
                                onfocus="this.style.borderColor='#6366f1'"
                                onblur="this.style.borderColor='#d1d5db'">
                            <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                What the agent says when answering the call
                            </p>
                        </div>
                        
                        {{-- Agent Status --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Status
                            </label>
                            <select 
                                wire:model="editingAgent.status"
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    background: white;
                                    cursor: pointer;
                                    outline: none;
                                ">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="testing">Testing</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Voice & Language Tab --}}
            <div x-show="activeTab === 'voice'" x-transition>
                <div style="max-width: 800px; margin: 0 auto;">
                    {{-- Voice Search --}}
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Select Voice
                        </label>
                        <div style="position: relative;">
                            <svg style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input 
                                type="text"
                                x-model="voiceSearchTerm"
                                placeholder="Search voices by name, language, or provider..."
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem 0 44px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                ">
                        </div>
                    </div>
                    
                    {{-- Voice Grid --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        <template x-for="voice in filteredVoices" :key="voice.id">
                            <div 
                                @click="selectedVoice = voice.id; @this.set('editingAgent.voice_id', voice.id)"
                                :class="selectedVoice === voice.id ? 'ring-2 ring-indigo-500' : ''"
                                style="
                                    padding: 1rem;
                                    background: #f9fafb;
                                    border: 2px solid #e5e7eb;
                                    border-radius: 0.5rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                "
                                :style="selectedVoice === voice.id && { borderColor: '#6366f1', background: '#eef2ff' }">
                                
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span x-text="getVoiceIcon(voice.provider)" style="font-size: 1.5rem;"></span>
                                    <div x-show="selectedVoice === voice.id" style="
                                        width: 20px;
                                        height: 20px;
                                        background: #6366f1;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">
                                        <svg style="width: 12px; height: 12px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                                
                                <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827;" x-text="voice.name"></h4>
                                <p style="font-size: 0.75rem; color: #6b7280;" x-text="voice.provider"></p>
                                <p style="font-size: 0.75rem; color: #6b7280;" x-text="voice.language"></p>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <span style="
                                        padding: 0.125rem 0.375rem;
                                        font-size: 0.625rem;
                                        background: #e5e7eb;
                                        color: #374151;
                                        border-radius: 9999px;
                                    " x-text="voice.gender"></span>
                                    <span style="
                                        padding: 0.125rem 0.375rem;
                                        font-size: 0.625rem;
                                        background: #e5e7eb;
                                        color: #374151;
                                        border-radius: 9999px;
                                    " x-text="voice.style"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Voice Settings --}}
                    <div style="margin-top: 2rem; space-y: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Voice Speed
                            </label>
                            <input 
                                type="range"
                                wire:model="editingAgent.voice_speed"
                                min="0.5"
                                max="2"
                                step="0.1"
                                style="width: 100%;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #6b7280;">
                                <span>Slow (0.5x)</span>
                                <span>Normal (1x)</span>
                                <span>Fast (2x)</span>
                            </div>
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Voice Temperature (Emotion)
                            </label>
                            <input 
                                type="range"
                                wire:model="editingAgent.voice_temperature"
                                min="0"
                                max="1"
                                step="0.1"
                                style="width: 100%;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #6b7280;">
                                <span>Stable</span>
                                <span>Balanced</span>
                                <span>Expressive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- System Prompt Tab --}}
            <div x-show="activeTab === 'prompt'" x-transition>
                <div style="max-width: 800px; margin: 0 auto;">
                    <div style="margin-bottom: 1rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827;">
                            System Prompt
                        </h3>
                        <p style="font-size: 0.875rem; color: #6b7280;">
                            Define the agent's behavior, personality, and instructions
                        </p>
                    </div>
                    
                    <textarea 
                        wire:model="editingAgent.prompt"
                        placeholder="You are a helpful customer service agent..."
                        style="
                            width: 100%;
                            min-height: 400px;
                            padding: 1rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            font-family: 'Monaco', 'Menlo', monospace;
                            color: #111827;
                            background: #f9fafb;
                            outline: none;
                            resize: vertical;
                            line-height: 1.5;
                        "
                        onfocus="this.style.borderColor='#6366f1'"
                        onblur="this.style.borderColor='#d1d5db'"></textarea>
                    
                    {{-- Prompt Templates --}}
                    <div style="margin-top: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                            Quick Templates
                        </label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button 
                                type="button"
                                @click="$wire.set('editingAgent.prompt', 'Sie sind ein freundlicher und professioneller Kundenservice-Mitarbeiter...')"
                                style="
                                    padding: 0.375rem 0.75rem;
                                    font-size: 0.75rem;
                                    background: #eef2ff;
                                    color: #6366f1;
                                    border: none;
                                    border-radius: 0.375rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                ">
                                German Professional
                            </button>
                            
                            <button 
                                type="button"
                                @click="$wire.set('editingAgent.prompt', 'You are a friendly and helpful customer service representative...')"
                                style="
                                    padding: 0.375rem 0.75rem;
                                    font-size: 0.75rem;
                                    background: #eef2ff;
                                    color: #6366f1;
                                    border: none;
                                    border-radius: 0.375rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                ">
                                English Friendly
                            </button>
                            
                            <button 
                                type="button"
                                @click="$wire.set('editingAgent.prompt', 'Sie sind ein Terminbuchungsassistent für eine Arztpraxis...')"
                                style="
                                    padding: 0.375rem 0.75rem;
                                    font-size: 0.75rem;
                                    background: #eef2ff;
                                    color: #6366f1;
                                    border: none;
                                    border-radius: 0.375rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                ">
                                Medical Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Advanced Settings Tab --}}
            <div x-show="activeTab === 'advanced'" x-transition>
                <div style="max-width: 800px; margin: 0 auto;">
                    <div style="space-y: 1.5rem;">
                        {{-- Interruption Settings --}}
                        <div style="
                            background: #f9fafb;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            padding: 1.5rem;
                        ">
                            <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                                Interruption Handling
                            </h4>
                            
                            <div style="space-y: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input 
                                        type="checkbox"
                                        wire:model="editingAgent.interruption_enabled"
                                        style="
                                            width: 1rem;
                                            height: 1rem;
                                            border-radius: 0.25rem;
                                            border: 1px solid #d1d5db;
                                            cursor: pointer;
                                            margin-right: 0.5rem;
                                        ">
                                    <span style="font-size: 0.875rem; color: #374151;">
                                        Allow interruptions
                                    </span>
                                </label>
                                
                                <div>
                                    <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                                        Interruption Threshold (ms)
                                    </label>
                                    <input 
                                        type="number"
                                        wire:model="editingAgent.interruption_threshold"
                                        min="100"
                                        max="2000"
                                        step="100"
                                        placeholder="500"
                                        style="
                                            width: 200px;
                                            height: 32px;
                                            padding: 0 0.75rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.375rem;
                                            font-size: 0.75rem;
                                            color: #374151;
                                            outline: none;
                                        ">
                                </div>
                            </div>
                        </div>
                        
                        {{-- End Call Settings --}}
                        <div style="
                            background: #f9fafb;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            padding: 1.5rem;
                        ">
                            <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                                Call Ending Behavior
                            </h4>
                            
                            <div style="space-y: 1rem;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input 
                                        type="checkbox"
                                        wire:model="editingAgent.end_call_after_silence"
                                        style="
                                            width: 1rem;
                                            height: 1rem;
                                            border-radius: 0.25rem;
                                            border: 1px solid #d1d5db;
                                            cursor: pointer;
                                            margin-right: 0.5rem;
                                        ">
                                    <span style="font-size: 0.875rem; color: #374151;">
                                        End call after extended silence
                                    </span>
                                </label>
                                
                                <div>
                                    <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                                        Silence Duration (seconds)
                                    </label>
                                    <input 
                                        type="number"
                                        wire:model="editingAgent.silence_timeout"
                                        min="5"
                                        max="60"
                                        step="5"
                                        placeholder="30"
                                        style="
                                            width: 200px;
                                            height: 32px;
                                            padding: 0 0.75rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.375rem;
                                            font-size: 0.75rem;
                                            color: #374151;
                                            outline: none;
                                        ">
                                </div>
                                
                                <div>
                                    <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                                        Max Call Duration (minutes)
                                    </label>
                                    <input 
                                        type="number"
                                        wire:model="editingAgent.max_duration"
                                        min="1"
                                        max="60"
                                        step="1"
                                        placeholder="30"
                                        style="
                                            width: 200px;
                                            height: 32px;
                                            padding: 0 0.75rem;
                                            border: 1px solid #d1d5db;
                                            border-radius: 0.375rem;
                                            font-size: 0.75rem;
                                            color: #374151;
                                            outline: none;
                                        ">
                                </div>
                            </div>
                        </div>
                        
                        {{-- Webhook URL --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                                Webhook URL
                            </label>
                            <input 
                                type="url"
                                wire:model="editingAgent.webhook_url"
                                placeholder="https://api.askproai.de/api/retell/webhook"
                                style="
                                    width: 100%;
                                    height: 40px;
                                    padding: 0 1rem;
                                    border: 1px solid #d1d5db;
                                    border-radius: 0.5rem;
                                    font-size: 0.875rem;
                                    color: #111827;
                                    outline: none;
                                "
                                readonly>
                            <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                This is your webhook endpoint for receiving call events
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Version Management Tab --}}
            <div x-show="activeTab === 'version'" x-transition>
                <div style="max-width: 800px; margin: 0 auto;">
                    {{-- Version Actions --}}
                    <div style="
                        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
                        border: 1px solid #6366f1;
                        border-radius: 0.5rem;
                        padding: 1.5rem;
                        margin-bottom: 2rem;
                    ">
                        <h4 style="font-size: 1rem; font-weight: 600; color: #4338ca; margin-bottom: 1rem;">
                            Version Management
                        </h4>
                        
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button 
                                @click="versionMode = 'create_new'; showVersionDialog = true"
                                style="
                                    padding: 0.5rem 1rem;
                                    font-size: 0.875rem;
                                    font-weight: 500;
                                    background: white;
                                    color: #6366f1;
                                    border: 1px solid #6366f1;
                                    border-radius: 0.5rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                ">
                                <svg style="width: 1rem; height: 1rem; display: inline-block; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create New Version
                            </button>
                            
                            <button 
                                @click="versionMode = 'duplicate'; showVersionDialog = true"
                                style="
                                    padding: 0.5rem 1rem;
                                    font-size: 0.875rem;
                                    font-weight: 500;
                                    background: white;
                                    color: #6366f1;
                                    border: 1px solid #6366f1;
                                    border-radius: 0.5rem;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                ">
                                <svg style="width: 1rem; height: 1rem; display: inline-block; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Duplicate Current Version
                            </button>
                        </div>
                        
                        <p style="font-size: 0.75rem; color: #6366f1; margin-top: 1rem;">
                            Current Version: {{ $editingAgent['version'] ?? 'V1' }}
                            @if(isset($editingAgent['is_active']) && $editingAgent['is_active'])
                                <span style="
                                    margin-left: 0.5rem;
                                    padding: 0.125rem 0.5rem;
                                    background: #10b981;
                                    color: white;
                                    font-size: 0.625rem;
                                    border-radius: 9999px;
                                ">ACTIVE</span>
                            @endif
                        </p>
                    </div>
                    
                    {{-- Version History --}}
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                            Version History
                        </h4>
                        
                        <div style="space-y: 0.75rem;">
                            @if(isset($groupedAgents[$editingAgent['base_name']]))
                                @foreach($groupedAgents[$editingAgent['base_name']]['versions'] as $version)
                                    <div style="
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        padding: 1rem;
                                        background: {{ $version['is_active'] ? '#eef2ff' : '#f9fafb' }};
                                        border: 1px solid {{ $version['is_active'] ? '#6366f1' : '#e5e7eb' }};
                                        border-radius: 0.5rem;
                                    ">
                                        <div>
                                            <h5 style="font-size: 0.875rem; font-weight: 600; color: #111827;">
                                                {{ $version['version'] }}
                                            </h5>
                                            <p style="font-size: 0.75rem; color: #6b7280;">
                                                Agent ID: {{ $version['agent_id'] }}
                                            </p>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            @if($version['is_active'])
                                                <span style="
                                                    padding: 0.25rem 0.75rem;
                                                    background: #10b981;
                                                    color: white;
                                                    font-size: 0.75rem;
                                                    font-weight: 500;
                                                    border-radius: 9999px;
                                                ">
                                                    Active
                                                </span>
                                            @else
                                                <button 
                                                    wire:click="activateAgentVersion('{{ $version['agent_id'] }}')"
                                                    style="
                                                        padding: 0.25rem 0.75rem;
                                                        font-size: 0.75rem;
                                                        background: white;
                                                        color: #6366f1;
                                                        border: 1px solid #6366f1;
                                                        border-radius: 0.375rem;
                                                        cursor: pointer;
                                                        transition: all 0.2s ease;
                                                    ">
                                                    Activate
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
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
            <div style="font-size: 0.75rem; color: #6b7280;">
                Last updated: {{ $editingAgent['updated_at'] ?? 'Never' }}
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <button 
                    type="button"
                    wire:click="closeAgentEditor"
                    class="modern-btn modern-btn-secondary">
                    Cancel
                </button>
                
                <button 
                    wire:click="saveAgent"
                    class="modern-btn modern-btn-primary"
                    style="
                        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                        color: white;
                        padding: 0.5rem 1.25rem;
                        font-weight: 600;
                    ">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
    
    {{-- Version Dialog --}}
    <div x-show="showVersionDialog" x-transition
         style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            z-index: 60;
            width: 90%;
            max-width: 500px;
         ">
        <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin-bottom: 1rem;">
            <span x-show="versionMode === 'create_new'">Create New Version</span>
            <span x-show="versionMode === 'duplicate'">Duplicate Version</span>
        </h3>
        
        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1.5rem;">
            <span x-show="versionMode === 'create_new'">This will create a new version of the agent with the current settings.</span>
            <span x-show="versionMode === 'duplicate'">This will create an exact copy of the current agent version.</span>
        </p>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                Version Name
            </label>
            <input 
                type="text"
                wire:model="newVersionName"
                placeholder="e.g., V2"
                style="
                    width: 100%;
                    height: 40px;
                    padding: 0 1rem;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    color: #111827;
                    outline: none;
                ">
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button 
                @click="showVersionDialog = false"
                style="
                    padding: 0.5rem 1rem;
                    font-size: 0.875rem;
                    background: #f3f4f6;
                    color: #374151;
                    border: none;
                    border-radius: 0.5rem;
                    cursor: pointer;
                ">
                Cancel
            </button>
            <button 
                wire:click="createVersion"
                @click="showVersionDialog = false"
                style="
                    padding: 0.5rem 1rem;
                    font-size: 0.875rem;
                    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                    color: white;
                    border: none;
                    border-radius: 0.5rem;
                    cursor: pointer;
                    font-weight: 600;
                ">
                Create Version
            </button>
        </div>
    </div>
</div>
@endif