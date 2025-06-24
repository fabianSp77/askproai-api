<x-filament-panels::page class="fi-company-integration-portal">
    <div class="space-y-6">
        {{-- Header Section - Premium Design --}}
        <div class="integration-header glass-card">
            <div class="flex items-start justify-between">
                <div>
                    <h1>Integration Control Center</h1>
                    <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
                        Verwalten Sie alle Integrationen Ihrer Unternehmen zentral. Überprüfen Sie den Status, führen Sie Tests durch und konfigurieren Sie Verbindungen.
                    </p>
                </div>
                <a href="https://docs.askproai.de/integrations" 
                   target="_blank"
                   class="btn-premium btn-premium-secondary"
                   data-tooltip="Zur Dokumentation">
                    <x-heroicon-m-book-open class="w-4 h-4" />
                    Dokumentation
                </a>
            </div>
            
            {{-- Quick Stats --}}
            @if($selectedCompany)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 quick-stats-grid">
                    <div class="stat-card-modern glass-card">
                        <div class="stat-number">
                            {{ collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() }}/5
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Integrationen aktiv</div>
                    </div>
                    <div class="stat-card-modern glass-card">
                        <div class="stat-number">
                            {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Webhooks (24h)</div>
                    </div>
                    <div class="stat-card-modern glass-card">
                        <div class="stat-number">
                            {{ count($phoneNumbers) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Telefonnummern</div>
                    </div>
                    <div class="stat-card-modern glass-card">
                        <div class="stat-number">
                            {{ count($branches) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Filialen</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Company Selector - Modern Cards --}}
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Unternehmen auswählen</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Wählen Sie das Unternehmen, dessen Integrationen Sie verwalten möchten.</p>
                </div>
                <button wire:click="refreshData" 
                        class="btn-premium btn-premium-secondary"
                        wire:loading.attr="disabled"
                        data-tooltip="Daten neu laden">
                    <x-heroicon-m-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" />
                    <span wire:loading.remove>Aktualisieren</span>
                    <span wire:loading>Lädt...</span>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($companies as $company)
                    <button
                        wire:click="selectCompany({{ $company['id'] }})"
                        wire:loading.attr="disabled"
                        @class([
                            'company-card-modern clickable-element',
                            'selected' => $selectedCompanyId === $company['id'],
                        ])
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1 text-left">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $company['name'] }}</h3>
                                @if($company['slug'])
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $company['slug'] }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-2">
                                    <span @class([
                                        'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
                                        'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' => $company['is_active'],
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' => !$company['is_active'],
                                    ])>
                                        {{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="flex items-center justify-end gap-1 text-gray-500 dark:text-gray-400">
                                    <x-heroicon-m-building-office-2 class="w-4 h-4" />
                                    <span>{{ $company['branch_count'] }}</span>
                                </div>
                                <div class="flex items-center justify-end gap-1 text-gray-500 dark:text-gray-400 mt-1">
                                    <x-heroicon-m-phone class="w-4 h-4" />
                                    <span>{{ $company['phone_count'] }}</span>
                                </div>
                            </div>
                        </div>
                        @if($selectedCompanyId === $company['id'])
                            <div class="absolute top-2 right-2">
                                <x-heroicon-m-check-circle class="w-5 h-5 text-primary-500" />
                            </div>
                        @endif
                    </button>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-building-office class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                        <p>Keine Unternehmen gefunden.</p>
                        <x-filament::button 
                            href="{{ route('filament.admin.resources.companies.create') }}"
                            tag="a"
                            class="mt-3"
                            size="sm"
                        >
                            Unternehmen anlegen
                        </x-filament::button>
                    </div>
                @endforelse
            </div>
        </div>
        
        @if($selectedCompany)
            {{-- Integration Status Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Cal.com Card - Premium Design --}}
                <div class="integration-card-premium">
                    <div class="p-6">
                        <div class="integration-card-header">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'integration-icon-container',
                                    'success' => $integrationStatus['calcom']['status'] === 'success',
                                    'warning' => $integrationStatus['calcom']['status'] === 'warning',
                                ])>
                                    <x-heroicon-o-calendar class="integration-icon" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Cal.com</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kalender & Buchungen</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">API Key</span>
                                @if($integrationStatus['calcom']['api_key'])
                                    <span class="status-dot active"></span>
                                @else
                                    <span class="status-dot"></span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Team Slug</span>
                                @if($integrationStatus['calcom']['team_slug'])
                                    <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                @else
                                    <x-heroicon-m-information-circle class="w-4 h-4 text-blue-500" />
                                @endif
                            </div>
                            @if($integrationStatus['calcom']['event_types'] > 0)
                                <div class="p-2 rounded bg-primary-50 dark:bg-primary-900/20">
                                    <span class="text-sm text-primary-700 dark:text-primary-300">
                                        {{ $integrationStatus['calcom']['event_types'] }} Event-Typen
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex gap-2">
                                {{ $this->saveCalcomApiKeyAction }}
                                @if($integrationStatus['calcom']['api_key'])
                                    {{ $this->saveCalcomTeamSlugAction }}
                                @endif
                            </div>
                            
                            @if($integrationStatus['calcom']['configured'])
                                <button wire:click="testCalcomIntegration" 
                                        class="btn-premium btn-premium-primary w-full clickable-element">
                                    <x-heroicon-m-play class="w-4 h-4" />
                                    Verbindung testen
                                </button>
                                
                                <button wire:click="syncCalcomEventTypes" 
                                        class="btn-premium btn-premium-secondary w-full clickable-element">
                                    <x-heroicon-m-arrow-path class="w-4 h-4" />
                                    Events synchronisieren
                                </button>
                            @endif
                        </div>
                        
                        @if(isset($testResults['calcom']))
                            <div class="mt-4 p-3 rounded-lg text-sm {{ $testResults['calcom']['success'] ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' }}">
                                <p class="font-medium">{{ $testResults['calcom']['message'] }}</p>
                                <p class="text-xs mt-1 opacity-75">{{ $testResults['calcom']['tested_at'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Retell.ai Card - Premium Design --}}
                <div class="integration-card-premium">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center',
                                    'bg-green-100 dark:bg-green-900/20' => $integrationStatus['retell']['status'] === 'success',
                                    'bg-yellow-100 dark:bg-yellow-900/20' => $integrationStatus['retell']['status'] === 'warning',
                                ])>
                                    <x-heroicon-o-phone class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Retell.ai</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">KI-Telefonie</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">API Key</span>
                                @if($integrationStatus['retell']['api_key'])
                                    <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                @else
                                    <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Agent ID</span>
                                @if($integrationStatus['retell']['agent_id'])
                                    <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                @else
                                    <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                @endif
                            </div>
                            @if($integrationStatus['retell']['phone_numbers'] > 0)
                                <div class="p-2 rounded bg-primary-50 dark:bg-primary-900/20">
                                    <span class="text-sm text-primary-700 dark:text-primary-300">
                                        {{ $integrationStatus['retell']['phone_numbers'] }} Telefonnummern
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex gap-2">
                                {{ $this->saveRetellApiKeyAction }}
                                @if($integrationStatus['retell']['api_key'])
                                    {{ $this->saveRetellAgentIdAction }}
                                @endif
                            </div>
                            
                            @if($integrationStatus['retell']['configured'])
                                <x-filament::button 
                                    wire:click="testRetellIntegration" 
                                    size="sm"
                                    class="w-full"
                                    icon="heroicon-m-play"
                                >
                                    Verbindung testen
                                </x-filament::button>
                                
                                <x-filament::button 
                                    wire:click="importRetellCalls" 
                                    size="sm"
                                    color="gray"
                                    class="w-full"
                                    icon="heroicon-m-arrow-down-tray"
                                >
                                    Anrufe importieren
                                </x-filament::button>
                            @endif
                        </div>
                        
                        @if(isset($testResults['retell']))
                            <div class="mt-4 p-3 rounded-lg text-sm {{ $testResults['retell']['success'] ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' }}">
                                <p class="font-medium">{{ $testResults['retell']['message'] }}</p>
                                <p class="text-xs mt-1 opacity-75">{{ $testResults['retell']['tested_at'] }}</p>
                            </div>
                        @endif
                        
                        {{-- Webhook Info --}}
                        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-1">Webhook URL:</p>
                            <code class="text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded block break-all">
                                https://api.askproai.de/api/mcp/webhook/retell
                            </code>
                        </div>
                    </div>
                </div>
                
                {{-- Webhooks Card - Premium Design --}}
                <div class="integration-card-premium">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center',
                                    'bg-green-100 dark:bg-green-900/20' => $integrationStatus['webhooks']['status'] === 'success',
                                    'bg-yellow-100 dark:bg-yellow-900/20' => $integrationStatus['webhooks']['status'] === 'warning',
                                ])>
                                    <x-heroicon-o-link class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Webhooks</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Echtzeit-Events</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            @if($integrationStatus['webhooks']['recent_webhooks'] > 0)
                                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                        {{ $integrationStatus['webhooks']['recent_webhooks'] }}
                                    </p>
                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Webhooks in 24h
                                    </p>
                                </div>
                            @else
                                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        Keine Webhook-Aktivität
                                    </p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-0.5">
                                        Stellen Sie sicher, dass Webhooks konfiguriert sind.
                                    </p>
                                </div>
                            @endif
                        </div>
                        
                        <x-filament::button 
                            href="{{ route('filament.admin.pages.webhook-monitor') }}"
                            tag="a"
                            size="sm"
                            class="w-full"
                            icon="heroicon-m-chart-bar"
                        >
                            Webhook Monitor
                        </x-filament::button>
                    </div>
                </div>
                
                {{-- Stripe Card - Premium Design --}}
                <div class="integration-card-premium">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-blue-100 dark:bg-blue-900/20">
                                    <x-heroicon-o-credit-card class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Stripe</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Zahlungen</p>
                                </div>
                            </div>
                            <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full">
                                Optional
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $integrationStatus['stripe']['message'] }}
                            </p>
                        </div>
                        
                        @if($integrationStatus['stripe']['configured'])
                            <x-filament::button 
                                wire:click="testStripeIntegration" 
                                size="sm"
                                class="w-full"
                                icon="heroicon-m-play"
                            >
                                Verbindung testen
                            </x-filament::button>
                        @else
                            <x-filament::button 
                                href="https://dashboard.stripe.com/apikeys"
                                tag="a"
                                target="_blank"
                                size="sm"
                                color="gray"
                                class="w-full"
                                icon="heroicon-m-plus"
                            >
                                Stripe verbinden
                            </x-filament::button>
                        @endif
                        
                        @if(isset($testResults['stripe']))
                            <div class="mt-4 p-3 rounded-lg text-sm {{ $testResults['stripe']['success'] ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' }}">
                                <p class="font-medium">{{ $testResults['stripe']['message'] }}</p>
                                <p class="text-xs mt-1 opacity-75">{{ $testResults['stripe']['tested_at'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Knowledge Base Card - Premium Design --}}
                <div class="integration-card-premium">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-blue-100 dark:bg-blue-900/20">
                                    <x-heroicon-o-book-open class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Wissensdatenbank</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">KI-Training</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            @if($integrationStatus['knowledge']['document_count'] > 0)
                                <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                                    <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                                        {{ $integrationStatus['knowledge']['document_count'] }}
                                    </p>
                                    <p class="text-sm text-primary-600 dark:text-primary-400">
                                        Dokumente
                                    </p>
                                </div>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Trainieren Sie Ihre KI mit unternehmensspezifischem Wissen.
                                </p>
                            @endif
                        </div>
                        
                        <x-filament::button 
                            href="{{ route('filament.admin.pages.knowledge-base') }}"
                            tag="a"
                            size="sm"
                            class="w-full"
                            icon="heroicon-m-pencil-square"
                        >
                            Wissensdatenbank verwalten
                        </x-filament::button>
                    </div>
                </div>
            </div>
            
            {{-- Quick Actions - Modern Grid --}}
            <div class="glass-card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnellaktionen</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <x-filament::button 
                        wire:click="testAllIntegrations" 
                        icon="heroicon-m-play"
                        :loading="$isTestingAll"
                        class="justify-center"
                    >
                        Alle testen
                    </x-filament::button>
                    
                    <x-filament::button 
                        wire:click="openSetupWizard" 
                        color="gray"
                        icon="heroicon-m-cog"
                        class="justify-center"
                    >
                        Setup Wizard
                    </x-filament::button>
                    
                    <x-filament::button 
                        href="{{ route('filament.admin.pages.event-type-setup-wizard') }}"
                        tag="a"
                        color="gray"
                        icon="heroicon-m-calendar"
                        class="justify-center"
                    >
                        Events Setup
                    </x-filament::button>
                    
                    <x-filament::button 
                        wire:click="syncRetellAgents"
                        color="gray"
                        icon="heroicon-m-arrow-path"
                        class="justify-center"
                    >
                        Agents Sync
                    </x-filament::button>
                </div>
            </div>
            
            {{-- Service Mappings - Modern Layout --}}
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Service-EventType Zuordnung
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Verknüpfen Sie Services mit Cal.com Event Types
                            </p>
                        </div>
                        {{ $this->openServiceMappingModalAction }}
                    </div>
                </div>
                
                <div class="p-6">
                    @if(count($serviceMappings ?? []) > 0)
                        <div class="space-y-4">
                            @foreach($serviceMappings as $mapping)
                                <div class="service-mapping-card flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $mapping->service_name }} → {{ $mapping->event_type_name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            @if($mapping->branch_name)
                                                Filiale: {{ $mapping->branch_name }}
                                            @else
                                                Alle Filialen
                                            @endif
                                        </div>
                                    </div>
                                    <x-filament::button
                                        wire:click="removeServiceMapping({{ $mapping->id }})"
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-m-trash"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-link class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">
                                Noch keine Service-EventType Zuordnungen vorhanden.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-12 text-center">
                <x-heroicon-o-building-office class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kein Unternehmen ausgewählt</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Wählen Sie oben ein Unternehmen aus, um dessen Integrationsstatus zu sehen und Verbindungen zu verwalten.
                </p>
            </div>
        @endif
    </div>
    
    {{-- Filament Actions Modals --}}
    <x-filament-actions::modals />
    
    @push('styles')
    <style>
        .fi-company-integration-portal {
            --tw-prose-body: theme('colors.gray.600');
            --tw-prose-headings: theme('colors.gray.900');
        }
        
        .dark .fi-company-integration-portal {
            --tw-prose-body: theme('colors.gray.400');
            --tw-prose-headings: theme('colors.gray.100');
        }
    </style>
    @endpush
</x-filament-panels::page>