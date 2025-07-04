<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Integration Overview Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cal.com Integration Card --}}
            <x-filament::section
                :collapsible="true"
                :collapsed="false"
                icon="heroicon-o-calendar"
            >
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <span class="text-lg font-semibold">Cal.com Integration</span>
                        <x-filament::badge 
                            :color="$integrationStatus['calcom']['connected'] ?? false ? 'success' : 'danger'"
                            size="lg"
                        >
                            {{ $integrationStatus['calcom']['connected'] ?? false ? 'Verbunden' : 'Getrennt' }}
                        </x-filament::badge>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">API Version</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['calcom']['api_version'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Event-Typen</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['calcom']['event_types_count'] ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Letzte Sync</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['calcom']['last_sync'] ?? 'Nie' }}</dd>
                        </div>
                    </dl>
                    
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="syncEventTypes"
                            size="sm"
                            outlined
                        >
                            Event-Typen synchronisieren
                        </x-filament::button>
                        <x-filament::button
                            wire:click="openCalcomSettings"
                            size="sm"
                            color="gray"
                            outlined
                        >
                            Einstellungen
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
            
            {{-- Retell.ai Integration Card --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Retell.ai Integration</span>
                        <x-filament::badge 
                            :color="$integrationStatus['retell']['connected'] ?? false ? 'success' : 'danger'"
                        >
                            {{ $integrationStatus['retell']['connected'] ?? false ? 'Aktiv' : 'Inaktiv' }}
                        </x-filament::badge>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Agenten</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['retell']['agents_count'] ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Telefonnummern</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['retell']['phone_numbers_count'] ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Letzter Anruf</dt>
                            <dd class="text-sm font-medium">{{ $integrationStatus['retell']['last_call'] ?? 'Nie' }}</dd>
                        </div>
                    </dl>
                    
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="refreshRetellAgents"
                            size="sm"
                            outlined
                        >
                            Agenten aktualisieren
                        </x-filament::button>
                        <x-filament::button
                            wire:click="openRetellSettings"
                            size="sm"
                            color="gray"
                            outlined
                        >
                            Konfiguration
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
            
            {{-- Webhook Status Card --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Webhook Status</span>
                        <x-filament::badge color="info">
                            {{ number_format($webhookStats['success_rate'] ?? 0, 1) }}% Erfolg
                        </x-filament::badge>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Heute empfangen</dt>
                            <dd class="text-sm font-medium">{{ $webhookStats['total_today'] ?? 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Erfolgsrate (24h)</dt>
                            <dd class="text-sm font-medium">{{ number_format($webhookStats['success_rate'] ?? 0, 1) }}%</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Letzter Webhook</dt>
                            <dd class="text-sm font-medium">{{ $webhookStats['last_webhook'] ?? 'Nie' }}</dd>
                        </div>
                    </dl>
                    
                    <x-filament::button
                        wire:click="openWebhookMonitor"
                        size="sm"
                        outlined
                        class="w-full"
                    >
                        Webhook Monitor öffnen
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
        
        {{-- API Health Status --}}
        <x-filament::section>
            <x-slot name="heading">
                API Health Status
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['calcom' => 'Cal.com API', 'retell' => 'Retell.ai API', 'internal' => 'Internal API'] as $key => $label)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium">{{ $label }}</h4>
                            <x-filament::badge
                                :color="match($apiHealth[$key]['status'] ?? 'unknown') {
                                    'operational' => 'success',
                                    'degraded' => 'warning',
                                    'error' => 'danger',
                                    default => 'gray'
                                }"
                                size="xs"
                            >
                                {{ ucfirst($apiHealth[$key]['status'] ?? 'unknown') }}
                            </x-filament::badge>
                        </div>
                        
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Response Time</span>
                                <span class="font-mono">{{ $apiHealth[$key]['response_time'] ?? 'N/A' }}ms</span>
                            </div>
                            @if(isset($apiHealth[$key]['uptime']))
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Uptime</span>
                                    <span>{{ $apiHealth[$key]['uptime'] }}</span>
                                </div>
                            @endif
                            @if(isset($apiHealth[$key]['last_check']))
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Letzter Check</span>
                                    <span>{{ $apiHealth[$key]['last_check'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        
        {{-- Synchronization Status --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Event Type Sync Status --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Event-Typ Synchronisation</span>
                        <x-filament::badge
                            :color="$syncStatus['event_types']['status'] === 'active' ? 'success' : 'gray'"
                        >
                            {{ $syncStatus['event_types']['status'] === 'active' ? 'Aktiv' : 'Pausiert' }}
                        </x-filament::badge>
                    </div>
                </x-slot>
                
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Letzte Synchronisation</dt>
                        <dd class="text-sm font-medium">{{ $syncStatus['event_types']['last_sync'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Nächste Synchronisation</dt>
                        <dd class="text-sm font-medium">{{ $syncStatus['event_types']['next_sync'] }}</dd>
                    </div>
                </dl>
            </x-filament::section>
            
            {{-- Staff Assignment Status --}}
            <x-filament::section>
                <x-slot name="heading">
                    Mitarbeiter-Zuordnungen
                </x-slot>
                
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Gesamt-Zuordnungen</dt>
                        <dd class="text-sm font-medium">{{ $syncStatus['staff_assignments']['total'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Aktive Zuordnungen</dt>
                        <dd class="text-sm font-medium">{{ $syncStatus['staff_assignments']['active'] }}</dd>
                    </div>
                </dl>
                
                <div class="mt-4">
                    <x-filament::link
                        href="/admin/event-type-setup-wizard"
                        tag="a"
                        size="sm"
                    >
                        Zuordnungen verwalten →
                    </x-filament::link>
                </div>
            </x-filament::section>
        </div>
        
        {{-- Quick Actions --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Schnellaktionen & Troubleshooting
            </x-slot>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-filament::button
                    href="/admin/event-type-import-wizard"
                    tag="a"
                    outlined
                    size="sm"
                    class="w-full"
                >
                    Event-Typen importieren
                </x-filament::button>
                
                <x-filament::button
                    href="/admin/retell-agent-import-wizard"
                    tag="a"
                    outlined
                    size="sm"
                    class="w-full"
                >
                    Agenten importieren
                </x-filament::button>
                
                <x-filament::button
                    href="/admin/webhook-analysis"
                    tag="a"
                    outlined
                    size="sm"
                    class="w-full"
                >
                    Webhook-Analyse
                </x-filament::button>
                
                <x-filament::button
                    href="/admin/api-health-monitor"
                    tag="a"
                    outlined
                    size="sm"
                    class="w-full"
                >
                    API Health Details
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
    
    {{-- Auto-refresh every 60 seconds --}}
    <div wire:poll.60s="loadIntegrationStatus"></div>
</x-filament-panels::page>