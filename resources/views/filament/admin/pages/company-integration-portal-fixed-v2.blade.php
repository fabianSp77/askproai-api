<x-filament-panels::page>
    {{-- Remove ALL inline styles - let Filament handle the layout --}}
    
    {{-- Header --}}
    <div class="fi-section-header mb-6">
        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            Company Integration Portal
        </h1>
        <p class="fi-header-description mt-2 text-sm text-gray-600 dark:text-gray-400">
            Verwalten Sie Ihre Unternehmens-Integrationen
        </p>
    </div>

    {{-- Company Selection with proper Filament grid --}}
    <div class="fi-section mb-8">
        <h2 class="fi-section-heading text-lg font-semibold mb-4">Unternehmen auswählen</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($companies as $company)
                <button
                    type="button"
                    wire:click="selectCompany({{ $company['id'] }})"
                    wire:key="company-{{ $company['id'] }}"
                    @class([
                        'fi-input-wrapper relative rounded-lg border p-4 transition hover:bg-gray-50 dark:hover:bg-white/5',
                        'ring-2 ring-primary-600 border-primary-600' => $selectedCompanyId == $company['id'],
                        'border-gray-300 dark:border-gray-700' => $selectedCompanyId != $company['id'],
                    ])
                >
                    <div class="space-y-1">
                        <h3 class="font-semibold text-gray-950 dark:text-white">
                            {{ $company['name'] }}
                        </h3>
                        @if($company['slug'])
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $company['slug'] }}
                            </p>
                        @endif
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span>{{ $company['branch_count'] }} Filialen</span>
                            <span>{{ $company['phone_count'] }} Tel.</span>
                        </div>
                        @if($company['is_active'])
                            <x-filament::badge color="success" size="sm">
                                Aktiv
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="gray" size="sm">
                                Inaktiv
                            </x-filament::badge>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    @if($selectedCompany)
        {{-- Integration Status using Filament's card component --}}
        <div class="fi-section">
            <h2 class="fi-section-heading text-lg font-semibold mb-4">Integration Status</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Cal.com Status Card --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar class="h-5 w-5" />
                            Cal.com
                        </div>
                    </x-slot>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span>Status:</span>
                            @if($integrationStatus['calcom']['configured'])
                                <x-filament::badge color="success">Konfiguriert</x-filament::badge>
                            @else
                                <x-filament::badge color="warning">Nicht konfiguriert</x-filament::badge>
                            @endif
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $integrationStatus['calcom']['message'] }}
                        </p>
                        
                        @if($integrationStatus['calcom']['configured'])
                            <x-filament::button
                                wire:click="testCalcomIntegration"
                                size="sm"
                                class="w-full"
                            >
                                Verbindung testen
                            </x-filament::button>
                        @else
                            <x-filament-actions::action
                                :action="$this->saveCalcomApiKeyAction"
                                size="sm"
                                class="w-full"
                            />
                        @endif
                    </div>
                </x-filament::section>

                {{-- Retell.ai Status Card --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-phone class="h-5 w-5" />
                            Retell.ai
                        </div>
                    </x-slot>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span>Status:</span>
                            @if($integrationStatus['retell']['configured'])
                                <x-filament::badge color="success">Konfiguriert</x-filament::badge>
                            @else
                                <x-filament::badge color="warning">Nicht konfiguriert</x-filament::badge>
                            @endif
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $integrationStatus['retell']['message'] }}
                        </p>
                        
                        @if($integrationStatus['retell']['configured'])
                            <x-filament::button
                                wire:click="testRetellIntegration"
                                size="sm"
                                class="w-full"
                            >
                                Verbindung testen
                            </x-filament::button>
                        @else
                            <x-filament-actions::action
                                :action="$this->saveRetellApiKeyAction"
                                size="sm"
                                class="w-full"
                            />
                        @endif
                    </div>
                </x-filament::section>

                {{-- Webhooks Status Card --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-link class="h-5 w-5" />
                            Webhooks
                        </div>
                    </x-slot>
                    
                    <div class="space-y-3">
                        <div class="text-center py-4">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $integrationStatus['webhooks']['recent_webhooks'] }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Webhooks in 24h
                            </p>
                        </div>
                        
                        @if($integrationStatus['webhooks']['recent_webhooks'] == 0)
                            <x-filament::badge color="warning" class="w-full justify-center">
                                Keine Aktivität
                            </x-filament::badge>
                        @endif
                    </div>
                </x-filament::section>
            </div>
        </div>

        {{-- Quick Actions using proper Filament components --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Schnellaktionen
            </x-slot>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-medium mb-3">Cal.com Konfiguration</h3>
                    <div class="space-y-2">
                        <x-filament-actions::action
                            :action="$this->saveCalcomApiKeyAction"
                            class="w-full"
                        />
                        <x-filament-actions::action
                            :action="$this->saveCalcomTeamSlugAction"
                            class="w-full"
                        />
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium mb-3">Retell.ai Konfiguration</h3>
                    <div class="space-y-2">
                        <x-filament-actions::action
                            :action="$this->saveRetellApiKeyAction"
                            class="w-full"
                        />
                        <x-filament-actions::action
                            :action="$this->saveRetellAgentIdAction"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium mb-3">Service Mapping</h3>
                <x-filament-actions::action
                    :action="$this->openServiceMappingModalAction"
                />
            </div>
        </x-filament::section>

        {{-- Branches Section --}}
        @if(count($branches) > 0)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Filialen
                </x-slot>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($branches as $branch)
                        <div class="fi-input-wrapper rounded-lg border border-gray-300 dark:border-gray-700 p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium">{{ $branch['name'] }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $branch['address'] ?? 'Keine Adresse' }}
                                    </p>
                                </div>
                                @if($branch['is_active'])
                                    <x-filament::badge color="success" size="sm">
                                        Aktiv
                                    </x-filament::badge>
                                @else
                                    <x-filament::badge color="gray" size="sm">
                                        Inaktiv
                                    </x-filament::badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @else
        {{-- No Company Selected --}}
        <x-filament::section class="text-center py-12">
            <x-heroicon-o-cursor-arrow-rays class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                Kein Unternehmen ausgewählt
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Wählen Sie oben ein Unternehmen aus, um fortzufahren.
            </p>
        </x-filament::section>
    @endif

    {{-- Ensure modals are rendered --}}
    <x-filament-actions::modals />
</x-filament-panels::page>