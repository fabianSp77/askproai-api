{{-- Settings Tab Content --}}
<div style="display: grid; gap: 1.5rem;">
    {{-- API Configuration --}}
    <div class="modern-card">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 1.5rem;">
            API Configuration
        </h3>
        
        <div style="display: grid; gap: 1.25rem;">
            {{-- Webhook URL --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Webhook URL
                </label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input 
                        type="text" 
                        value="https://api.askproai.de/api/retell/webhook" 
                        readonly
                        style="
                            flex: 1; 
                            padding: 0.625rem 0.875rem; 
                            border: 1px solid #e5e7eb; 
                            border-radius: 0.375rem; 
                            background: #f9fafb;
                            font-family: monospace;
                            font-size: 0.875rem;
                            color: var(--modern-text-primary);
                        ">
                    <button 
                        onclick="navigator.clipboard.writeText('https://api.askproai.de/api/retell/webhook'); this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy', 2000);"
                        class="modern-btn modern-btn-secondary"
                        style="min-width: 80px;">
                        Copy
                    </button>
                </div>
                <p style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-top: 0.25rem;">
                    Use this URL in your Retell.ai webhook configuration
                </p>
            </div>
            
            {{-- API Key Status --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Retell API Key
                </label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input 
                        type="password" 
                        value="{{ $retellApiKey ? '••••••••••••' . substr($retellApiKey, -4) : '' }}" 
                        readonly
                        style="
                            flex: 1; 
                            padding: 0.625rem 0.875rem; 
                            border: 1px solid #e5e7eb; 
                            border-radius: 0.375rem;
                            font-family: monospace;
                            font-size: 0.875rem;
                            background: #f9fafb;
                        ">
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.375rem;
                        padding: 0.375rem 0.75rem;
                        border-radius: 0.375rem;
                        font-size: 0.75rem;
                        font-weight: 500;
                        {{ $retellApiKey ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' }}
                    ">
                        <svg style="width: 0.875rem; height: 0.875rem;" fill="currentColor" viewBox="0 0 20 20">
                            @if($retellApiKey)
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            @endif
                        </svg>
                        {{ $retellApiKey ? 'Connected' : 'Not Configured' }}
                    </span>
                </div>
                <p style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-top: 0.25rem;">
                    API key is managed in Company settings
                </p>
            </div>
        </div>
    </div>
    
    {{-- Voice Defaults --}}
    <div class="modern-card">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 1.5rem;">
            Default Voice Settings
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.25rem;">
            {{-- Voice Selection --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Default Voice
                </label>
                <select 
                    wire:model.live="defaultSettings.voice_id"
                    style="
                        width: 100%; 
                        padding: 0.625rem 0.875rem; 
                        border: 1px solid #e5e7eb; 
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        background: white;
                        cursor: pointer;
                    ">
                    <option value="openai-Alloy">Alloy (OpenAI) - Neutral</option>
                    <option value="openai-Echo">Echo (OpenAI) - Male</option>
                    <option value="openai-Fable">Fable (OpenAI) - British</option>
                    <option value="openai-Onyx">Onyx (OpenAI) - Deep Male</option>
                    <option value="openai-Nova">Nova (OpenAI) - Female</option>
                    <option value="openai-Shimmer">Shimmer (OpenAI) - Soft Female</option>
                </select>
            </div>
            
            {{-- Language Selection --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Default Language
                </label>
                <select 
                    wire:model.live="defaultSettings.language"
                    style="
                        width: 100%; 
                        padding: 0.625rem 0.875rem; 
                        border: 1px solid #e5e7eb; 
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        background: white;
                        cursor: pointer;
                    ">
                    <option value="de-DE">Deutsch (Deutschland)</option>
                    <option value="en-US">English (US)</option>
                    <option value="en-GB">English (UK)</option>
                    <option value="fr-FR">Français</option>
                    <option value="es-ES">Español</option>
                    <option value="it-IT">Italiano</option>
                    <option value="pt-BR">Português (Brasil)</option>
                    <option value="nl-NL">Nederlands</option>
                </select>
            </div>
            
            {{-- Interruption Sensitivity --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Interruption Sensitivity
                </label>
                <select 
                    wire:model.live="defaultSettings.interruption_sensitivity"
                    style="
                        width: 100%; 
                        padding: 0.625rem 0.875rem; 
                        border: 1px solid #e5e7eb; 
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        background: white;
                        cursor: pointer;
                    ">
                    <option value="0">Low (0) - Hard to interrupt</option>
                    <option value="1">Medium (1) - Balanced</option>
                    <option value="2">High (2) - Easy to interrupt</option>
                </select>
            </div>
            
            {{-- Response Speed --}}
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Response Speed
                </label>
                <select 
                    wire:model.live="defaultSettings.response_speed"
                    style="
                        width: 100%; 
                        padding: 0.625rem 0.875rem; 
                        border: 1px solid #e5e7eb; 
                        border-radius: 0.375rem;
                        font-size: 0.875rem;
                        background: white;
                        cursor: pointer;
                    ">
                    <option value="0.8">Slow (0.8x)</option>
                    <option value="1.0">Normal (1.0x)</option>
                    <option value="1.2">Fast (1.2x)</option>
                </select>
            </div>
        </div>
        
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--modern-border);">
            <button 
                wire:click="saveDefaultSettings"
                wire:loading.attr="disabled"
                class="modern-btn modern-btn-primary">
                <span wire:loading.remove>Save Voice Defaults</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </div>
    
    {{-- System Information --}}
    <div class="modern-card">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 1.5rem;">
            System Information
        </h3>
        
        <div style="display: grid; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                <span style="font-weight: 500; color: var(--modern-text-secondary);">Company</span>
                <span style="font-weight: 600;">{{ auth()->user()->company->name ?? 'Not assigned' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                <span style="font-weight: 500; color: var(--modern-text-secondary);">Active Agents</span>
                <span style="font-weight: 600;">{{ count($agents) }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                <span style="font-weight: 500; color: var(--modern-text-secondary);">Phone Numbers</span>
                <span style="font-weight: 600;">{{ count($phoneNumbers) }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                <span style="font-weight: 500; color: var(--modern-text-secondary);">API Status</span>
                <span style="color: {{ $retellApiKey ? '#10b981' : '#ef4444' }}; font-weight: 600;">
                    {{ $retellApiKey ? '● Connected' : '● Disconnected' }}
                </span>
            </div>
        </div>
    </div>
</div>