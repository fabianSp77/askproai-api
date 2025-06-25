<x-filament-panels::page>
    {{-- Inline Styles für modernes, helles Design --}}
    <style>
        /* Modern Light Design System */
        :root {
            --modern-primary: #6366f1;
            --modern-secondary: #8b5cf6;
            --modern-success: #10b981;
            --modern-warning: #f59e0b;
            --modern-error: #ef4444;
            --modern-info: #3b82f6;
            
            --modern-gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --modern-gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --modern-gradient-light: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            
            --modern-bg-primary: #ffffff;
            --modern-bg-secondary: #f9fafb;
            --modern-bg-tertiary: #f3f4f6;
            
            --modern-border: #e5e7eb;
            --modern-border-focus: #6366f1;
            
            --modern-text-primary: #111827;
            --modern-text-secondary: #6b7280;
            --modern-text-tertiary: #9ca3af;
            
            --modern-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --modern-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --modern-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --modern-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --modern-transition: all 0.2s ease;
        }
        
        /* Main Container - Light Background */
        .control-center-container {
            background: var(--modern-bg-secondary);
            min-height: calc(100vh - 64px);
            position: relative;
            padding: 1rem;
        }
        
        @media (min-width: 768px) {
            .control-center-container {
                padding: 1.5rem;
            }
        }
        
        /* Modern Card Component */
        .modern-card {
            background: var(--modern-bg-primary);
            border: 1px solid var(--modern-border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: var(--modern-transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--modern-shadow-sm);
        }
        
        /* Tab Navigation - Fixed Styling */
        .modern-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--modern-bg-primary);
            border: 1px solid var(--modern-border);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            box-shadow: var(--modern-shadow-sm);
        }
        
        .modern-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--modern-transition);
            color: var(--modern-text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
            white-space: nowrap;
            background: transparent;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .modern-tab:hover {
            color: var(--modern-text-primary);
            background: var(--modern-bg-tertiary);
        }
        
        .modern-tab.active {
            color: white !important;
            background: var(--modern-gradient-primary) !important;
            box-shadow: var(--modern-shadow-sm);
        }
        
        /* Agent Card Grid */
        .agent-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .agent-card-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Search Input Styles */
        .search-input-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            height: 40px;
            padding: 0 16px 0 44px;
            font-size: 14px;
            color: #111827;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9ca3af;
            pointer-events: none;
            z-index: 10;
        }
    </style>
    
    <div class="control-center-container" 
         x-data="{
             activeTab: @entangle('activeTab').defer,
             searchTerm: '',
             filteredAgents: []
         }"
         x-init="
             // Watch for tab changes from Livewire
             $watch('activeTab', value => {
                 // Update any tab-specific logic here
             });
         ">
        
        {{-- Header Section --}}
        <div class="modern-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: between; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--modern-text-primary); margin: 0;">
                        Retell Control Center
                    </h1>
                    <p style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                        Manage your AI phone agents, webhooks, and integrations
                    </p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button 
                        wire:click="loadMetrics" 
                        style="
                            padding: 0.5rem 1rem;
                            background: var(--modern-bg-tertiary);
                            border: none;
                            border-radius: 0.5rem;
                            color: var(--modern-text-secondary);
                            font-size: 0.875rem;
                            font-weight: 500;
                            cursor: pointer;
                            transition: var(--modern-transition);
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                        "
                        onmouseover="this.style.background='#e5e7eb'"
                        onmouseout="this.style.background='#f3f4f6'">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Tab Navigation - Fixed Implementation --}}
        <div class="modern-tabs">
            <button 
                x-on:click="$wire.changeTab('dashboard')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'dashboard' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Dashboard
            </button>
            
            <button 
                x-on:click="$wire.changeTab('agents')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'agents' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Agents
            </button>
            
            <button 
                x-on:click="$wire.changeTab('functions')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'functions' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                Functions
            </button>
            
            <button 
                x-on:click="$wire.changeTab('webhooks')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'webhooks' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Webhooks
            </button>
            
            <button 
                x-on:click="$wire.changeTab('phones')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'phones' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Phones
            </button>
            
            <button 
                x-on:click="$wire.changeTab('settings')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'settings' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </button>
        </div>
        
        {{-- Content Area --}}
        <div class="tab-content">
            {{-- Dashboard Tab --}}
            <div x-show="activeTab === 'dashboard'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.dashboard')
            </div>
            
            {{-- Agents Tab --}}
            <div x-show="activeTab === 'agents'" x-transition>
                {{-- Search and Filter Bar - Fixed --}}
                <div style="
                    margin-bottom: 1.25rem;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                    background-color: var(--modern-bg-primary);
                    padding: 1rem;
                    border-radius: 0.5rem;
                    border: 1px solid var(--modern-border);
                ">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                        {{-- Search Input - Fixed Implementation --}}
                        <div class="search-input-container">
                            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="agentSearch"
                                placeholder="Search agents by name..."
                                class="search-input"
                                x-model="searchTerm">
                        </div>
                        
                        {{-- Refresh Button --}}
                        <button 
                            wire:click="loadAgents" 
                            style="
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                height: 40px;
                                padding: 0 1rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                border-radius: 0.5rem;
                                background-color: var(--modern-bg-tertiary);
                                color: var(--modern-text-secondary);
                                border: none;
                                cursor: pointer;
                                transition: var(--modern-transition);
                                white-space: nowrap;
                            "
                            onmouseover="this.style.backgroundColor='#e5e7eb'"
                            onmouseout="this.style.backgroundColor='#f3f4f6'">
                            <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                    </div>
                    
                    {{-- Create Agent Button --}}
                    <button 
                        wire:click="openAgentCreator" 
                        style="
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            height: 40px;
                            padding: 0 1.25rem;
                            font-size: 0.875rem;
                            font-weight: 600;
                            border-radius: 0.5rem;
                            background: var(--modern-gradient-primary);
                            color: white;
                            border: none;
                            cursor: pointer;
                            transition: var(--modern-transition);
                            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
                            white-space: nowrap;
                        "
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(99, 102, 241, 0.3)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(99, 102, 241, 0.2)'">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Agent
                    </button>
                </div>
                
                {{-- Agents Grid --}}
                @if(count($agents) > 0)
                    <div class="agent-card-grid">
                        @foreach($agents as $agent)
                            @php
                            $agentData = $agent;
                            $agentData['all_versions'] = [];
                            
                            if (isset($agent['base_name'])) {
                                for ($i = 1; $i <= ($agent['total_versions'] ?? 1); $i++) {
                                    $agentData['all_versions'][] = [
                                        'version' => 'V' . $i,
                                        'is_active' => ($agent['active_version']['version'] ?? '') === 'V' . $i
                                    ];
                                }
                            }
                            @endphp
                            
                            <x-retell-agent-card 
                                :agent="$agentData"
                                :globalState="$globalState ?? []"
                                wire:key="{{ $agent['agent_id'] ?? uniqid() }}"
                            />
                        @endforeach
                    </div>
                @else
                    <div class="modern-card" style="text-align: center; padding: 3rem;">
                        <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--modern-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <h3 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.5rem;">
                            No agents found
                        </h3>
                        <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                            Create your first agent to get started
                        </p>
                    </div>
                @endif
            </div>
            
            {{-- Functions Tab --}}
            <div x-show="activeTab === 'functions'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.functions')
            </div>
            
            {{-- Webhooks Tab --}}
            <div x-show="activeTab === 'webhooks'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.webhooks')
            </div>
            
            {{-- Phones Tab --}}
            <div x-show="activeTab === 'phones'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.phones')
            </div>
            
            {{-- Settings Tab --}}
            <div x-show="activeTab === 'settings'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.settings')
            </div>
        </div>
        
        {{-- Function Builder Modal --}}
        @if($showFunctionBuilder)
            @include('filament.admin.pages.partials.retell-control-center.function-builder')
        @endif
    </div>
</x-filament-panels::page>