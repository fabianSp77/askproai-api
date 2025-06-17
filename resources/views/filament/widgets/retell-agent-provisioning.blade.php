<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🤖 Retell.ai Agent Verwaltung
        </x-slot>

        <x-slot name="description">
            Automatische Erstellung und Verwaltung des KI-Telefonagenten
        </x-slot>

        @php
            $status = $this->getAgentStatus();
            $checks = $this->getProvisioningChecks();
            $allChecksPassed = collect($checks)->every(fn($check) => $check['passed']);
        @endphp

        <div class="space-y-4">
            {{-- Current Status --}}
            <div class="p-4 rounded-lg {{ $status['status'] === 'active' ? 'bg-success-50' : ($status['status'] === 'not_provisioned' ? 'bg-warning-50' : 'bg-danger-50') }}">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium {{ $status['status'] === 'active' ? 'text-success-700' : ($status['status'] === 'not_provisioned' ? 'text-warning-700' : 'text-danger-700') }}">
                            Status: {{ $status['message'] }}
                        </h4>
                        @if($status['hasAgent'])
                            <p class="text-xs mt-1 {{ $status['status'] === 'active' ? 'text-success-600' : 'text-gray-600' }}">
                                Agent ID: {{ $status['agentId'] }}
                                @if(isset($status['createdAt']) && $status['createdAt'])
                                    | Erstellt: {{ $status['createdAt'] }}
                                @endif
                            </p>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        @if($status['status'] === 'active')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success-100 text-success-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Aktiv
                            </span>
                        @elseif($status['status'] === 'not_provisioned')
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning-100 text-warning-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Nicht vorhanden
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                </svg>
                                Inaktiv
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Prerequisites Check --}}
            @if($status['status'] === 'not_provisioned')
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Voraussetzungen für Agent-Erstellung:</h4>
                    <div class="space-y-1">
                        @foreach($checks as $check)
                            <div class="flex items-center text-xs">
                                @if($check['passed'])
                                    <svg class="w-4 h-4 text-success-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-600">{{ $check['label'] }}</span>
                                @else
                                    <svg class="w-4 h-4 text-danger-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-500">{{ $check['label'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-2">
                @if($status['status'] === 'not_provisioned')
                    <x-filament::button
                        wire:click="provisionAgent"
                        :disabled="!$allChecksPassed"
                        color="primary"
                        size="sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Agent erstellen
                    </x-filament::button>
                @elseif($status['status'] === 'active')
                    {{-- Agent aktualisieren Button temporär deaktiviert wegen Livewire-Problem --}}
                    <x-filament::button
                        disabled
                        color="gray"
                        size="sm"
                        title="Funktion temporär deaktiviert"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Agent ist aktiv
                    </x-filament::button>
                @elseif($status['hasAgent'] && $status['status'] !== 'active')
                    <x-filament::button
                        wire:click="updateAgent"
                        color="warning"
                        size="sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Agent aktivieren
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="testAgent"
                        color="success"
                        size="sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Test-Anruf
                    </x-filament::button>
                @endif
                
                <x-filament::link
                    href="https://dashboard.retellai.com"
                    target="_blank"
                    color="gray"
                    size="sm"
                    tag="a"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Retell.ai Dashboard
                </x-filament::link>
            </div>

            {{-- Info Box --}}
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Der Agent wird automatisch mit den Daten dieser Filiale konfiguriert:
                            Services, Öffnungszeiten, Mitarbeiter und Kontaktinformationen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
    
    {{-- Agent Details Section --}}
    @if($status['status'] === 'active' && $status['hasAgent'])
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                📋 Agent Konfiguration
            </x-slot>
            
            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-700 mb-3">
                        Um die vollständigen Agent-Einstellungen zu sehen oder zu bearbeiten, besuchen Sie bitte das Retell.ai Dashboard.
                        <br><br>
                        <strong>Agent ID:</strong> <code class="bg-gray-200 px-1 rounded">{{ $record->retell_agent_id }}</code>
                    </p>
                    
                    <div class="space-y-2">
                        <a href="https://dashboard.retellai.com/agents/{{ $record->retell_agent_id }}" 
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Agent-Einstellungen öffnen
                        </a>
                    </div>
                </div>
                
                <div class="text-sm text-gray-600">
                    <p class="font-medium mb-2">In Retell.ai können Sie folgende Einstellungen sehen und bearbeiten:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>System Prompt (Anweisungen für den Agent)</li>
                        <li>Sprachmodell (GPT-4, GPT-3.5, etc.)</li>
                        <li>Stimme und Spracheinstellungen</li>
                        <li>Webhook-Konfiguration</li>
                        <li>Erweiterte Funktionen und Parameter</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    @endif
    
    @if(false && $agentDetails)
        @if(isset($agentDetails['error']))
            {{-- Error handling code --}}
        @else
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                📋 Agent Konfigurationsdetails
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Basic Info --}}
                <div class="space-y-2">
                    <h4 class="font-medium text-sm text-gray-700">Basis-Informationen</h4>
                    <dl class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Agent Name:</dt>
                            <dd class="font-medium">{{ $agentDetails['basic']['agent_name'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Modell:</dt>
                            <dd class="font-mono">{{ $agentDetails['model']['llm_id'] }}</dd>
                        </div>
                    </dl>
                </div>
                
                {{-- Voice Settings --}}
                <div class="space-y-2">
                    <h4 class="font-medium text-sm text-gray-700">Sprache & Stimme</h4>
                    <dl class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Sprache:</dt>
                            <dd>{{ $agentDetails['language']['language'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Stimme:</dt>
                            <dd class="font-mono text-xs">{{ $agentDetails['language']['voice_id'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Geschwindigkeit:</dt>
                            <dd>{{ $agentDetails['language']['voice_speed'] }}x</dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            {{-- System Prompt --}}
            <div class="mt-4">
                <h4 class="font-medium text-sm text-gray-700 mb-2">System Prompt</h4>
                <div class="bg-gray-50 p-3 rounded text-xs font-mono text-gray-600 max-h-48 overflow-y-auto">
                    <pre class="whitespace-pre-wrap">{{ Str::limit($agentDetails['model']['system_prompt'], 500) }}</pre>
                </div>
                @if(strlen($agentDetails['model']['system_prompt']) > 500)
                    <p class="text-xs text-gray-500 mt-1">... (gekürzt, vollständiger Prompt in Retell.ai)</p>
                @endif
            </div>
            
            {{-- Edit Link --}}
            <div class="mt-4 flex justify-end">
                <a href="https://dashboard.retellai.com/agents/{{ $record->retell_agent_id }}" 
                   target="_blank"
                   class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Vollständige Konfiguration in Retell.ai anzeigen
                </a>
            </div>
        </x-filament::section>
        @endif
    @endif
</x-filament-widgets::widget>