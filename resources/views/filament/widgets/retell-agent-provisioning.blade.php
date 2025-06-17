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
                                @if($status['createdAt'])
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
                    <x-filament::button
                        wire:click="updateAgent"
                        color="gray"
                        size="sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Agent aktualisieren
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
                    href="https://app.retell.ai/agents"
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
</x-filament-widgets::widget>