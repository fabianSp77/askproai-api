<x-filament-panels::page class="fi-company-integration-portal">
    {{-- Header Section --}}
    <div class="mb-6">
        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Integration Control Center</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-3xl">
                        Verwalten Sie alle Integrationen Ihrer Unternehmen zentral.
                    </p>
                </div>
                <x-filament::button
                    href="https://docs.askproai.de/integrations"
                    tag="a"
                    target="_blank"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-book-open"
                >
                    Dokumentation
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- Company Selector Section --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Unternehmen auswählen</span>
                <x-filament::button 
                    wire:click="refreshData" 
                    size="sm"
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    wire:loading.attr="disabled"
                >
                    Aktualisieren
                </x-filament::button>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($companies as $company)
                <x-filament::card
                    tag="button"
                    wire:click="selectCompany({{ $company['id'] }})"
                    wire:loading.attr="disabled"
                    class="relative cursor-pointer transition hover:shadow-lg hover:-translate-y-0.5 {{ $selectedCompanyId === $company['id'] ? 'ring-2 ring-primary-500' : '' }}"
                >
                    <div class="space-y-3">
                        <div>
                            <h3 class="company-name font-semibold text-gray-900 dark:text-white">
                                {{ $company['name'] }}
                            </h3>
                            @if($company['slug'])
                                <p class="company-slug text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company['slug'] }}
                                </p>
                            @endif
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <x-filament::badge 
                                :color="$company['is_active'] ? 'success' : 'gray'"
                                size="sm"
                            >
                                {{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                            </x-filament::badge>
                            
                            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-m-building-office-2 class="w-4 h-4" />
                                    {{ $company['branch_count'] }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-heroicon-m-phone class="w-4 h-4" />
                                    {{ $company['phone_count'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($selectedCompanyId === $company['id'])
                        <div class="absolute top-2 right-2">
                            <x-heroicon-m-check-circle class="w-5 h-5 text-primary-500" />
                        </div>
                    @endif
                </x-filament::card>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <x-heroicon-o-building-office class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Keine Unternehmen gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Legen Sie ein neues Unternehmen an, um zu beginnen.</p>
                        <div class="mt-6">
                            <x-filament::button
                                href="{{ route('filament.admin.resources.companies.create') }}"
                                tag="a"
                            >
                                Unternehmen anlegen
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </x-filament::section>

    @if($selectedCompany)
        {{-- Progress Overview --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Integrations-Fortschritt
            </x-slot>
            
            @php
                $configuredCount = collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count();
                $totalIntegrations = 5;
                $progressPercentage = $totalIntegrations > 0 ? ($configuredCount / $totalIntegrations) * 100 : 0;
            @endphp
            
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span>{{ $configuredCount }} von {{ $totalIntegrations }} Integrationen aktiv</span>
                    <span>{{ round($progressPercentage) }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: {{ $progressPercentage }}%"></div>
                </div>
            </div>
        </x-filament::section>

        {{-- Integration Status Cards --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Cal.com Card --}}
            <x-filament::card class="integration-card">
                <div class="integration-card-content">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-900/20 flex items-center justify-center">
                                    <x-heroicon-o-calendar class="w-7 h-7 text-amber-700 dark:text-amber-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Cal.com</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kalender & Buchungen</p>
                                </div>
                            </div>
                            <span class="status-dot {{ $integrationStatus['calcom']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $integrationStatus['calcom']['message'] ?? 'Nicht konfiguriert' }}
                        </p>
                    </div>
                    
                    @if($integrationStatus['calcom']['configured'] ?? false)
                        <x-filament::button 
                            wire:click="testCalcomIntegration" 
                            size="sm"
                            class="w-full mt-4"
                        >
                            Verbindung testen
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::card>

            {{-- Retell.ai Card --}}
            <x-filament::card class="integration-card">
                <div class="integration-card-content">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                    <x-heroicon-o-phone class="w-7 h-7 text-blue-700 dark:text-blue-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Retell.ai</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">KI-Telefon Agent</p>
                                </div>
                            </div>
                            <span class="status-dot {{ $integrationStatus['retell']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $integrationStatus['retell']['message'] ?? 'Nicht konfiguriert' }}
                        </p>
                    </div>
                    
                    @if($integrationStatus['retell']['configured'] ?? false)
                        <x-filament::button 
                            wire:click="testRetellIntegration" 
                            size="sm"
                            class="w-full mt-4"
                        >
                            Verbindung testen
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::card>

            {{-- Webhooks Card --}}
            <x-filament::card class="integration-card">
                <div class="integration-card-content">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                    <x-heroicon-o-bolt class="w-7 h-7 text-purple-700 dark:text-purple-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Webhooks</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Ereignisse & API</p>
                                </div>
                            </div>
                            <span class="status-dot {{ $integrationStatus['webhooks']['recent_webhooks'] > 0 ? 'active' : 'inactive' }}"></span>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} Webhooks in 24h
                        </p>
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Branches Section --}}
        @if(count($branches) > 0)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Filialen verwalten
                </x-slot>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach($branches as $branch)
                        <x-filament::card>
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $branch['name'] }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $branch['city'] ?? '' }}</p>
                                </div>
                                <x-filament::badge 
                                    :color="$branch['is_active'] ? 'success' : 'gray'"
                                    size="sm"
                                >
                                    {{ $branch['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                </x-filament::badge>
                            </div>
                            
                            <div class="mt-4">
                                <x-filament::button
                                    wire:click="manageBranchEventTypes({{ $branch['id'] }})"
                                    size="sm"
                                    class="event-type-button w-full"
                                >
                                    Event Types verwalten
                                </x-filament::button>
                            </div>
                        </x-filament::card>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Phone Numbers Section --}}
        @if(count($phoneNumbers) > 0)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Telefonnummern & Agent-Zuordnung
                </x-slot>
                
                @include('filament.admin.pages.company-integration-portal-phone-agents')
            </x-filament::section>
        @endif
    @else
        {{-- Empty State when no company selected --}}
        <div class="mt-6 text-center py-12">
            <x-heroicon-o-cursor-arrow-rays class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Kein Unternehmen ausgewählt</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Wählen Sie oben ein Unternehmen aus, um dessen Integrationen zu verwalten.</p>
        </div>
    @endif
    
    {{-- Include clean CSS --}}
    @push('styles')
        @vite(['resources/css/filament/admin/company-integration-portal-clean.css'])
    @endpush
</x-filament-panels::page>