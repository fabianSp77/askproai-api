<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold">Company Integration Portal</h1>
            <p class="text-gray-600 mt-1">Wählen Sie ein Unternehmen aus, um die Integrationen zu verwalten.</p>
        </div>

        {{-- Company Selection Grid --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
            @foreach($companies as $company)
                <div style="padding: 1.5rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; background: {{ $selectedCompanyId == $company['id'] ? '#fef3c7' : 'white' }}; cursor: pointer; transition: all 0.2s;"
                     wire:click="selectCompany({{ $company['id'] }})"
                     onmouseover="this.style.borderColor='#f59e0b'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)';"
                     onmouseout="this.style.borderColor='{{ $selectedCompanyId == $company['id'] ? '#f59e0b' : '#e5e7eb' }}'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">
                        {{ $company['name'] }}
                    </h3>
                    @if(!empty($company['slug']))
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                            {{ $company['slug'] }}
                        </p>
                    @endif
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: #6b7280;">
                        <span>📍 {{ $company['branch_count'] }} {{ $company['branch_count'] == 1 ? 'Filiale' : 'Filialen' }}</span>
                        <span>📞 {{ $company['phone_count'] }} {{ $company['phone_count'] == 1 ? 'Nummer' : 'Nummern' }}</span>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: {{ $company['is_active'] ? '#10b981' : '#6b7280' }};"></span>
                        <span style="font-size: 0.75rem; margin-left: 0.25rem;">{{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if($selectedCompany)
            <div wire:loading.class="opacity-50" wire:target="selectCompany">
                {{-- Integration Status --}}
                <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 1.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        Integration Status für {{ $selectedCompany->name }}
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        {{-- Cal.com Status --}}
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 500;">Cal.com</span>
                                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: {{ $integrationStatus['calcom']['configured'] ?? false ? '#10b981' : '#6b7280' }};"></span>
                            </div>
                            <p style="font-size: 0.875rem; color: #6b7280;">
                                {{ $integrationStatus['calcom']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                        </div>

                        {{-- Retell.ai Status --}}
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 500;">Retell.ai</span>
                                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: {{ $integrationStatus['retell']['configured'] ?? false ? '#10b981' : '#6b7280' }};"></span>
                            </div>
                            <p style="font-size: 0.875rem; color: #6b7280;">
                                {{ $integrationStatus['retell']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                        </div>

                        {{-- Webhooks Status --}}
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 500;">Webhooks</span>
                                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: {{ ($integrationStatus['webhooks']['recent_webhooks'] ?? 0) > 0 ? '#10b981' : '#6b7280' }};"></span>
                            </div>
                            <p style="font-size: 0.875rem; color: #6b7280;">
                                {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} in den letzten 24h
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Configuration Forms --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                    {{-- Cal.com Configuration --}}
                    <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 1.5rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Cal.com Konfiguration</h3>
                        
                        <form wire:submit.prevent="saveCalcomConfig">
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">API Key</label>
                                <input type="text" 
                                       wire:model="calcomApiKey" 
                                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;"
                                       placeholder="cal_live_...">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Team Slug</label>
                                <input type="text" 
                                       wire:model="calcomTeamSlug" 
                                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;"
                                       placeholder="team-name">
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" 
                                        style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                    Speichern
                                </button>
                                @if($integrationStatus['calcom']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testCalcomIntegration" 
                                            style="padding: 0.5rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Retell.ai Configuration --}}
                    <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 1.5rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Retell.ai Konfiguration</h3>
                        
                        <form wire:submit.prevent="saveRetellConfig">
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">API Key</label>
                                <input type="text" 
                                       wire:model="retellApiKey" 
                                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;"
                                       placeholder="key_...">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Agent ID</label>
                                <input type="text" 
                                       wire:model="retellAgentId" 
                                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;"
                                       placeholder="agent_...">
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" 
                                        style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                    Speichern
                                </button>
                                @if($integrationStatus['retell']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testRetellIntegration" 
                                            style="padding: 0.5rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Branches --}}
                @if(count($branches) > 0)
                    <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 1.5rem; margin-top: 1.5rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Filialen</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                            @foreach($branches as $branch)
                                <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <h4 style="font-weight: 500;">{{ $branch['name'] }}</h4>
                                            <p style="font-size: 0.875rem; color: #6b7280;">{{ $branch['city'] ?? '-' }}</p>
                                        </div>
                                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: {{ $branch['is_active'] ? '#10b981' : '#6b7280' }};"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- No Company Selected --}}
            <div style="text-align: center; padding: 3rem; background: #f9fafb; border-radius: 0.5rem;">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">Kein Unternehmen ausgewählt</h3>
                <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Wählen Sie ein Unternehmen aus der Liste oben aus.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>