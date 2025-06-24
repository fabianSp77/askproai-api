<x-filament-panels::page>
    <style>
        .portal-container { max-width: 100%; }
        .company-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 1rem; 
            margin-bottom: 2rem;
        }
        .company-card {
            padding: 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .company-card:hover {
            border-color: #f59e0b;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .company-card.selected {
            border-color: #f59e0b;
            background: #fef3c7;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.active { background: #10b981; }
        .status-dot.inactive { background: #6b7280; }
        .action-button {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-button:hover {
            transform: translateY(-1px);
        }
        .action-button.primary {
            background: #3b82f6;
            color: white;
        }
        .action-button.primary:hover {
            background: #2563eb;
        }
        .action-button.secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .action-button.secondary:hover {
            background: #e5e7eb;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>

    <div class="portal-container">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Company Integration Portal</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Wählen Sie ein Unternehmen aus, um die Integrationen zu verwalten.</p>
        </div>

        {{-- Company Selection --}}
        <div class="company-grid">
            @foreach($companies as $company)
                <div class="company-card {{ $selectedCompanyId == $company['id'] ? 'selected' : '' }}"
                     wire:click="selectCompany({{ $company['id'] }})">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $company['name'] }}</h3>
                    @if(\!empty($company['slug']))
                        <p class="text-sm text-gray-500 mt-1">{{ $company['slug'] }}</p>
                    @endif
                    <div class="mt-3 flex items-center gap-4 text-sm text-gray-600">
                        <span>📍 {{ $company['branch_count'] }} {{ $company['branch_count'] == 1 ? 'Filiale' : 'Filialen' }}</span>
                        <span>📞 {{ $company['phone_count'] }} {{ $company['phone_count'] == 1 ? 'Nummer' : 'Nummern' }}</span>
                    </div>
                    <div class="mt-3">
                        <span class="status-dot {{ $company['is_active'] ? 'active' : 'inactive' }}"></span>
                        <span class="text-xs ml-1">{{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if($selectedCompany)
            <div wire:loading.class="opacity-50" wire:target="selectCompany">
                {{-- Integration Status Overview --}}
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Integration Status für {{ $selectedCompany->name }}</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {{-- Cal.com --}}
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium">Cal.com</span>
                                <span class="status-dot {{ $integrationStatus['calcom']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ $integrationStatus['calcom']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                        </div>

                        {{-- Retell.ai --}}
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium">Retell.ai</span>
                                <span class="status-dot {{ $integrationStatus['retell']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ $integrationStatus['retell']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                        </div>

                        {{-- Webhooks --}}
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium">Webhooks</span>
                                <span class="status-dot {{ ($integrationStatus['webhooks']['recent_webhooks'] ?? 0) > 0 ? 'active' : 'inactive' }}"></span>
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} in den letzten 24h
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Configuration Forms --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Cal.com Configuration --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Cal.com Konfiguration</h3>
                        
                        <form wire:submit.prevent="saveCalcomConfig">
                            <div class="form-group">
                                <label class="form-label">API Key</label>
                                <input type="text" 
                                       wire:model="calcomApiKey" 
                                       class="form-input"
                                       placeholder="cal_live_...">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Team Slug</label>
                                <input type="text" 
                                       wire:model="calcomTeamSlug" 
                                       class="form-input"
                                       placeholder="team-name">
                            </div>
                            
                            <div class="flex gap-2">
                                <button type="submit" class="action-button primary">
                                    Speichern
                                </button>
                                @if($integrationStatus['calcom']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testCalcomIntegration" 
                                            class="action-button secondary">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Retell.ai Configuration --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Retell.ai Konfiguration</h3>
                        
                        <form wire:submit.prevent="saveRetellConfig">
                            <div class="form-group">
                                <label class="form-label">API Key</label>
                                <input type="text" 
                                       wire:model="retellApiKey" 
                                       class="form-input"
                                       placeholder="key_...">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Agent ID</label>
                                <input type="text" 
                                       wire:model="retellAgentId" 
                                       class="form-input"
                                       placeholder="agent_...">
                            </div>
                            
                            <div class="flex gap-2">
                                <button type="submit" class="action-button primary">
                                    Speichern
                                </button>
                                @if($integrationStatus['retell']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testRetellIntegration" 
                                            class="action-button secondary">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Branches --}}
                @if(count($branches) > 0)
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-semibold mb-4">Filialen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($branches as $branch)
                                <div class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium">{{ $branch['name'] }}</h4>
                                            <p class="text-sm text-gray-600">{{ $branch['city'] ?? '-' }}</p>
                                        </div>
                                        <span class="status-dot {{ $branch['is_active'] ? 'active' : 'inactive' }}"></span>
                                    </div>
                                    @if(\!empty($branch['phone']))
                                        <p class="text-sm text-gray-500 mt-2">📞 {{ $branch['phone'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Phone Numbers --}}
                @if(count($phoneNumbers) > 0)
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-semibold mb-4">Telefonnummern</h3>
                        <div class="space-y-2">
                            @foreach($phoneNumbers as $phone)
                                <div class="flex items-center justify-between p-3 border rounded">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono">{{ $phone['number'] ?? '-' }}</span>
                                        @if($phone['is_primary'] ?? false)
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Primär</span>
                                        @endif
                                    </div>
                                    <span class="status-dot {{ $phone['is_active'] ?? false ? 'active' : 'inactive' }}"></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- No Company Selected --}}
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Kein Unternehmen ausgewählt</h3>
                <p class="mt-1 text-sm text-gray-500">Wählen Sie ein Unternehmen aus der Liste oben aus.</p>
            </div>
        @endif

        {{-- Success/Error Messages --}}
        @if (session()->has('message'))
            <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
                {{ session('message') }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
