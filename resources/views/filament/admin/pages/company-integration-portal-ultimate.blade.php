<x-filament-panels::page class="fi-company-integration-portal-ultimate">
    {{-- Custom Styles --}}
    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/filament/admin/company-integration-portal-ultimate.css') }}">
    @endpush
    
    {{-- Custom Scripts --}}
    @push('scripts')
    <script src="{{ asset('js/company-integration-portal-ultimate.js') }}"></script>
    @endpush
    
    <div class="portal-container" x-data="companyIntegrationPortal">
        {{-- Premium Header with Glassmorphism --}}
        <div class="portal-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon-wrapper">
                        <div class="header-icon-glow"></div>
                        <x-heroicon-o-building-office-2 class="header-icon" />
                    </div>
                    <div class="header-text">
                        <h1 class="header-title">Integration Control Center</h1>
                        <p class="header-subtitle">Manage all integrations, agents, and configurations in one place</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button wire:click="refreshData" class="header-action-button">
                        <x-heroicon-m-arrow-path class="w-4 h-4" />
                        <span>Refresh</span>
                    </button>
                    <a href="https://docs.askproai.de/integrations" target="_blank" class="header-action-button secondary">
                        <x-heroicon-m-book-open class="w-4 h-4" />
                        <span>Docs</span>
                    </a>
                </div>
            </div>
            
            {{-- Live Status Bar --}}
            @if($selectedCompany)
            <div class="status-bar">
                <div class="status-item" data-status="{{ collect($integrationStatus)->filter(fn($s) => $s['configured'] ?? false)->count() > 3 ? 'success' : 'warning' }}">
                    <div class="status-indicator"></div>
                    <span class="status-label">{{ collect($integrationStatus)->filter(fn($s) => $s['configured'] ?? false)->count() }}/5 Integrations</span>
                </div>
                <div class="status-item" data-status="{{ ($integrationStatus['webhooks']['recent_webhooks'] ?? 0) > 0 ? 'success' : 'warning' }}">
                    <div class="status-indicator"></div>
                    <span class="status-label">{{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} Webhooks (24h)</span>
                </div>
                <div class="status-item" data-status="{{ count($phoneNumbers) > 0 ? 'success' : 'warning' }}">
                    <div class="status-indicator"></div>
                    <span class="status-label">{{ count($phoneNumbers) }} Phone Numbers</span>
                </div>
                <div class="status-item" data-status="{{ count($branches) > 0 ? 'success' : 'warning' }}">
                    <div class="status-indicator"></div>
                    <span class="status-label">{{ count($branches) }} Branches</span>
                </div>
            </div>
            @endif
        </div>
        
        {{-- Company Selector with Modern Cards --}}
        <div class="section-wrapper">
            <div class="section-header">
                <h2 class="section-title">Select Company</h2>
                <p class="section-description">Choose a company to manage its integrations</p>
            </div>
            
            <div class="company-grid">
                @foreach($companies as $company)
                <button 
                    wire:click="selectCompany({{ $company['id'] }})"
                    class="company-card {{ $selectedCompanyId === $company['id'] ? 'selected' : '' }}"
                    wire:loading.class="loading"
                    wire:target="selectCompany"
                >
                    <div class="company-card-glow"></div>
                    <div class="company-card-content">
                        <div class="company-icon">
                            <x-heroicon-o-building-office class="w-8 h-8" />
                        </div>
                        <div class="company-info">
                            <h3 class="company-name">{{ $company['name'] }}</h3>
                            @if($company['slug'])
                            <p class="company-slug">{{ $company['slug'] }}</p>
                            @endif
                        </div>
                        <div class="company-stats">
                            <div class="stat">
                                <x-heroicon-m-building-office-2 class="w-4 h-4" />
                                <span>{{ $company['branch_count'] }}</span>
                            </div>
                            <div class="stat">
                                <x-heroicon-m-phone class="w-4 h-4" />
                                <span>{{ $company['phone_count'] }}</span>
                            </div>
                        </div>
                        <div class="company-status {{ $company['is_active'] ? 'active' : 'inactive' }}">
                            <div class="status-dot"></div>
                            <span>{{ $company['is_active'] ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>
                    @if($selectedCompanyId === $company['id'])
                    <div class="selection-indicator">
                        <x-heroicon-m-check class="w-5 h-5" />
                    </div>
                    @endif
                </button>
                @endforeach
            </div>
        </div>
        
        @if($selectedCompany)
        <div class="company-details" wire:loading.class="loading" wire:target="selectCompany">
            {{-- Integration Dashboard --}}
            <div class="integration-dashboard">
                <div class="dashboard-grid">
                    {{-- Cal.com Integration --}}
                    <div class="integration-card" data-integration="calcom" data-status="{{ $integrationStatus['calcom']['status'] ?? 'warning' }}">
                        <div class="integration-header">
                            <div class="integration-icon-wrapper">
                                <x-heroicon-o-calendar-days class="integration-icon" />
                            </div>
                            <div class="integration-title-wrapper">
                                <h3 class="integration-title">Cal.com</h3>
                                <p class="integration-subtitle">Calendar & Booking System</p>
                            </div>
                            <div class="integration-status">
                                @if($integrationStatus['calcom']['configured'])
                                <div class="status-badge success">Connected</div>
                                @else
                                <div class="status-badge warning">Not Connected</div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="config-items">
                                <div class="config-item">
                                    <span class="config-label">API Key</span>
                                    <div class="config-value">
                                        @if($integrationStatus['calcom']['api_key'])
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                        @else
                                        <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                        @endif
                                    </div>
                                </div>
                                <div class="config-item">
                                    <span class="config-label">Team Slug</span>
                                    <div class="config-value">
                                        @if($integrationStatus['calcom']['team_slug'])
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                        @else
                                        <x-heroicon-m-information-circle class="w-4 h-4 text-blue-500" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($integrationStatus['calcom']['event_types'] > 0)
                            <div class="integration-stat">
                                <div class="stat-number">{{ $integrationStatus['calcom']['event_types'] }}</div>
                                <div class="stat-label">Event Types</div>
                            </div>
                            @endif
                            
                            <div class="integration-actions">
                                {{ $this->saveCalcomApiKeyAction }}
                                @if($integrationStatus['calcom']['api_key'])
                                {{ $this->saveCalcomTeamSlugAction }}
                                @endif
                                @if($integrationStatus['calcom']['configured'])
                                <button wire:click="testCalcomIntegration" class="action-button test">
                                    <x-heroicon-m-play class="w-4 h-4" />
                                    Test
                                </button>
                                <button wire:click="syncCalcomEventTypes" class="action-button sync">
                                    <x-heroicon-m-arrow-path class="w-4 h-4" />
                                    Sync
                                </button>
                                @endif
                            </div>
                            
                            @if(isset($testResults['calcom']))
                            <div class="test-result {{ $testResults['calcom']['success'] ? 'success' : 'error' }}">
                                <p class="result-message">{{ $testResults['calcom']['message'] }}</p>
                                <p class="result-time">{{ $testResults['calcom']['tested_at'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Retell.ai Integration --}}
                    <div class="integration-card" data-integration="retell" data-status="{{ $integrationStatus['retell']['status'] ?? 'warning' }}">
                        <div class="integration-header">
                            <div class="integration-icon-wrapper">
                                <x-heroicon-o-phone class="integration-icon" />
                            </div>
                            <div class="integration-title-wrapper">
                                <h3 class="integration-title">Retell.ai</h3>
                                <p class="integration-subtitle">AI Phone System</p>
                            </div>
                            <div class="integration-status">
                                @if($integrationStatus['retell']['configured'])
                                <div class="status-badge success">Connected</div>
                                @else
                                <div class="status-badge warning">Not Connected</div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="config-items">
                                <div class="config-item">
                                    <span class="config-label">API Key</span>
                                    <div class="config-value">
                                        @if($integrationStatus['retell']['api_key'])
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                        @else
                                        <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                        @endif
                                    </div>
                                </div>
                                <div class="config-item">
                                    <span class="config-label">Agent ID</span>
                                    <div class="config-value">
                                        @if($integrationStatus['retell']['agent_id'])
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                        @else
                                        <x-heroicon-m-x-circle class="w-4 h-4 text-red-500" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($integrationStatus['retell']['phone_numbers'] > 0)
                            <div class="integration-stat">
                                <div class="stat-number">{{ $integrationStatus['retell']['phone_numbers'] }}</div>
                                <div class="stat-label">Phone Numbers</div>
                            </div>
                            @endif
                            
                            <div class="integration-actions">
                                {{ $this->saveRetellApiKeyAction }}
                                @if($integrationStatus['retell']['api_key'])
                                {{ $this->saveRetellAgentIdAction }}
                                @endif
                                @if($integrationStatus['retell']['configured'])
                                <button wire:click="testRetellIntegration" class="action-button test">
                                    <x-heroicon-m-play class="w-4 h-4" />
                                    Test
                                </button>
                                <button wire:click="importRetellCalls" class="action-button sync">
                                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                    Import
                                </button>
                                @endif
                            </div>
                            
                            @if(isset($testResults['retell']))
                            <div class="test-result {{ $testResults['retell']['success'] ? 'success' : 'error' }}">
                                <p class="result-message">{{ $testResults['retell']['message'] }}</p>
                                <p class="result-time">{{ $testResults['retell']['tested_at'] }}</p>
                            </div>
                            @endif
                            
                            <div class="webhook-info">
                                <p class="webhook-label">Webhook URL:</p>
                                <code class="webhook-url">https://api.askproai.de/api/mcp/webhook/retell</code>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Webhooks Status --}}
                    <div class="integration-card" data-integration="webhooks" data-status="{{ $integrationStatus['webhooks']['status'] ?? 'warning' }}">
                        <div class="integration-header">
                            <div class="integration-icon-wrapper">
                                <x-heroicon-o-link class="integration-icon" />
                            </div>
                            <div class="integration-title-wrapper">
                                <h3 class="integration-title">Webhooks</h3>
                                <p class="integration-subtitle">Real-time Events</p>
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            @if($integrationStatus['webhooks']['recent_webhooks'] > 0)
                            <div class="webhook-activity active">
                                <div class="activity-icon">
                                    <div class="pulse-ring"></div>
                                    <x-heroicon-m-signal class="w-6 h-6" />
                                </div>
                                <div class="activity-stats">
                                    <div class="stat-number">{{ $integrationStatus['webhooks']['recent_webhooks'] }}</div>
                                    <div class="stat-label">Events in 24h</div>
                                </div>
                            </div>
                            @else
                            <div class="webhook-activity inactive">
                                <x-heroicon-o-signal-slash class="w-12 h-12" />
                                <p>No recent activity</p>
                            </div>
                            @endif
                            
                            <a href="{{ route('filament.admin.pages.webhook-monitor') }}" class="action-button primary full-width">
                                <x-heroicon-m-chart-bar class="w-4 h-4" />
                                Webhook Monitor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Phone Numbers Management --}}
            <div class="section-wrapper">
                <div class="section-header">
                    <h2 class="section-title">Phone Numbers & Agent Assignment</h2>
                    <p class="section-description">Configure phone numbers and assign AI agents with version control</p>
                </div>
                
                <div class="phone-management">
                    @if(count($phoneNumbers) > 0)
                    <div class="phone-grid">
                        @foreach($phoneNumbers as $phone)
                        <div class="phone-card {{ $phone['is_active'] ? 'active' : 'inactive' }}">
                            <div class="phone-header">
                                <div class="phone-number">
                                    <x-heroicon-m-phone class="w-5 h-5" />
                                    <span>{{ $phone['formatted'] ?? $phone['number'] }}</span>
                                </div>
                                <div class="phone-status {{ $phone['is_active'] ? 'active' : 'inactive' }}">
                                    <div class="status-dot"></div>
                                </div>
                            </div>
                            
                            <div class="phone-details">
                                <div class="detail-row">
                                    <span class="detail-label">Branch:</span>
                                    <span class="detail-value">{{ $phone['branch_name'] ?? 'Unassigned' }}</span>
                                </div>
                                
                                @if($phone['is_primary'])
                                <div class="primary-badge">
                                    <x-heroicon-m-star class="w-4 h-4" />
                                    Primary
                                </div>
                                @endif
                                
                                <div class="agent-assignment">
                                    <label class="assignment-label">Retell Agent:</label>
                                    <div class="agent-selector">
                                        <select 
                                            wire:model="phoneAgentMapping.{{ $phone['id'] }}"
                                            wire:change="updatePhoneAgent({{ $phone['id'] }}, $event.target.value)"
                                            class="agent-select"
                                        >
                                            <option value="">No Agent</option>
                                            @foreach($retellAgents as $agent)
                                            <option value="{{ $agent['agent_id'] }}">
                                                {{ $agent['agent_name'] ?? 'Unnamed Agent' }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @if($phone['retell_agent_id'])
                                        <a 
                                            href="https://app.retellai.com/agents/{{ $phone['retell_agent_id'] }}"
                                            target="_blank"
                                            class="agent-link"
                                            title="Open in Retell.ai"
                                        >
                                            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <x-heroicon-o-phone class="empty-icon" />
                        <h3>No Phone Numbers</h3>
                        <p>Add phone numbers to start receiving calls</p>
                        <a href="{{ route('filament.admin.resources.phone-numbers.create') }}" class="action-button primary">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            Add Phone Number
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Branches Management --}}
            <div class="section-wrapper">
                <div class="section-header">
                    <h2 class="section-title">Branch Configuration</h2>
                    <p class="section-description">Manage branches with inline editing and event type assignments</p>
                </div>
                
                <div class="branches-grid">
                    @foreach($branches as $branch)
                    <div class="branch-card-ultimate {{ $branch['is_active'] ? 'active' : 'inactive' }}" x-data="{ expanded: false }">
                        <div class="branch-header-ultimate">
                            <div class="branch-title-section">
                                @if($branchEditStates["name_{$branch['id']}"] ?? false)
                                <div class="inline-edit-group">
                                    <input 
                                        type="text"
                                        wire:model="branchNames.{{ $branch['id'] }}"
                                        class="inline-edit-input"
                                        wire:keydown.enter="saveBranchName({{ $branch['id'] }})"
                                        wire:keydown.escape="toggleBranchNameInput({{ $branch['id'] }})"
                                    />
                                    <button wire:click="saveBranchName({{ $branch['id'] }})" class="inline-edit-button save">
                                        <x-heroicon-m-check class="w-4 h-4" />
                                    </button>
                                    <button wire:click="toggleBranchNameInput({{ $branch['id'] }})" class="inline-edit-button cancel">
                                        <x-heroicon-m-x-mark class="w-4 h-4" />
                                    </button>
                                </div>
                                @else
                                <div class="branch-name-group">
                                    <x-heroicon-o-building-storefront class="branch-icon" />
                                    <h3 class="branch-name">{{ $branch['name'] }}</h3>
                                    @if($branch['is_main'])
                                    <span class="main-badge">Main Branch</span>
                                    @endif
                                    <button wire:click="toggleBranchNameInput({{ $branch['id'] }})" class="edit-trigger">
                                        <x-heroicon-m-pencil-square class="w-4 h-4" />
                                    </button>
                                </div>
                                @endif
                            </div>
                            
                            <div class="branch-controls">
                                <div class="branch-toggle">
                                    <span class="toggle-label">{{ $branch['is_active'] ? 'Active' : 'Inactive' }}</span>
                                    <button
                                        wire:click="toggleBranchActiveState({{ $branch['id'] }})"
                                        class="toggle-switch {{ $branch['is_active'] ? 'active' : '' }}"
                                    >
                                        <span class="toggle-thumb"></span>
                                    </button>
                                </div>
                                
                                <div class="branch-menu" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="menu-button">
                                        <x-heroicon-m-ellipsis-vertical class="w-5 h-5" />
                                    </button>
                                    <div x-show="open" x-transition class="menu-dropdown">
                                        <a href="{{ route('filament.admin.resources.branches.edit', $branch['id']) }}" class="menu-item">
                                            <x-heroicon-m-cog-6-tooth class="w-4 h-4" />
                                            Advanced Settings
                                        </a>
                                        @if(!$branch['is_main'])
                                        <button wire:click="deleteBranch({{ $branch['id'] }})" wire:confirm="Are you sure?" class="menu-item danger">
                                            <x-heroicon-m-trash class="w-4 h-4" />
                                            Delete Branch
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="branch-content-ultimate">
                            {{-- Quick Stats --}}
                            <div class="branch-stats">
                                <div class="stat-item">
                                    <x-heroicon-m-calendar-days class="stat-icon" />
                                    <span class="stat-value">{{ $branch['event_type_count'] }}</span>
                                    <span class="stat-label">Event Types</span>
                                </div>
                                <div class="stat-item">
                                    <x-heroicon-m-phone class="stat-icon" />
                                    <span class="stat-value">{{ $branch['phone_count'] }}</span>
                                    <span class="stat-label">Phone Numbers</span>
                                </div>
                                <div class="stat-item">
                                    <x-heroicon-m-users class="stat-icon" />
                                    <span class="stat-value">{{ $branch['staff_count'] }}</span>
                                    <span class="stat-label">Staff Members</span>
                                </div>
                            </div>
                            
                            {{-- Inline Editable Fields --}}
                            <div class="branch-fields">
                                {{-- Address --}}
                                <div class="field-group">
                                    <label class="field-label">Address</label>
                                    @if($branchEditStates["address_{$branch['id']}"] ?? false)
                                    <div class="inline-edit-group">
                                        <input 
                                            type="text" 
                                            wire:model="branchAddresses.{{ $branch['id'] }}" 
                                            class="inline-edit-input"
                                            wire:keydown.enter="saveBranchAddress({{ $branch['id'] }})"
                                        />
                                        <button wire:click="saveBranchAddress({{ $branch['id'] }})" class="inline-edit-button save">
                                            <x-heroicon-m-check class="w-4 h-4" />
                                        </button>
                                        <button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" class="inline-edit-button cancel">
                                            <x-heroicon-m-x-mark class="w-4 h-4" />
                                        </button>
                                    </div>
                                    @else
                                    <div class="field-value-group">
                                        <span class="field-value">{{ $branch['address'] ?? 'Not set' }}</span>
                                        <button wire:click="toggleBranchAddressInput({{ $branch['id'] }})" class="edit-trigger">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </button>
                                    </div>
                                    @endif
                                </div>
                                
                                {{-- Email --}}
                                <div class="field-group">
                                    <label class="field-label">Email</label>
                                    @if($branchEditStates["email_{$branch['id']}"] ?? false)
                                    <div class="inline-edit-group">
                                        <input 
                                            type="email" 
                                            wire:model="branchEmails.{{ $branch['id'] }}" 
                                            class="inline-edit-input"
                                            wire:keydown.enter="saveBranchEmail({{ $branch['id'] }})"
                                        />
                                        <button wire:click="saveBranchEmail({{ $branch['id'] }})" class="inline-edit-button save">
                                            <x-heroicon-m-check class="w-4 h-4" />
                                        </button>
                                        <button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" class="inline-edit-button cancel">
                                            <x-heroicon-m-x-mark class="w-4 h-4" />
                                        </button>
                                    </div>
                                    @else
                                    <div class="field-value-group">
                                        <span class="field-value">{{ $branch['email'] ?? 'Not set' }}</span>
                                        <button wire:click="toggleBranchEmailInput({{ $branch['id'] }})" class="edit-trigger">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Advanced Configuration (Collapsible) --}}
                            <div class="branch-advanced">
                                <button @click="expanded = !expanded" class="expand-button">
                                    <span>Advanced Configuration</span>
                                    <x-heroicon-m-chevron-down class="w-5 h-5 transition-transform" x-bind:class="{ 'rotate-180': expanded }" />
                                </button>
                                
                                <div x-show="expanded" x-collapse class="advanced-content">
                                    {{-- Event Types Configuration --}}
                                    <div class="config-section">
                                        <h4 class="config-title">
                                            <x-heroicon-o-calendar-days class="w-5 h-5" />
                                            Cal.com Event Types
                                        </h4>
                                        @if($branch['event_type_count'] > 0)
                                        <div class="event-types-preview">
                                            @if($branch['primary_event_type_name'])
                                            <div class="event-type-item primary">
                                                <span class="primary-badge">Primary</span>
                                                <span>{{ $branch['primary_event_type_name'] }}</span>
                                            </div>
                                            @endif
                                            @if($branch['event_type_count'] > 1)
                                            <p class="more-types">+{{ $branch['event_type_count'] - 1 }} more</p>
                                            @endif
                                        </div>
                                        @else
                                        <p class="no-config">No event types configured</p>
                                        @endif
                                        <button wire:click="manageBranchEventTypes('{{ $branch['id'] }}')" class="config-button">
                                            <x-heroicon-m-cog-6-tooth class="w-4 h-4" />
                                            Manage Event Types
                                        </button>
                                    </div>
                                    
                                    {{-- Retell Agent Configuration --}}
                                    <div class="config-section">
                                        <h4 class="config-title">
                                            <x-heroicon-o-cpu-chip class="w-5 h-5" />
                                            Retell.ai Agent Override
                                        </h4>
                                        @if($branchEditStates["retell_{$branch['id']}"] ?? false)
                                        <div class="inline-edit-group">
                                            <input 
                                                type="text" 
                                                wire:model="branchRetellAgentIds.{{ $branch['id'] }}" 
                                                placeholder="agent_xxx or empty for default"
                                                class="inline-edit-input"
                                            />
                                            <button wire:click="saveBranchRetellAgent({{ $branch['id'] }})" class="inline-edit-button save">
                                                <x-heroicon-m-check class="w-4 h-4" />
                                            </button>
                                            <button wire:click="toggleBranchRetellAgentInput({{ $branch['id'] }})" class="inline-edit-button cancel">
                                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                        @else
                                        <div class="agent-status">
                                            @if($branch['has_retell'])
                                            <span class="agent-badge custom">Custom Agent</span>
                                            <code>{{ substr($branch['retell_agent_id'], 0, 15) }}...</code>
                                            @elseif($branch['uses_master_retell'])
                                            <span class="agent-badge default">Using Default</span>
                                            @else
                                            <span class="agent-badge none">Not Configured</span>
                                            @endif
                                            <button wire:click="toggleBranchRetellAgentInput({{ $branch['id'] }})" class="config-button small">
                                                {{ $branch['has_retell'] ? 'Change' : 'Configure' }}
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                    {{-- Add New Branch --}}
                    <a href="{{ route('filament.admin.resources.branches.create') }}" class="add-branch-card">
                        <x-heroicon-m-plus-circle class="w-12 h-12" />
                        <span>Add New Branch</span>
                    </a>
                </div>
            </div>
            
            {{-- Service Mappings --}}
            <div class="section-wrapper">
                <div class="section-header">
                    <h2 class="section-title">Service-EventType Mapping</h2>
                    <p class="section-description">Link services with Cal.com event types for automatic booking</p>
                    {{ $this->openServiceMappingModalAction }}
                </div>
                
                <div class="mappings-container">
                    @if(count($serviceMappings ?? []) > 0)
                    <div class="mappings-grid">
                        @foreach($serviceMappings as $mapping)
                        <div class="mapping-card">
                            <div class="mapping-flow">
                                <div class="flow-item service">
                                    <x-heroicon-o-briefcase class="w-5 h-5" />
                                    <span>{{ $mapping->service_name }}</span>
                                </div>
                                <div class="flow-arrow">→</div>
                                <div class="flow-item event">
                                    <x-heroicon-o-calendar class="w-5 h-5" />
                                    <span>{{ $mapping->event_type_name }}</span>
                                </div>
                            </div>
                            <div class="mapping-meta">
                                @if($mapping->branch_name)
                                <span class="meta-item">
                                    <x-heroicon-m-building-office-2 class="w-4 h-4" />
                                    {{ $mapping->branch_name }}
                                </span>
                                @else
                                <span class="meta-item all">All Branches</span>
                                @endif
                            </div>
                            <button 
                                wire:click="removeServiceMapping({{ $mapping->id }})"
                                wire:confirm="Remove this mapping?"
                                class="remove-button"
                            >
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <x-heroicon-o-link class="empty-icon" />
                        <h3>No Service Mappings</h3>
                        <p>Create mappings to link services with calendar event types</p>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Quick Actions Bar --}}
            <div class="quick-actions-bar">
                <h3 class="actions-title">Quick Actions</h3>
                <div class="actions-grid">
                    <button wire:click="testAllIntegrations" class="quick-action-button" wire:loading.attr="disabled">
                        <x-heroicon-m-play class="w-5 h-5" />
                        <span>Test All</span>
                    </button>
                    <button wire:click="openSetupWizard" class="quick-action-button">
                        <x-heroicon-m-sparkles class="w-5 h-5" />
                        <span>Setup Wizard</span>
                    </button>
                    <a href="{{ route('filament.admin.pages.event-type-import-wizard') }}" class="quick-action-button">
                        <x-heroicon-m-calendar class="w-5 h-5" />
                        <span>Import Events</span>
                    </a>
                    <button wire:click="syncRetellAgents" class="quick-action-button">
                        <x-heroicon-m-arrow-path class="w-5 h-5" />
                        <span>Sync Agents</span>
                    </button>
                </div>
            </div>
        </div>
        @else
        {{-- Empty State --}}
        <div class="empty-state-full">
            <div class="empty-content">
                <x-heroicon-o-building-office class="empty-icon-large" />
                <h2>No Company Selected</h2>
                <p>Select a company above to manage its integrations and configurations</p>
            </div>
        </div>
        @endif
    </div>
    
    {{-- Event Type Management Modal --}}
    <x-filament::modal id="event-type-modal" wire:model="showEventTypeModal" width="4xl">
        <x-slot name="heading">
            <div class="modal-header-ultimate">
                <x-heroicon-o-calendar-days class="w-6 h-6" />
                <span>Manage Event Types</span>
            </div>
        </x-slot>
        
        @if($currentBranchId && isset($branchEventTypes[$currentBranchId]))
        <div class="event-type-modal-content">
            {{-- Assigned Event Types --}}
            <div class="modal-section">
                <h3 class="section-title">Assigned Event Types</h3>
                @if(count($branchEventTypes[$currentBranchId]) > 0)
                <div class="event-types-list">
                    @foreach($branchEventTypes[$currentBranchId] as $eventType)
                    <div class="event-type-card {{ $eventType['is_primary'] ? 'primary' : '' }}">
                        <div class="event-type-info">
                            @if($eventType['is_primary'])
                            <span class="primary-indicator">Primary</span>
                            @endif
                            <h4>{{ $eventType['name'] }}</h4>
                            <div class="event-meta">
                                <span>ID: {{ $eventType['calcom_id'] }}</span>
                                <span>{{ $eventType['duration'] }} min</span>
                            </div>
                        </div>
                        <div class="event-type-actions">
                            @if(!$eventType['is_primary'])
                            <button 
                                wire:click="setPrimaryEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                class="action-button small"
                            >
                                Set as Primary
                            </button>
                            @endif
                            @if(!$eventType['is_primary'] || count($branchEventTypes[$currentBranchId]) > 1)
                            <button 
                                wire:click="removeBranchEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                                wire:confirm="Remove this event type?"
                                class="action-button small danger"
                            >
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="empty-message">No event types assigned yet</p>
                @endif
            </div>
            
            {{-- Available Event Types --}}
            @if(count($availableEventTypes) > 0)
            <div class="modal-section">
                <h3 class="section-title">Available Event Types</h3>
                <div class="available-event-types">
                    @foreach($availableEventTypes as $eventType)
                    <div class="available-event-card">
                        <div class="event-info">
                            <h4>{{ $eventType['name'] }}</h4>
                            <div class="event-meta">
                                <span>ID: {{ $eventType['calcom_id'] }}</span>
                                <span>{{ $eventType['duration'] }} min</span>
                            </div>
                        </div>
                        <button 
                            wire:click="addBranchEventType('{{ $currentBranchId }}', {{ $eventType['id'] }})"
                            class="add-button"
                        >
                            <x-heroicon-m-plus class="w-4 h-4" />
                            Add
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
        
        <x-slot name="footer">
            <button wire:click="closeEventTypeModal" class="modal-close-button">
                Close
            </button>
        </x-slot>
    </x-filament::modal>
    
    {{-- Filament Actions Modals --}}
    <x-filament-actions::modals />
</x-filament-panels::page>