<x-filament-panels::page>
    <style>
        /* Override any conflicting styles */
        .company-card-container {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)) !important;
            gap: 1rem !important;
            width: 100% !important;
        }
        
        .company-card {
            width: 100% !important;
            min-width: 300px !important;
            cursor: pointer !important;
            padding: 1.5rem !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 0.5rem !important;
            background: white !important;
            transition: all 0.2s !important;
        }
        
        .company-card:hover {
            border-color: #f59e0b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        
        .company-card.selected {
            border-color: #f59e0b !important;
            background: #fef3c7 !important;
        }
        
        @media (max-width: 640px) {
            .company-card-container {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Company Integration Portal</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Verwalten Sie Ihre Unternehmens-Integrationen</p>
    </div>

    {{-- Company Selection --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4">Unternehmen auswählen</h2>
        <div class="company-card-container">
            @foreach($companies as $company)
                <div class="company-card {{ $selectedCompanyId == $company['id'] ? 'selected' : '' }}"
                     wire:click="selectCompany({{ $company['id'] }})">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $company['name'] }}</h3>
                    @if($company['slug'])
                        <p class="text-sm text-gray-500 mt-1">{{ $company['slug'] }}</p>
                    @endif
                    <div class="mt-3 flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ $company['branch_count'] }} Filialen</span>
                        <span>{{ $company['phone_count'] }} Telefonnummern</span>
                    </div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $company['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($selectedCompany)
        {{-- Integration Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Integration Status</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Cal.com Status --}}
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium">Cal.com</h3>
                        <div class="w-3 h-3 rounded-full {{ $integrationStatus['calcom']['configured'] ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">
                        {{ $integrationStatus['calcom']['message'] }}
                    </p>
                    @if($integrationStatus['calcom']['configured'])
                        <button wire:click="testCalcomIntegration" 
                                class="w-full px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                            Verbindung testen
                        </button>
                    @endif
                </div>

                {{-- Retell.ai Status --}}
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium">Retell.ai</h3>
                        <div class="w-3 h-3 rounded-full {{ $integrationStatus['retell']['configured'] ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">
                        {{ $integrationStatus['retell']['message'] }}
                    </p>
                    @if($integrationStatus['retell']['configured'])
                        <button wire:click="testRetellIntegration" 
                                class="w-full px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                            Verbindung testen
                        </button>
                    @endif
                </div>

                {{-- Webhooks Status --}}
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium">Webhooks</h3>
                        <div class="w-3 h-3 rounded-full {{ $integrationStatus['webhooks']['recent_webhooks'] > 0 ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ $integrationStatus['webhooks']['recent_webhooks'] }} Webhooks in 24h
                    </p>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Schnellaktionen</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium mb-3">Cal.com Konfiguration</h3>
                    <div class="space-y-2">
                        {{ $this->saveCalcomApiKeyAction }}
                        {{ $this->saveCalcomTeamSlugAction }}
                    </div>
                </div>
                
                <div>
                    <h3 class="font-medium mb-3">Retell.ai Konfiguration</h3>
                    <div class="space-y-2">
                        {{ $this->saveRetellApiKeyAction }}
                        {{ $this->saveRetellAgentIdAction }}
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t">
                <h3 class="font-medium mb-3">Service Mapping</h3>
                {{ $this->openServiceMappingModalAction }}
            </div>
        </div>

        {{-- Branches --}}
        @if(count($branches) > 0)
            <div class="mt-6">
                <h2 class="text-lg font-semibold mb-4">Filialen</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($branches as $branch)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium">{{ $branch['name'] }}</h3>
                                    <p class="text-sm text-gray-600">{{ $branch['city'] ?? 'Keine Stadt' }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $branch['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $branch['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        {{-- No Company Selected --}}
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Kein Unternehmen ausgewählt</h3>
            <p class="mt-1 text-sm text-gray-500">Wählen Sie oben ein Unternehmen aus, um fortzufahren.</p>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>