{{-- Agent Configuration Details --}}
@if($selectedAgent && (isset($selectedAgent['full_config']) || isset($selectedAgent['voice_settings']) || isset($selectedAgent['llm_settings'])))
<div style="margin-top: 2rem;">
    {{-- Agent Details Header --}}
    <div style="background: white; border-radius: 12px 12px 0 0; padding: 1.5rem; border: 1px solid #e5e7eb; border-bottom: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0;">
                    {{ $selectedAgent['agent_name'] ?? 'Agent Configuration' }}
                </h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                    Agent ID: {{ $selectedAgent['agent_id'] ?? 'N/A' }}
                </p>
            </div>
            <button wire:click="$set('selectedAgent', null)"
                    style="padding: 0.5rem; background: none; border: none; cursor: pointer; color: #6b7280; hover:color: #374151;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    
    {{-- Configuration Tabs --}}
    <div x-data="{ configTab: 'voice' }" style="background: white; border-radius: 0 0 12px 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; border-top: none;">
        {{-- Tab Navigation --}}
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
            <button @click="configTab = 'voice'" 
                    :style="configTab === 'voice' ? 'color: #6366f1; border-bottom: 2px solid #6366f1; font-weight: 600;' : 'color: #6b7280;'"
                    style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer; transition: all 0.2s;">
                Stimme & Audio
            </button>
            <button @click="configTab = 'llm'" 
                    :style="configTab === 'llm' ? 'color: #6366f1; border-bottom: 2px solid #6366f1; font-weight: 600;' : 'color: #6b7280;'"
                    style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer; transition: all 0.2s;">
                KI-Modell
            </button>
            <button @click="configTab = 'conversation'" 
                    :style="configTab === 'conversation' ? 'color: #6366f1; border-bottom: 2px solid #6366f1; font-weight: 600;' : 'color: #6b7280;'"
                    style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer; transition: all 0.2s;">
                Konversation
            </button>
            <button @click="configTab = 'analysis'" 
                    :style="configTab === 'analysis' ? 'color: #6366f1; border-bottom: 2px solid #6366f1; font-weight: 600;' : 'color: #6b7280;'"
                    style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer; transition: all 0.2s;">
                Analyse
            </button>
            <button @click="configTab = 'tools'" 
                    :style="configTab === 'tools' ? 'color: #6366f1; border-bottom: 2px solid #6366f1; font-weight: 600;' : 'color: #6b7280;'"
                    style="padding: 0.5rem 1rem; background: none; border: none; cursor: pointer; transition: all 0.2s;">
                Funktionen
            </button>
        </div>
        
        {{-- Voice & Audio Settings --}}
        <div x-show="configTab === 'voice'" x-transition>
            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Stimme & Audio Einstellungen</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                @if(isset($selectedAgent['voice_settings']))
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Stimme</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['voice_settings']['voice_id'] ?? 'Nicht festgelegt' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Stimmmodell</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['voice_settings']['voice_model'] ?? 'Standard' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Geschwindigkeit</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['voice_settings']['voice_speed'] ?? '1.0' }}x</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Lautstärke</label>
                        <p style="font-weight: 500; color: #111827;">{{ ($selectedAgent['voice_settings']['volume'] ?? 1) * 100 }}%</p>
                    </div>
                @endif
                
                @if(isset($selectedAgent['audio_settings']))
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Umgebungsgeräusche</label>
                        <p style="font-weight: 500; color: #111827;">{{ ($selectedAgent['audio_settings']['ambient_sound_volume'] ?? 0) * 100 }}%</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Rauschunterdrückung</label>
                        <p style="font-weight: 500; color: #111827;">{{ ucfirst($selectedAgent['audio_settings']['denoising_mode'] ?? 'off') }}</p>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- LLM Settings --}}
        <div x-show="configTab === 'llm'" x-transition>
            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">KI-Modell Einstellungen</h4>
            @if(isset($selectedAgent['llm_settings']))
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Modell</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['llm_settings']['model'] ?? 'gpt-4' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Temperatur</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['llm_settings']['model_temperature'] ?? '0.7' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; grid-column: span 2;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Hohe Priorität</label>
                        <p style="font-weight: 500; color: #111827;">
                            @if($selectedAgent['llm_settings']['model_high_priority'] ?? false)
                                <span style="color: #10b981;">✓ Aktiviert</span>
                            @else
                                <span style="color: #6b7280;">✗ Deaktiviert</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                {{-- Prompt Display --}}
                <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                    <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.5rem;">System-Prompt</label>
                    <div style="max-height: 300px; overflow-y: auto; background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                        <pre style="font-family: monospace; font-size: 0.75rem; white-space: pre-wrap; margin: 0;">{{ $selectedAgent['llm_settings']['general_prompt'] ?? 'Kein Prompt konfiguriert' }}</pre>
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Conversation Settings --}}
        <div x-show="configTab === 'conversation'" x-transition>
            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Konversationseinstellungen</h4>
            @if(isset($selectedAgent['conversation_settings']))
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Sprache</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['conversation_settings']['language'] ?? 'de-DE' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Unterbrechungsempfindlichkeit</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['conversation_settings']['interruption_sensitivity'] ?? '0.5' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Reaktionsgeschwindigkeit</label>
                        <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['conversation_settings']['responsiveness'] ?? '1' }}</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Stille bis Anrufende</label>
                        <p style="font-weight: 500; color: #111827;">{{ ($selectedAgent['conversation_settings']['end_call_after_silence_ms'] ?? 30000) / 1000 }}s</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Max. Anrufdauer</label>
                        <p style="font-weight: 500; color: #111827;">{{ ($selectedAgent['conversation_settings']['max_call_duration_ms'] ?? 300000) / 60000 }} Min.</p>
                    </div>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Rückmeldesignale</label>
                        <p style="font-weight: 500; color: #111827;">
                            @if($selectedAgent['conversation_settings']['enable_backchannel'] ?? false)
                                <span style="color: #10b981;">✓ Aktiviert</span>
                            @else
                                <span style="color: #6b7280;">✗ Deaktiviert</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                @if(!empty($selectedAgent['conversation_settings']['backchannel_words']))
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.5rem;">Rückmeldesignal-Wörter</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            @foreach($selectedAgent['conversation_settings']['backchannel_words'] as $word)
                                <span style="background: white; padding: 0.25rem 0.75rem; border-radius: 9999px; border: 1px solid #e5e7eb; font-size: 0.875rem;">
                                    {{ $word }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
        
        {{-- Analysis Settings --}}
        <div x-show="configTab === 'analysis'" x-transition>
            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Analyseeinstellungen</h4>
            @if(isset($selectedAgent['analysis_settings']))
                <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.25rem;">Nachgesprächs-Analysemodell</label>
                    <p style="font-weight: 500; color: #111827;">{{ $selectedAgent['analysis_settings']['post_call_analysis_model'] ?? 'gpt-4' }}</p>
                </div>
                
                @if(!empty($selectedAgent['analysis_settings']['post_call_analysis_data']))
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.875rem; color: #6b7280; display: block; margin-bottom: 0.5rem;">Analysedaten</label>
                        <div style="display: grid; gap: 0.5rem;">
                            @foreach($selectedAgent['analysis_settings']['post_call_analysis_data'] as $data)
                                <div style="background: white; padding: 0.75rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                                    <div style="font-weight: 500; color: #111827; font-size: 0.875rem;">{{ $data['name'] ?? 'Unbekannt' }}</div>
                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">{{ $data['description'] ?? '' }}</div>
                                    <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">Typ: {{ $data['type'] ?? 'string' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
        
        {{-- Tools/Functions --}}
        <div x-show="configTab === 'tools'" x-transition>
            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Konfigurierte Funktionen</h4>
            @if(!empty($selectedAgent['tools']))
                <div style="display: grid; gap: 1rem;">
                    @foreach($selectedAgent['tools'] as $tool)
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                <h5 style="font-weight: 600; color: #111827; font-size: 0.95rem;">{{ $tool['name'] ?? 'Unbekannte Funktion' }}</h5>
                                <span style="background: #e0e7ff; color: #4338ca; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem;">
                                    {{ $tool['type'] ?? 'custom' }}
                                </span>
                            </div>
                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">{{ $tool['description'] ?? 'Keine Beschreibung' }}</p>
                            
                            @if($tool['type'] === 'custom' && isset($tool['url']))
                                <div style="font-size: 0.75rem; color: #9ca3af;">
                                    <span>URL: {{ $tool['url'] ?? '' }}</span>
                                </div>
                            @endif
                            
                            @if($tool['type'] === 'transfer_call' && isset($tool['number']))
                                <div style="font-size: 0.75rem; color: #9ca3af;">
                                    <span>Nummer: {{ $tool['number'] ?? '' }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: #6b7280;">Keine Funktionen konfiguriert</p>
            @endif
        </div>
    </div>
</div>
@endif