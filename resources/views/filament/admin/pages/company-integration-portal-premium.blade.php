<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        /* Premium Design System */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        /* Animated Background */
        .portal-wrapper {
            position: relative;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
        }

        .portal-wrapper::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(236, 72, 153, 0.2) 0%, transparent 50%);
            animation: gradient-shift 20s ease infinite;
            z-index: 0;
        }

        @keyframes gradient-shift {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }

        /* Glass Container */
        .portal-container {
            position: relative;
            z-index: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Premium Header */
        .premium-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .premium-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 180deg at 50% 50%, rgba(99, 102, 241, 0.2) 0deg, rgba(168, 85, 247, 0.2) 60deg, rgba(236, 72, 153, 0.2) 120deg, transparent 180deg);
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .header-subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.8);
            margin: 0.5rem 0 0 0;
        }

        /* Company Cards Grid */
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Premium Company Card */
        .company-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .company-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .company-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .company-card:hover::before {
            transform: scaleX(1);
        }

        .company-card.selected {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border-color: var(--primary);
        }

        .company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .company-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .stat-icon {
            width: 20px;
            height: 20px;
            padding: 3px;
            background: var(--gray-100);
            border-radius: 6px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 1rem;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-badge.inactive {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-500);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Integration Dashboard */
        .integration-dashboard {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
        }

        .integrations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        /* Integration Card */
        .integration-card {
            background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .integration-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .integration-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .integration-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .integration-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .integration-icon.calcom {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }

        .integration-icon.retell {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
        }

        .integration-icon.webhooks {
            background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        }

        .integration-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .integration-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        .integration-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .integration-status.active {
            background: var(--success);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        }

        .integration-status.inactive {
            background: var(--gray-400);
        }

        .integration-message {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        /* Modern Form Styling */
        .form-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Premium Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            color: var(--gray-400);
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: var(--gray-600);
        }

        /* Loading States */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: inherit;
            z-index: 10;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .companies-grid {
                grid-template-columns: 1fr;
            }
            
            .form-section {
                grid-template-columns: 1fr;
            }
            
            .header-title {
                font-size: 1.875rem;
            }
        }
    </style>

    <div class="portal-wrapper">
        <div class="portal-container">
            {{-- Premium Header --}}
            <div class="premium-header">
                <div class="header-content">
                    <div>
                        <h1 class="header-title">Company Integration Portal</h1>
                        <p class="header-subtitle">Verwalten Sie alle Integrationen an einem Ort</p>
                    </div>
                    <button wire:click="refreshData" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Aktualisieren
                    </button>
                </div>
            </div>

            {{-- Company Selection --}}
            <div class="companies-grid">
                @foreach($companies as $company)
                    <div class="company-card {{ $selectedCompanyId == $company['id'] ? 'selected' : '' }}"
                         wire:click="selectCompany({{ $company['id'] }})">
                        <h3 class="company-name">{{ $company['name'] }}</h3>
                        @if(!empty($company['slug']))
                            <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0.25rem 0;">
                                {{ $company['slug'] }}
                            </p>
                        @endif
                        
                        <div class="company-stats">
                            <div class="stat">
                                <div class="stat-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span>{{ $company['branch_count'] }} {{ $company['branch_count'] == 1 ? 'Filiale' : 'Filialen' }}</span>
                            </div>
                            <div class="stat">
                                <div class="stat-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <span>{{ $company['phone_count'] }} {{ $company['phone_count'] == 1 ? 'Nummer' : 'Nummern' }}</span>
                            </div>
                        </div>
                        
                        <div class="status-badge {{ $company['is_active'] ? 'active' : 'inactive' }}">
                            <span class="status-dot"></span>
                            <span>{{ $company['is_active'] ? 'Aktiv' : 'Inaktiv' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($selectedCompany)
                <div wire:loading.delay class="loading-overlay">
                    <div class="spinner"></div>
                </div>

                {{-- Integration Dashboard --}}
                <div class="integration-dashboard">
                    <h2 class="dashboard-title">Integration Status für {{ $selectedCompany->name }}</h2>
                    
                    <div class="integrations-grid">
                        {{-- Cal.com --}}
                        <div class="integration-card">
                            <div class="integration-header">
                                <div class="integration-info">
                                    <div class="integration-icon calcom">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="integration-name">Cal.com</div>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">Kalender & Buchungen</div>
                                    </div>
                                </div>
                                <div class="integration-status {{ $integrationStatus['calcom']['configured'] ?? false ? 'active' : 'inactive' }}"></div>
                            </div>
                            <p class="integration-message">
                                {{ $integrationStatus['calcom']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                            @if($integrationStatus['calcom']['configured'] ?? false)
                                <button wire:click="testCalcomIntegration" class="btn btn-primary w-full">
                                    Verbindung testen
                                </button>
                            @endif
                        </div>

                        {{-- Retell.ai --}}
                        <div class="integration-card">
                            <div class="integration-header">
                                <div class="integration-info">
                                    <div class="integration-icon retell">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="integration-name">Retell.ai</div>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">KI-Telefon Agent</div>
                                    </div>
                                </div>
                                <div class="integration-status {{ $integrationStatus['retell']['configured'] ?? false ? 'active' : 'inactive' }}"></div>
                            </div>
                            <p class="integration-message">
                                {{ $integrationStatus['retell']['message'] ?? 'Nicht konfiguriert' }}
                            </p>
                            @if($integrationStatus['retell']['configured'] ?? false)
                                <button wire:click="testRetellIntegration" class="btn btn-primary w-full">
                                    Verbindung testen
                                </button>
                            @endif
                        </div>

                        {{-- Webhooks --}}
                        <div class="integration-card">
                            <div class="integration-header">
                                <div class="integration-info">
                                    <div class="integration-icon webhooks">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="integration-name">Webhooks</div>
                                        <div style="font-size: 0.75rem; color: var(--gray-500);">Ereignisse & API</div>
                                    </div>
                                </div>
                                <div class="integration-status {{ ($integrationStatus['webhooks']['recent_webhooks'] ?? 0) > 0 ? 'active' : 'inactive' }}"></div>
                            </div>
                            <p class="integration-message">
                                {{ $integrationStatus['webhooks']['recent_webhooks'] ?? 0 }} Webhooks in den letzten 24 Stunden
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Configuration Forms --}}
                <div class="form-section">
                    {{-- Cal.com Configuration --}}
                    <div class="form-card">
                        <h3 class="form-title">Cal.com Konfiguration</h3>
                        
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
                            
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="submit" class="btn btn-primary">
                                    Speichern
                                </button>
                                @if($integrationStatus['calcom']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testCalcomIntegration" 
                                            class="btn btn-secondary">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Retell.ai Configuration --}}
                    <div class="form-card">
                        <h3 class="form-title">Retell.ai Konfiguration</h3>
                        
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
                            
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="submit" class="btn btn-primary">
                                    Speichern
                                </button>
                                @if($integrationStatus['retell']['configured'] ?? false)
                                    <button type="button" 
                                            wire:click="testRetellIntegration" 
                                            class="btn btn-secondary">
                                        Testen
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- Empty State --}}
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="empty-title">Kein Unternehmen ausgewählt</h3>
                    <p class="empty-text">Wählen Sie ein Unternehmen aus der Liste oben aus, um fortzufahren.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>