<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with Documentation Link --}}
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Integration Control Center</h1>
                    <p class="text-gray-600 dark:text-gray-400 max-w-3xl">
                        Verwalten Sie alle Integrationen Ihrer Unternehmen zentral. Überprüfen Sie den Status, führen Sie Tests durch und konfigurieren Sie Verbindungen zu Cal.com, Retell.ai und weiteren Services.
                    </p>
                </div>
                <div class="flex gap-2">
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
            
            {{-- Quick Stats --}}
            @if($selectedCompany)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() }}/5
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Integrationen aktiv</div>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Webhooks (24h)</div>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ count($phoneNumbers) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Telefonnummern</div>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ count($branches) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Filialen</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Company Selector with Better UX --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Unternehmen auswählen</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Wählen Sie das Unternehmen, dessen Integrationen Sie verwalten möchten.</p>
                </div>
                <x-filament::button 
                    wire:click="refreshData" 
                    size="sm"
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Aktualisieren</span>
                    <span wire:loading>Lädt...</span>
                </x-filament::button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($companies as $company)
                    <button
                        wire:click="selectCompany({{ $company['id'] }})"
                        wire:loading.attr="disabled"
                        @class([
                            'relative p-4 rounded-lg border-2 transition-all duration-200 text-left hover:shadow-lg transform hover:-translate-y-0.5',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20 shadow-md' => $selectedCompanyId === $company['id'],
                            'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 bg-white dark:bg-gray-800' => $selectedCompanyId !== $company['id'],
                        ])
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
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
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $company['is_active'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
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
            {{-- Integration Progress Overview --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Integrations-Fortschritt</h3>
                <div class="relative">
                    <div class="overflow-hidden h-4 text-xs flex rounded-full bg-gray-200 dark:bg-gray-700">
                        <div 
                            style="width: {{ (collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() / 5) * 100 }}%"
                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500 transition-all duration-500"
                        ></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mt-2">
                        <span>{{ collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() }} von 5 Integrationen konfiguriert</span>
                        <span>{{ round((collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() / 5) * 100) }}%</span>
                    </div>
                </div>
            </div>

            {{-- Integration Status Cards with Enhanced Information --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Cal.com Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden group hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                                    'bg-green-100 dark:bg-green-900/20 group-hover:bg-green-200 dark:group-hover:bg-green-800/30' => $integrationStatus['calcom']['status'] === 'success',
                                    'bg-yellow-100 dark:bg-yellow-900/20 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800/30' => $integrationStatus['calcom']['status'] === 'warning',
                                    'bg-gray-100 dark:bg-gray-900/20 group-hover:bg-gray-200 dark:group-hover:bg-gray-800/30' => $integrationStatus['calcom']['status'] === 'info',
                                ])>
                                    <x-heroicon-o-calendar class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Cal.com</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kalender & Buchungen</p>
                                </div>
                            </div>
                            <div class="relative">
                                <button 
                                    x-data="{ open: false }"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <x-heroicon-m-question-mark-circle class="w-5 h-5 text-gray-400" />
                                </button>
                                <div 
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 z-10"
                                    style="display: none;"
                                >
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Was ist Cal.com?</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                        Cal.com ist Ihre Kalenderlösung für automatische Terminbuchungen. Kunden können direkt Termine buchen, die in Ihrem Kalender erscheinen.
                                    </p>
                                    <a 
                                        href="https://cal.com" 
                                        target="_blank"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                    >
                                        Mehr erfahren <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            {{-- API Key Configuration --}}
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                                @if($showCalcomApiKeyInput)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Cal.com API Key</label>
                                            <button 
                                                wire:click="toggleCalcomApiKeyInput"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                            >
                                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input 
                                                type="password"
                                                wire:model="calcomApiKey"
                                                placeholder="cal_live_xxxxxxxxxxxxxxxxx"
                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                                            />
                                            <x-filament::button
                                                wire:click="saveCalcomApiKey"
                                                size="sm"
                                                wire:loading.attr="disabled"
                                            >
                                                Speichern
                                            </x-filament::button>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <a href="https://app.cal.com/settings/developer/api-keys" target="_blank" class="text-primary-600 hover:underline">
                                                API Key in Cal.com erstellen →
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @if($integrationStatus['calcom']['api_key'])
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">API Key konfiguriert</span>
                                            @else
                                                <x-heroicon-m-x-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">API Key fehlt</span>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="toggleCalcomApiKeyInput"
                                            class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                        >
                                            <x-heroicon-m-pencil-square class="w-3 h-3" />
                                            {{ $integrationStatus['calcom']['api_key'] ? 'Ändern' : 'Konfigurieren' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Team Slug Configuration --}}
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                                @if($showCalcomTeamSlugInput)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Cal.com Team Slug</label>
                                            <button 
                                                wire:click="toggleCalcomTeamSlugInput"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                            >
                                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input 
                                                type="text"
                                                wire:model="calcomTeamSlug"
                                                placeholder="mein-team"
                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                                            />
                                            <x-filament::button
                                                wire:click="saveCalcomTeamSlug"
                                                size="sm"
                                                wire:loading.attr="disabled"
                                            >
                                                Speichern
                                            </x-filament::button>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Optional: Nur erforderlich, wenn Sie Teams in Cal.com verwenden
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @if($integrationStatus['calcom']['team_slug'])
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Team: {{ $selectedCompany->calcom_team_slug }}</span>
                                            @else
                                                <x-heroicon-m-information-circle class="w-4 h-4 text-blue-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Team Slug (optional)</span>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="toggleCalcomTeamSlugInput"
                                            class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                        >
                                            <x-heroicon-m-pencil-square class="w-3 h-3" />
                                            {{ $integrationStatus['calcom']['team_slug'] ? 'Ändern' : 'Konfigurieren' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            @if($integrationStatus['calcom']['event_types'] > 0)
                                <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-primary-700 dark:text-primary-300">
                                            {{ $integrationStatus['calcom']['event_types'] }} Event-Typen synchronisiert
                                        </span>
                                        <x-heroicon-m-calendar-days class="w-4 h-4 text-primary-500" />
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <x-filament::button 
                                wire:click="testCalcomIntegration" 
                                size="sm"
                                class="w-full"
                                :disabled="!$integrationStatus['calcom']['configured']"
                                wire:loading.attr="disabled"
                            >
                                <x-heroicon-m-play class="w-4 h-4 mr-1" wire:loading.remove wire:target="testCalcomIntegration" />
                                <x-filament::loading-indicator class="w-4 h-4 mr-1" wire:loading wire:target="testCalcomIntegration" />
                                Verbindung testen
                            </x-filament::button>
                            
                            @if($integrationStatus['calcom']['configured'])
                                <div class="grid grid-cols-2 gap-2">
                                    <x-filament::button 
                                        wire:click="syncCalcomEventTypes" 
                                        size="sm"
                                        color="gray"
                                        class="w-full"
                                        wire:loading.attr="disabled"
                                    >
                                        <x-heroicon-m-arrow-path class="w-4 h-4 mr-1" />
                                        <span class="hidden sm:inline">Events</span> Sync
                                    </x-filament::button>
                                    
                                    <x-filament::button 
                                        href="https://app.cal.com/event-types"
                                        tag="a"
                                        target="_blank"
                                        size="sm"
                                        color="gray"
                                        class="w-full"
                                    >
                                        <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 mr-1" />
                                        Dashboard
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($testResults['calcom']))
                            <div class="mt-4 p-3 rounded-lg text-sm {{ $testResults['calcom']['success'] ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' }}">
                                <p class="font-medium flex items-center gap-2">
                                    @if($testResults['calcom']['success'])
                                        <x-heroicon-m-check-circle class="w-4 h-4" />
                                    @else
                                        <x-heroicon-m-x-circle class="w-4 h-4" />
                                    @endif
                                    {{ $testResults['calcom']['message'] }}
                                </p>
                                <p class="text-xs mt-1 opacity-75">{{ $testResults['calcom']['tested_at'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Retell.ai Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden group hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                                    'bg-green-100 dark:bg-green-900/20 group-hover:bg-green-200 dark:group-hover:bg-green-800/30' => $integrationStatus['retell']['status'] === 'success',
                                    'bg-yellow-100 dark:bg-yellow-900/20 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800/30' => $integrationStatus['retell']['status'] === 'warning',
                                    'bg-gray-100 dark:bg-gray-900/20 group-hover:bg-gray-200 dark:group-hover:bg-gray-800/30' => $integrationStatus['retell']['status'] === 'info',
                                ])>
                                    <x-heroicon-o-phone class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Retell.ai</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">KI-Telefonie</p>
                                </div>
                            </div>
                            <div class="relative">
                                <button 
                                    x-data="{ open: false }"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <x-heroicon-m-question-mark-circle class="w-5 h-5 text-gray-400" />
                                </button>
                                <div 
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 z-10"
                                    style="display: none;"
                                >
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Was ist Retell.ai?</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                        Retell.ai ist Ihr KI-Telefonassistent, der Anrufe automatisch entgegennimmt, Kundenfragen beantwortet und Termine vereinbart - 24/7 verfügbar.
                                    </p>
                                    <a 
                                        href="https://retell.ai" 
                                        target="_blank"
                                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                    >
                                        Mehr erfahren <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            {{-- Retell API Key Configuration --}}
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                                @if($showRetellApiKeyInput)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Retell.ai API Key</label>
                                            <button 
                                                wire:click="toggleRetellApiKeyInput"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                            >
                                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input 
                                                type="password"
                                                wire:model="retellApiKey"
                                                placeholder="key_xxxxxxxxxxxxxxxxx"
                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                                            />
                                            <x-filament::button
                                                wire:click="saveRetellApiKey"
                                                size="sm"
                                                wire:loading.attr="disabled"
                                            >
                                                Speichern
                                            </x-filament::button>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <a href="https://dashboard.retell.ai/api-keys" target="_blank" class="text-primary-600 hover:underline">
                                                API Key in Retell.ai erstellen →
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @if($integrationStatus['retell']['api_key'])
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">API Key konfiguriert</span>
                                            @else
                                                <x-heroicon-m-x-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">API Key fehlt</span>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="toggleRetellApiKeyInput"
                                            class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                        >
                                            <x-heroicon-m-pencil-square class="w-3 h-3" />
                                            {{ $integrationStatus['retell']['api_key'] ? 'Ändern' : 'Konfigurieren' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Retell Agent ID Configuration --}}
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                                @if($showRetellAgentIdInput)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Retell.ai Agent ID</label>
                                            <button 
                                                wire:click="toggleRetellAgentIdInput"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                            >
                                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input 
                                                type="text"
                                                wire:model="retellAgentId"
                                                placeholder="agent_xxxxxxxxxxxxxxxxx"
                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500"
                                            />
                                            <x-filament::button
                                                wire:click="saveRetellAgentId"
                                                size="sm"
                                                wire:loading.attr="disabled"
                                            >
                                                Speichern
                                            </x-filament::button>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <a href="https://dashboard.retell.ai/agents" target="_blank" class="text-primary-600 hover:underline">
                                                Agent in Retell.ai erstellen →
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @if($integrationStatus['retell']['agent_id'])
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Agent: {{ substr($selectedCompany->retell_agent_id, 0, 10) }}...</span>
                                            @else
                                                <x-heroicon-m-x-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Agent ID fehlt</span>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="toggleRetellAgentIdInput"
                                            class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                                        >
                                            <x-heroicon-m-pencil-square class="w-3 h-3" />
                                            {{ $integrationStatus['retell']['agent_id'] ? 'Ändern' : 'Konfigurieren' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            @if($integrationStatus['retell']['phone_numbers'] > 0)
                                <div class="p-2 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-primary-700 dark:text-primary-300">
                                            {{ $integrationStatus['retell']['phone_numbers'] }} Telefonnummer{{ $integrationStatus['retell']['phone_numbers'] > 1 ? 'n' : '' }} verbunden
                                        </span>
                                        <x-heroicon-m-phone class="w-4 h-4 text-primary-500" />
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <x-filament::button 
                                wire:click="testRetellIntegration" 
                                size="sm"
                                class="w-full"
                                :disabled="!$integrationStatus['retell']['configured']"
                                wire:loading.attr="disabled"
                            >
                                <x-heroicon-m-play class="w-4 h-4 mr-1" wire:loading.remove wire:target="testRetellIntegration" />
                                <x-filament::loading-indicator class="w-4 h-4 mr-1" wire:loading wire:target="testRetellIntegration" />
                                Verbindung testen
                            </x-filament::button>
                            
                            @if($integrationStatus['retell']['configured'])
                                <div class="grid grid-cols-2 gap-2">
                                    <x-filament::button 
                                        wire:click="importRetellCalls" 
                                        size="sm"
                                        color="gray"
                                        class="w-full"
                                        wire:loading.attr="disabled"
                                    >
                                        <x-heroicon-m-arrow-down-tray class="w-4 h-4 mr-1" />
                                        Anrufe
                                    </x-filament::button>
                                    
                                    <x-filament::button 
                                        href="https://dashboard.retell.ai"
                                        tag="a"
                                        target="_blank"
                                        size="sm"
                                        color="gray"
                                        class="w-full"
                                    >
                                        <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 mr-1" />
                                        Dashboard
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($testResults['retell']))
                            <div class="mt-4 p-3 rounded-lg text-sm {{ $testResults['retell']['success'] ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200' }}">
                                <p class="font-medium flex items-center gap-2">
                                    @if($testResults['retell']['success'])
                                        <x-heroicon-m-check-circle class="w-4 h-4" />
                                    @else
                                        <x-heroicon-m-x-circle class="w-4 h-4" />
                                    @endif
                                    {{ $testResults['retell']['message'] }}
                                </p>
                                <p class="text-xs mt-1 opacity-75">{{ $testResults['retell']['tested_at'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Webhooks Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden group hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                                    'bg-green-100 dark:bg-green-900/20 group-hover:bg-green-200 dark:group-hover:bg-green-800/30' => $integrationStatus['webhooks']['status'] === 'success',
                                    'bg-yellow-100 dark:bg-yellow-900/20 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800/30' => $integrationStatus['webhooks']['status'] === 'warning',
                                ])>
                                    <x-heroicon-o-link class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Webhooks</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Echtzeit-Events</p>
                                </div>
                            </div>
                            <div class="relative">
                                <button 
                                    x-data="{ open: false }"
                                    @click="open = !open"
                                    @click.away="open = false"
                                    class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <x-heroicon-m-question-mark-circle class="w-5 h-5 text-gray-400" />
                                </button>
                                <div 
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 z-10"
                                    style="display: none;"
                                >
                                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Was sind Webhooks?</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                        Webhooks ermöglichen Echtzeit-Kommunikation zwischen Services. Wenn ein Anruf endet oder ein Termin gebucht wird, werden Sie sofort benachrichtigt.
                                    </p>
                                    <div class="space-y-1 text-xs">
                                        <p class="font-medium text-gray-700 dark:text-gray-300">Webhook URLs:</p>
                                        <code class="block bg-gray-100 dark:bg-gray-700 p-1 rounded text-xs break-all">
                                            https://api.askproai.de/api/mcp/retell/webhook
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            @if($integrationStatus['webhooks']['recent_webhooks'] > 0)
                                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                                {{ $integrationStatus['webhooks']['recent_webhooks'] }}
                                            </p>
                                            <p class="text-sm text-green-600 dark:text-green-400">
                                                Webhooks in 24h
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <x-heroicon-m-signal class="w-8 h-8 text-green-500" />
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-yellow-600 flex-shrink-0" />
                                        <div>
                                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                                Keine Webhook-Aktivität
                                            </p>
                                            <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-0.5">
                                                Stellen Sie sicher, dass Webhook URLs korrekt konfiguriert sind.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-2">
                            <x-filament::button 
                                href="{{ route('filament.admin.pages.webhook-monitor') }}"
                                tag="a"
                                size="sm"
                                class="w-full"
                            >
                                <x-heroicon-m-chart-bar class="w-4 h-4 mr-1" />
                                Webhook Monitor
                            </x-filament::button>
                            
                            <x-filament::button 
                                href="{{ route('filament.admin.pages.mcp-dashboard') }}"
                                tag="a"
                                size="sm"
                                color="gray"
                                class="w-full"
                            >
                                <x-heroicon-m-presentation-chart-line class="w-4 h-4 mr-1" />
                                MCP Dashboard
                            </x-filament::button>
                        </div>
                    </div>
                </div>
                
                {{-- Stripe Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden group hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                                    'bg-green-100 dark:bg-green-900/20 group-hover:bg-green-200 dark:group-hover:bg-green-800/30' => $integrationStatus['stripe']['status'] === 'success',
                                    'bg-blue-100 dark:bg-blue-900/20 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/30' => $integrationStatus['stripe']['status'] === 'info',
                                ])>
                                    <x-heroicon-o-credit-card class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Stripe</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Zahlungen</p>
                                </div>
                            </div>
                            <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full">
                                Optional
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $integrationStatus['stripe']['message'] }}
                                </p>
                            </div>
                        </div>
                        
                        @if($integrationStatus['stripe']['configured'])
                            <div class="space-y-2">
                                <x-filament::button 
                                    wire:click="testStripeIntegration" 
                                    size="sm"
                                    class="w-full"
                                >
                                    <x-heroicon-m-play class="w-4 h-4 mr-1" />
                                    Verbindung testen
                                </x-filament::button>
                                
                                <x-filament::button 
                                    href="https://dashboard.stripe.com"
                                    tag="a"
                                    target="_blank"
                                    size="sm"
                                    color="gray"
                                    class="w-full"
                                >
                                    <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 mr-1" />
                                    Stripe Dashboard
                                </x-filament::button>
                            </div>
                        @else
                            <x-filament::button 
                                href="https://dashboard.stripe.com/apikeys"
                                tag="a"
                                target="_blank"
                                size="sm"
                                color="gray"
                                class="w-full"
                            >
                                <x-heroicon-m-plus class="w-4 h-4 mr-1" />
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
                
                {{-- Knowledge Base Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden group hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                                    'bg-green-100 dark:bg-green-900/20 group-hover:bg-green-200 dark:group-hover:bg-green-800/30' => $integrationStatus['knowledge']['status'] === 'success',
                                    'bg-blue-100 dark:bg-blue-900/20 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/30' => $integrationStatus['knowledge']['status'] === 'info',
                                ])>
                                    <x-heroicon-o-book-open class="w-7 h-7 text-gray-700 dark:text-gray-300" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Wissensdatenbank</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">KI-Training</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
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
                                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Trainieren Sie Ihre KI mit unternehmensspezifischem Wissen für bessere Antworten.
                                    </p>
                                </div>
                            @endif
                        </div>
                        
                        <x-filament::button 
                            href="{{ route('filament.admin.pages.knowledge-base') }}"
                            tag="a"
                            size="sm"
                            class="w-full"
                            color="{{ $integrationStatus['knowledge']['configured'] ? 'primary' : 'gray' }}"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4 mr-1" />
                            Wissensdatenbank verwalten
                        </x-filament::button>
                    </div>
                </div>
            </div>
            
            {{-- Quick Actions & Next Steps --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnellaktionen</h2>
                    
                    <div class="grid grid-cols-2 gap-3">
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
                            href="{{ route('filament.admin.pages.mcp-control') }}"
                            tag="a"
                            color="gray"
                            icon="heroicon-m-command-line"
                            class="justify-center"
                        >
                            MCP Control
                        </x-filament::button>
                    </div>
                </div>
                
                {{-- Next Steps / Recommendations --}}
                <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <x-heroicon-m-light-bulb class="w-5 h-5 inline-block mr-2 text-primary-600" />
                        Empfohlene nächste Schritte
                    </h2>
                    
                    <div class="space-y-3">
                        @if(!$integrationStatus['calcom']['configured'])
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-arrow-right class="w-5 h-5 text-primary-600 dark:text-primary-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Cal.com konfigurieren</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        Erstellen Sie einen API Key und verknüpfen Sie Ihr Team für automatische Terminbuchungen.
                                    </p>
                                </div>
                            </div>
                        @endif
                        
                        @if(!$integrationStatus['retell']['configured'])
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-arrow-right class="w-5 h-5 text-primary-600 dark:text-primary-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Retell.ai aktivieren</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        Richten Sie Ihren KI-Telefonassistenten ein und verbinden Sie Ihre Telefonnummern.
                                    </p>
                                </div>
                            </div>
                        @endif
                        
                        @if($integrationStatus['webhooks']['recent_webhooks'] == 0)
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-arrow-right class="w-5 h-5 text-primary-600 dark:text-primary-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Webhooks überprüfen</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        Stellen Sie sicher, dass Webhook URLs in Retell.ai korrekt konfiguriert sind.
                                    </p>
                                </div>
                            </div>
                        @endif
                        
                        @if(collect($integrationStatus)->filter(fn($status) => $status['configured'] ?? false)->count() == 5)
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-check-circle class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Großartig! Alle Integrationen sind aktiv.</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        Überwachen Sie die Performance im MCP Dashboard.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Phone Numbers with Enhanced Display --}}
            @if(count($phoneNumbers) > 0)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Telefonnummern</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Verwalten Sie die Telefonnummern, über die Kunden Ihr Unternehmen erreichen können.
                                </p>
                            </div>
                            <x-filament::button
                                href="{{ route('filament.admin.resources.phone-numbers.index') }}"
                                tag="a"
                                size="sm"
                                color="gray"
                                icon="heroicon-m-pencil-square"
                            >
                                Verwalten
                            </x-filament::button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nummer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filiale</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Primär</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($phoneNumbers as $phone)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <x-heroicon-o-phone class="w-4 h-4 text-gray-400 mr-2" />
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $phone['formatted'] ?? $phone['number'] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $phone['branch'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span @class([
                                                'inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full',
                                                'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' => $phone['is_active'],
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' => !$phone['is_active'],
                                            ])>
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $phone['is_active'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                                {{ $phone['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @if($phone['is_primary'])
                                                <div class="flex items-center">
                                                    <x-heroicon-m-star class="w-4 h-4 text-yellow-500" />
                                                    <span class="ml-1 text-xs">Haupt</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a 
                                                href="tel:{{ $phone['number'] }}"
                                                class="text-primary-600 dark:text-primary-400 hover:underline"
                                            >
                                                Anrufen
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            
            {{-- Branches with Enhanced Cards --}}
            @if(count($branches) > 0)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filialen</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Übersicht aller Standorte und deren Konfigurationsstatus.
                            </p>
                        </div>
                        <x-filament::button
                            href="{{ route('filament.admin.resources.branches.index') }}"
                            tag="a"
                            size="sm"
                            color="gray"
                            icon="heroicon-m-pencil-square"
                        >
                            Alle Filialen
                        </x-filament::button>
                    </div>
                    
                    {{-- Include enhanced branch management --}}
                    @include('filament.admin.pages.company-integration-portal-branches')
                </div>
            @endif
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
    
    {{-- Auto-refresh script (optional) --}}
    @if($this->autoRefresh ?? false)
        <script>
            setInterval(() => {
                @this.call('refreshData');
            }, {{ ($this->refreshInterval ?? 30) * 1000 }});
        </script>
    @endif
</x-filament-panels::page>