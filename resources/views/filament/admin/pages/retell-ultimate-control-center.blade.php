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
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .modern-tabs::-webkit-scrollbar {
            height: 4px;
        }
        
        .modern-tabs::-webkit-scrollbar-track {
            background: var(--modern-bg-tertiary);
            border-radius: 2px;
        }
        
        .modern-tabs::-webkit-scrollbar-thumb {
            background: var(--modern-border);
            border-radius: 2px;
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
            outline: none;
            font-family: inherit;
            line-height: 1.5;
            min-height: 36px;
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
            font-family: inherit;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .search-input::placeholder {
            color: #9ca3af;
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
        
        /* Tab content transitions */
        [x-show] {
            transition: opacity 0.15s ease-in-out;
        }
        
        /* Buttons */
        .modern-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            padding: 0 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: var(--modern-transition);
            outline: none;
            font-family: inherit;
            line-height: 1.5;
            white-space: nowrap;
        }
        
        .modern-btn-primary {
            background: var(--modern-gradient-primary);
            color: white;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            font-weight: 600;
        }
        
        .modern-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }
        
        .modern-btn-secondary {
            background: var(--modern-bg-tertiary);
            color: var(--modern-text-secondary);
        }
        
        .modern-btn-secondary:hover {
            background: #e5e7eb;
        }
        
        /* Loading states */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    <div class="control-center-container" 
         x-data="{
             activeTab: @entangle('activeTab').live,
             searchTerm: @entangle('agentSearch').live,
             init() {
                 // Log initial state
                 console.log('Alpine init - activeTab:', this.activeTab);
                 
                 // Force activeTab to dashboard if not set
                 if (!this.activeTab) {
                     this.activeTab = 'dashboard';
                 }
                 
                 // Initialize search functionality
                 this.$watch('searchTerm', value => {
                     // Search is handled by Livewire wire:model.live
                 });
                 
                 // Ensure activeTab is synced
                 this.$watch('activeTab', value => {
                     console.log('Tab changed to:', value);
                 });
                 
                 // Data is now loaded in mount, no need to trigger here
             }
         }">
        
        
        
        {{-- Header Section --}}
        <div class="modern-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--modern-text-primary); margin: 0;">
                        Retell Control Center
                    </h1>
                    <p style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                        Manage your AI phone agents, webhooks, and integrations
                    </p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    @if($error)
                        <div style="padding: 0.5rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; color: #dc2626; font-size: 0.875rem;">
                            {{ $error }}
                        </div>
                    @endif
                    
                    @if($successMessage)
                        <div style="padding: 0.5rem 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; color: #16a34a; font-size: 0.875rem;">
                            {{ $successMessage }}
                        </div>
                    @endif
                    
                    <button 
                        wire:click="syncAgents" 
                        class="modern-btn modern-btn-primary"
                        wire:loading.attr="disabled"
                        title="Sync all agents from Retell API">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="loading-spinner">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="syncAgents">Sync Agents</span>
                        <span wire:loading wire:target="syncAgents">Syncing...</span>
                    </button>
                    
                    <button 
                        wire:click="refreshData" 
                        class="modern-btn modern-btn-secondary"
                        wire:loading.attr="disabled">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="loading-spinner">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove>Refresh</span>
                        <span wire:loading>Refreshing...</span>
                    </button>
                    
                </div>
            </div>
        </div>
        
        {{-- Tab Navigation --}}
        <div class="modern-tabs">
            <button 
                @click="activeTab = 'dashboard'; $wire.changeTab('dashboard')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'dashboard' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Dashboard
            </button>
            
            <button 
                @click="activeTab = 'agents'; $wire.changeTab('agents')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'agents' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Agents
            </button>
            
            <button 
                @click="activeTab = 'calls'; $wire.changeTab('calls')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'calls' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Calls
            </button>
            
            <button 
                @click="activeTab = 'webhooks'; $wire.changeTab('webhooks')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'webhooks' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Webhooks
            </button>
            
            <button 
                @click="activeTab = 'phones'; $wire.changeTab('phones')"
                class="modern-tab"
                :class="{ 'active': activeTab === 'phones' }">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Phones
            </button>
            
            <button 
                @click="activeTab = 'settings'; $wire.changeTab('settings')"
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
            <div x-show="activeTab === 'dashboard'">
                @include('filament.admin.pages.partials.retell-control-center.dashboard')
            </div>
            
            {{-- Agents Tab --}}
            <div x-show="activeTab === 'agents'" x-transition>
                {{-- Search and Filter Bar --}}
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
                    box-shadow: var(--modern-shadow-sm);
                ">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                        {{-- Search Input --}}
                        <div class="search-input-container">
                            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="agentSearch"
                                placeholder="Search agents by name..."
                                class="search-input">
                        </div>
                        
                        {{-- Refresh Button --}}
                        <button 
                            wire:click="loadAgents"
                            wire:loading.attr="disabled"
                            class="modern-btn modern-btn-secondary">
                            <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="loading-spinner">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span wire:loading.remove>Refresh</span>
                            <span wire:loading>Loading...</span>
                        </button>
                    </div>
                    
                    {{-- Create Agent Button --}}
                    <button 
                        wire:click="openAgentCreator" 
                        class="modern-btn modern-btn-primary">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Agent
                    </button>
                </div>
                
                {{-- Agents Grid --}}
                <div>
                    @if($isLoading)
                        <div class="modern-card" style="text-align: center; padding: 3rem;">
                            <div class="loading-spinner" style="margin: 0 auto 1rem;"></div>
                            <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">Loading agents...</p>
                        </div>
                    @elseif(count($this->filteredAgents) > 0)
                        <div class="agent-card-grid">
                            @foreach($this->filteredAgents as $agent)
                                @php
                                $agentData = $agent;
                                $agentData['all_versions'] = [];
                                
                                if (isset($agent['base_name']) && isset($groupedAgents[$agent['base_name']])) {
                                    $agentData['all_versions'] = $groupedAgents[$agent['base_name']]['versions'] ?? [];
                                }
                                @endphp
                                
                                <x-retell-agent-card 
                                    :agent="$agentData"
                                    :globalState="$globalState ?? []"
                                    wire:key="agent-{{ $agent['agent_id'] ?? uniqid() }}"
                                />
                            @endforeach
                        </div>
                    @else
                        <div class="modern-card" style="text-align: center; padding: 3rem;">
                            <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--modern-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <h3 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.5rem;">
                                @if($agentSearch)
                                    No agents found matching "{{ $agentSearch }}"
                                @else
                                    No agents found
                                @endif
                            </h3>
                            <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                                @if($agentSearch)
                                    Try adjusting your search terms
                                @else
                                    Create your first agent to get started
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Calls Tab --}}
            <div x-show="activeTab === 'calls'" x-transition>
                @include('filament.admin.pages.partials.retell-control-center.calls')
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
        
        {{-- Agent Functions Viewer Modal --}}
        @include('filament.admin.pages.partials.retell-control-center.agent-functions-viewer')
        
        {{-- Agent Editor Modal --}}
        @if($showAgentEditor)
            @include('filament.admin.pages.partials.retell-control-center.agent-editor')
        @endif
        
        {{-- Performance Dashboard Modal --}}
        @if($showPerformanceDashboard)
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 50;
                padding: 1rem;
            ">
                <div style="
                    width: 100%;
                    max-width: 1600px;
                    height: 95vh;
                    background: white;
                    border-radius: 1rem;
                    overflow: hidden;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                " wire:click.stop>
                    @include('filament.admin.pages.partials.retell-control-center.agent-performance')
                </div>
            </div>
        @endif
        
        {{-- Floating Notification System --}}
        <div x-data="{ 
                show: false, 
                message: '', 
                type: 'success',
                timer: null,
                showNotification(detail) {
                    this.message = detail.message;
                    this.type = detail.type || 'success';
                    this.show = true;
                    
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => {
                        this.show = false;
                    }, detail.duration || 5000);
                }
            }"
            x-on:show-notification.window="showNotification($event.detail)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform translate-x-full opacity-0"
            x-transition:enter-end="transform translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform translate-x-0 opacity-100"
            x-transition:leave-end="transform translate-x-full opacity-0"
            style="
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                z-index: 100;
                max-width: 400px;
            "
            @click="show = false">
            <div :class="{
                    'background: #d1fae5; border-color: #6ee7b7; color: #065f46;': type === 'success',
                    'background: #fee2e2; border-color: #fca5a5; color: #991b1b;': type === 'error',
                    'background: #fed7aa; border-color: #fdba74; color: #9a3412;': type === 'warning'
                }"
                :style="{
                    'padding': '1rem 1.5rem',
                    'border': '1px solid',
                    'border-radius': '0.5rem',
                    'box-shadow': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                    'display': 'flex',
                    'align-items': 'center',
                    'gap': '0.75rem',
                    'background': type === 'success' ? '#d1fae5' : (type === 'error' ? '#fee2e2' : '#fed7aa'),
                    'border-color': type === 'success' ? '#6ee7b7' : (type === 'error' ? '#fca5a5' : '#fdba74'),
                    'color': type === 'success' ? '#065f46' : (type === 'error' ? '#991b1b' : '#9a3412')
                }">
                <svg x-show="type === 'success'" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="type === 'error'" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span x-text="message" style="font-weight: 500;"></span>
            </div>
        </div>
    </div>
    
    {{-- Push Scripts --}}
    @push('scripts')
    <script>
        // Ensure Alpine.js is properly initialized
        document.addEventListener('alpine:init', () => {
            Alpine.data('retellControlCenter', () => ({
                // Additional Alpine.js data if needed
            }));
        });
        
        // Ensure data loads on page load
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Control Center DOM loaded');
            console.log('Checking for Livewire:', typeof Livewire !== 'undefined' ? 'Found' : 'Not found');
        });
        
        // Listen for Livewire v3 events
        document.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized');
            
            // Data is loaded in mount, log component info
            const component = Livewire.first();
            if (component) {
                console.log('Found Livewire component:', component.id);
                console.log('Data should already be loaded from mount');
            }
            
            Livewire.on('control-center-mounted', () => {
                console.log('Control Center mounted, data should be loaded');
            });
            
            Livewire.on('control-center-ready', () => {
                console.log('Control Center ready event received');
            });
            
            Livewire.on('tab-changed', (data) => {
                // In Livewire v3, the event data comes directly as parameter
                if (data && data.tab) {
                    console.log('Tab changed to:', data.tab);
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>