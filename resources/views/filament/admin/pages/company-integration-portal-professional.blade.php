<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        /* Professional Design System */
        * {
            box-sizing: border-box;
        }

        .portal-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fafbfc;
            min-height: 100vh;
            padding: 2rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Clean Header */
        .portal-header {
            margin-bottom: 3rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e1e4e8;
        }

        .header-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #0d1117;
            margin: 0;
            letter-spacing: -0.025em;
        }

        .header-subtitle {
            font-size: 1rem;
            color: #57606a;
            margin: 0.5rem 0 0 0;
            font-weight: 400;
        }

        /* Clean Company Grid */
        .companies-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0d1117;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }

        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .company-card {
            background: white;
            border: 1px solid #d1d5da;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }

        .company-card:hover {
            border-color: #0969da;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .company-card.selected {
            border-color: #0969da;
            border-width: 2px;
            padding: calc(1.5rem - 1px);
        }

        .company-card.selected::before {
            content: '';
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 20px;
            height: 20px;
            background: #0969da;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-card.selected::after {
            content: '✓';
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            font-size: 12px;
            font-weight: 700;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0d1117;
            margin: 0 0 0.25rem 0;
        }

        .company-meta {
            font-size: 0.875rem;
            color: #57606a;
            margin: 0 0 1rem 0;
        }

        .company-stats {
            display: flex;
            gap: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e1e4e8;
        }

        .stat {
            font-size: 0.875rem;
            color: #57606a;
        }

        .stat-value {
            font-weight: 600;
            color: #0d1117;
        }

        /* Integration Status - Clean Design */
        .status-section {
            background: white;
            border: 1px solid #d1d5da;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .status-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .status-icon.calcom {
            background: #f6f8fa;
            color: #fb923c;
        }

        .status-icon.retell {
            background: #f6f8fa;
            color: #3b82f6;
        }

        .status-icon.webhooks {
            background: #f6f8fa;
            color: #8b5cf6;
        }

        .status-content {
            flex: 1;
        }

        .status-name {
            font-weight: 600;
            color: #0d1117;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-indicator.active {
            background: #2ea043;
        }

        .status-indicator.inactive {
            background: #d1d5da;
        }

        .status-message {
            font-size: 0.875rem;
            color: #57606a;
            margin-top: 0.25rem;
        }

        /* Clean Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .form-section {
            background: white;
            border: 1px solid #d1d5da;
            border-radius: 8px;
            padding: 2rem;
        }

        .form-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0d1117;
            margin: 0 0 1.5rem 0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #0d1117;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #d1d5da;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #0d1117;
            background: #fafbfc;
            transition: all 0.15s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #0969da;
            background: white;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.1);
        }

        .form-input::placeholder {
            color: #8b949e;
        }

        /* Professional Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            line-height: 1;
        }

        .btn-primary {
            background: #0969da;
            color: white;
            border-color: #0969da;
        }

        .btn-primary:hover {
            background: #0860ca;
            border-color: #0860ca;
        }

        .btn-primary:active {
            background: #0757ba;
        }

        .btn-secondary {
            background: white;
            color: #0d1117;
            border-color: #d1d5da;
        }

        .btn-secondary:hover {
            background: #f6f8fa;
            border-color: #d1d5da;
        }

        .btn-secondary:active {
            background: #e1e4e8;
        }

        .btn-icon {
            width: 16px;
            height: 16px;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border: 1px solid #d1d5da;
            border-radius: 8px;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            color: #8b949e;
            margin: 0 auto 1rem;
        }

        .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0d1117;
            margin: 0 0 0.5rem 0;
        }

        .empty-text {
            font-size: 0.875rem;
            color: #57606a;
            margin: 0;
        }

        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 2px solid #0969da;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dafde1;
            color: #1a4e2a;
            border: 1px solid #bbe5c7;
        }

        .alert-error {
            background: #ffeef0;
            color: #9a1e2a;
            border: 1px solid #ffc1c8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .portal-wrapper {
                padding: 1rem;
            }

            .companies-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .company-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Utility Classes */
        .w-full { width: 100%; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
    </style>

    <div class="portal-wrapper">
        <div class="portal-container">
            {{-- Clean Header --}}
            <div class="portal-header">
                <div class="header-content">
                    <div>
                        <h1 class="header-title">Company Integration Portal</h1>
                        <p class="header-subtitle">Manage your integrations and configurations</p>
                    </div>
                    <button wire:click="refreshData" class="btn btn-secondary">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            {{-- Companies Section --}}
            <div class="companies-section">
                <h2 class="section-title">Select Company</h2>
                <div class="companies-grid">
                    @foreach($companies as $company)
                        <div class="company-card {{ $selectedCompanyId == $company['id'] ? 'selected' : '' }}"
                             wire:click="selectCompany({{ $company['id'] }})">
                            <h3 class="company-name">{{ $company['name'] }}</h3>
                            @if(!empty($company['slug']))
                                <p class="company-meta">{{ $company['slug'] }}</p>
                            @endif
                            
                            <div class="company-stats">
                                <div class="stat">
                                    <span class="stat-value">{{ $company['branch_count'] }}</span>
                                    {{ $company['branch_count'] == 1 ? 'Branch' : 'Branches' }}
                                </div>
                                <div class="stat">
                                    <span class="stat-value">{{ $company['phone_count'] }}</span>
                                    {{ $company['phone_count'] == 1 ? 'Number' : 'Numbers' }}
                                </div>
                                <div class="stat">
                                    Status: <span class="stat-value">{{ $company['is_active'] ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($selectedCompany)
                <div wire:loading.class="loading" wire:target="selectCompany">
                    {{-- Integration Status --}}
                    <div class="status-section">
                        <h2 class="section-title">Integration Status</h2>
                        
                        <div class="status-grid">
                            {{-- Cal.com Status --}}
                            <div class="status-item">
                                <div class="status-icon calcom">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="status-content">
                                    <div class="status-name">
                                        Cal.com
                                        <span class="status-indicator {{ $integrationStatus['calcom']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                                    </div>
                                    <p class="status-message">
                                        {{ $integrationStatus['calcom']['message'] ?? 'Not configured' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Retell.ai Status --}}
                            <div class="status-item">
                                <div class="status-icon retell">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="status-content">
                                    <div class="status-name">
                                        Retell.ai
                                        <span class="status-indicator {{ $integrationStatus['retell']['configured'] ?? false ? 'active' : 'inactive' }}"></span>
                                    </div>
                                    <p class="status-message">
                                        {{ $integrationStatus['retell']['message'] ?? 'Not configured' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Webhooks Status --}}
                            <div class="status-item">
                                <div class="status-icon webhooks">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="status-content">
                                    <div class="status-name">
                                        Webhooks
                                        <span class="status-indicator {{ ($integrationStatus['webhooks']['recent_webhooks'] ?? 0) > 0 ? 'active' : 'inactive' }}"></span>
                                    </div>
                                    <p class="status-message">
                                        {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} webhooks in last 24 hours
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuration Forms --}}
                    <div class="form-grid">
                        {{-- Cal.com Configuration --}}
                        <div class="form-section">
                            <h3 class="form-title">Cal.com Configuration</h3>
                            
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
                                
                                <div class="button-group">
                                    <button type="submit" class="btn btn-primary">
                                        Save Configuration
                                    </button>
                                    @if($integrationStatus['calcom']['configured'] ?? false)
                                        <button type="button" 
                                                wire:click="testCalcomIntegration" 
                                                class="btn btn-secondary">
                                            Test Connection
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>

                        {{-- Retell.ai Configuration --}}
                        <div class="form-section">
                            <h3 class="form-title">Retell.ai Configuration</h3>
                            
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
                                
                                <div class="button-group">
                                    <button type="submit" class="btn btn-primary">
                                        Save Configuration
                                    </button>
                                    @if($integrationStatus['retell']['configured'] ?? false)
                                        <button type="button" 
                                                wire:click="testRetellIntegration" 
                                                class="btn btn-secondary">
                                            Test Connection
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Branches Section --}}
                    @if(count($branches) > 0)
                        <div class="status-section mt-8">
                            <h2 class="section-title">Branches</h2>
                            <div class="status-grid">
                                @foreach($branches as $branch)
                                    <div class="status-item">
                                        <div class="status-content">
                                            <div class="status-name">
                                                {{ $branch['name'] }}
                                                <span class="status-indicator {{ $branch['is_active'] ? 'active' : 'inactive' }}"></span>
                                            </div>
                                            <p class="status-message">
                                                {{ $branch['city'] ?? 'No location specified' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Empty State --}}
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="empty-title">No Company Selected</h3>
                    <p class="empty-text">Select a company from the list above to manage its integrations.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>