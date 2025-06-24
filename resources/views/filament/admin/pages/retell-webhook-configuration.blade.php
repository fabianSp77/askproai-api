<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Webhook Status --}}
            <x-filament::card>
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Webhook Status</h3>
                        <p class="mt-1 text-2xl font-semibold">
                            @if($this->isConfiguredInRetell)
                                <span class="text-success-600">Konfiguriert</span>
                            @else
                                <span class="text-warning-600">Nicht konfiguriert</span>
                            @endif
                        </p>
                    </div>
                    <x-heroicon-o-link class="w-5 h-5 text-gray-400" />
                </div>
            </x-filament::card>
            
            {{-- Last Test --}}
            <x-filament::card>
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Letzter Test</h3>
                        <p class="mt-1 text-sm">
                            @if($this->lastTestTime)
                                {{ $this->lastTestTime }}
                            @else
                                <span class="text-gray-400">Noch nicht getestet</span>
                            @endif
                        </p>
                        @if($this->lastTestStatus)
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1',
                                'bg-success-100 text-success-800' => $this->lastTestStatus === 'success',
                                'bg-danger-100 text-danger-800' => $this->lastTestStatus === 'failed',
                            ])>
                                {{ $this->lastTestStatus === 'success' ? 'Erfolgreich' : 'Fehlgeschlagen' }}
                            </span>
                        @endif
                    </div>
                    <x-heroicon-o-clock class="w-5 h-5 text-gray-400" />
                </div>
            </x-filament::card>
            
            {{-- Custom Functions --}}
            <x-filament::card>
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Custom Functions</h3>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ collect($customFunctions)->where('enabled', true)->count() }}
                            <span class="text-sm text-gray-500">/ {{ count($customFunctions) }}</span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Aktiviert</p>
                    </div>
                    <x-heroicon-o-code-bracket class="w-5 h-5 text-gray-400" />
                </div>
            </x-filament::card>
        </div>
        
        {{-- Form --}}
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="mt-6 flex gap-3">
                <x-filament::button type="submit">
                    Speichern
                </x-filament::button>
                
                <x-filament::button 
                    color="gray" 
                    wire:click="testWebhook"
                    wire:loading.attr="disabled"
                    wire:target="testWebhook"
                >
                    <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="testWebhook" />
                    <span wire:loading.remove wire:target="testWebhook">Webhook testen</span>
                    <span wire:loading wire:target="testWebhook">Teste...</span>
                </x-filament::button>
                
                <x-filament::button 
                    color="gray" 
                    wire:click="deployToRetell"
                    wire:loading.attr="disabled"
                >
                    <x-heroicon-m-cloud-arrow-up class="h-5 w-5 -ml-1 mr-2" />
                    Zu Retell.ai deployen
                </x-filament::button>
                
                <x-filament::button 
                    color="gray" 
                    wire:click="getAgentPromptTemplate"
                >
                    <x-heroicon-m-document-text class="h-5 w-5 -ml-1 mr-2" />
                    Agent Prompt Template
                </x-filament::button>
            </div>
        </form>
        
        {{-- Test Results --}}
        @if($testResults)
            <x-filament::card>
                <h3 class="text-lg font-medium mb-4">Test-Ergebnisse</h3>
                
                @if($testResults['success'] ?? false)
                    <div class="bg-success-50 border border-success-200 rounded-lg p-4">
                        <div class="flex">
                            <x-heroicon-s-check-circle class="h-5 w-5 text-success-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-success-800">
                                    Webhook-Test erfolgreich
                                </h3>
                                <div class="mt-2 text-sm text-success-700">
                                    <p>Status Code: {{ $testResults['status_code'] ?? 'N/A' }}</p>
                                    <p>Response Zeit: {{ $testResults['response_time_ms'] ?? 'N/A' }}ms</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-4">
                        <div class="flex">
                            <x-heroicon-s-x-circle class="h-5 w-5 text-danger-400" />
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-danger-800">
                                    Webhook-Test fehlgeschlagen
                                </h3>
                                <div class="mt-2 text-sm text-danger-700">
                                    <p>{{ $testResults['message'] ?? $testResults['error'] ?? 'Unbekannter Fehler' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::card>
        @endif
        
        {{-- Instructions --}}
        <x-filament::card>
            <h3 class="text-lg font-medium mb-4">Konfigurationsanleitung</h3>
            
            <div class="prose prose-sm max-w-none">
                <ol>
                    <li>
                        <strong>Webhook URL kopieren:</strong> Klicken Sie auf das Kopier-Symbol neben der Webhook URL
                    </li>
                    <li>
                        <strong>In Retell.ai eintragen:</strong> Gehen Sie zu Ihrem Retell.ai Dashboard → Agent Settings → Webhook
                    </li>
                    <li>
                        <strong>Secret konfigurieren:</strong> Kopieren Sie das Webhook Secret und tragen Sie es in Retell.ai ein
                    </li>
                    <li>
                        <strong>Events auswählen:</strong> Aktivieren Sie die gewünschten Events (empfohlen: call_ended)
                    </li>
                    <li>
                        <strong>Custom Functions:</strong> Klicken Sie auf "Zu Retell.ai deployen" um die Functions zu übertragen
                    </li>
                    <li>
                        <strong>Testen:</strong> Nutzen Sie "Webhook testen" um die Verbindung zu prüfen
                    </li>
                </ol>
                
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>Hinweis:</strong> Nach Änderungen müssen Sie die Konfiguration in Retell.ai manuell aktualisieren. 
                        Die automatische Synchronisation über "Zu Retell.ai deployen" funktioniert nur für Custom Functions.
                    </p>
                </div>
            </div>
        </x-filament::card>
    </div>
    
    {{-- Agent Prompt Modal --}}
    <x-filament::modal id="agent-prompt-modal" width="3xl">
        <x-slot name="heading">
            Agent Prompt Template
        </x-slot>
        
        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Prompt Template</h4>
                <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm whitespace-pre-wrap" 
                     x-data="{ prompt: '' }"
                     x-init="
                        $wire.on('open-modal', (event) => {
                            if (event.id === 'agent-prompt-modal') {
                                prompt = event.data.prompt || '';
                            }
                        });
                     "
                     x-text="prompt">
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Verfügbare Variablen</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <dl class="space-y-2 text-sm" 
                        x-data="{ variables: {} }"
                        x-init="
                           $wire.on('open-modal', (event) => {
                               if (event.id === 'agent-prompt-modal') {
                                   variables = event.data.variables || {};
                               }
                           });
                        ">
                        <template x-for="(value, key) in variables" :key="key">
                            <div class="flex">
                                <dt class="font-medium text-gray-700 w-1/3" x-text="`{${key}}:`"></dt>
                                <dd class="text-gray-600 w-2/3" x-text="value"></dd>
                            </div>
                        </template>
                    </dl>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-amber-50 rounded-lg">
                <p class="text-sm text-amber-800">
                    <strong>Verwendung:</strong> Kopieren Sie dieses Template und fügen Sie es in die Agent-Einstellungen in Retell.ai ein. 
                    Die Variablen werden automatisch durch die tatsächlichen Werte ersetzt.
                </p>
            </div>
        </div>
    </x-filament::modal>
</x-filament-panels::page>