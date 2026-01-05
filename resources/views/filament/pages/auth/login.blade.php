{{-- Single root element required by Livewire 3 --}}
<div class="askpro-login-root">
    {{-- Inline styles inside root element --}}
    <style>
        /* Full-page takeover - break out of Filament's container */
        .askpro-login-root {
            position: fixed;
            inset: 0;
            z-index: 50;
            overflow: hidden;
        }

        .askpro-login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Branding Panel (Left) */
        .askpro-branding-panel {
            display: none;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            padding: 3rem;
        }

        @media (min-width: 1024px) {
            .askpro-branding-panel {
                display: flex;
                width: 50%;
                align-items: center;
                justify-content: center;
            }
        }

        @media (min-width: 1280px) {
            .askpro-branding-panel {
                width: 60%;
            }
        }

        /* Background Effects */
        .askpro-bg-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .askpro-bg-blob-1 {
            position: absolute;
            top: 5rem;
            left: 2rem;
            width: 18rem;
            height: 18rem;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            filter: blur(48px);
            animation: pulse-slow 4s ease-in-out infinite;
        }

        .askpro-bg-blob-2 {
            position: absolute;
            bottom: 5rem;
            right: 2rem;
            width: 24rem;
            height: 24rem;
            background: rgba(251,146,60,0.2);
            border-radius: 50%;
            filter: blur(48px);
            animation: pulse-slower 6s ease-in-out infinite;
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.05); }
        }

        @keyframes pulse-slower {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.1); }
        }

        /* Branding Content */
        .askpro-branding-content {
            position: relative;
            z-index: 10;
            color: white;
            max-width: 32rem;
        }

        .askpro-logo-wrapper {
            margin-bottom: 3rem;
        }

        .askpro-logo {
            width: 4rem;
            height: 4rem;
            border-radius: 1rem;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .askpro-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        @media (min-width: 1280px) {
            .askpro-title { font-size: 3rem; }
        }

        .askpro-subtitle {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2.5rem;
            line-height: 1.75;
        }

        /* Features */
        .askpro-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .askpro-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255,255,255,0.9);
        }

        .askpro-feature-icon {
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .askpro-feature span {
            font-size: 1.125rem;
        }

        /* Stats */
        .askpro-stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .askpro-stat {
            text-align: center;
        }

        .askpro-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .askpro-stat-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.7);
        }

        .askpro-stat-divider {
            width: 1px;
            height: 2.5rem;
            background: rgba(255,255,255,0.2);
        }

        /* Form Panel (Right) */
        .askpro-form-panel {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: white;
        }

        /* Dark mode form panel */
        :root.dark .askpro-form-panel,
        .dark .askpro-form-panel,
        html.dark .askpro-form-panel {
            background: rgb(17 24 39) !important;
        }

        :root.dark .askpro-form-panel .fi-simple-page,
        html.dark .askpro-form-panel .fi-simple-page {
            color: #f9fafb;
        }

        @media (min-width: 1024px) {
            .askpro-form-panel {
                width: 50%;
                padding: 3rem;
            }
        }

        @media (min-width: 1280px) {
            .askpro-form-panel {
                width: 40%;
            }
        }

        /* Form Enhancements */
        .askpro-form-panel .fi-simple-page {
            max-width: 26rem;
            width: 100%;
            padding: 0;
        }

        .askpro-form-panel .fi-simple-main-ctn {
            min-height: auto;
            padding: 0;
        }

        .askpro-form-panel .fi-simple-page > div {
            background: transparent !important;
            box-shadow: none !important;
        }

        .askpro-form-panel .fi-input {
            border-radius: 0.75rem !important;
            border-width: 1.5px !important;
            transition: all 0.2s ease;
        }

        .askpro-form-panel .fi-input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
            border-color: rgb(245, 158, 11) !important;
        }

        .askpro-form-panel .fi-btn-primary {
            border-radius: 0.75rem !important;
            font-weight: 600 !important;
            padding: 0.875rem 1.5rem !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 14px 0 rgba(245, 158, 11, 0.3) !important;
        }

        .askpro-form-panel .fi-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px 0 rgba(245, 158, 11, 0.4) !important;
        }

        .askpro-form-panel .fi-checkbox-input:checked {
            background-color: rgb(245, 158, 11) !important;
            border-color: rgb(245, 158, 11) !important;
        }

        /* Form Footer */
        .askpro-form-footer {
            margin-top: 2rem;
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            .askpro-bg-blob-1, .askpro-bg-blob-2 {
                animation: none !important;
            }
        }
    </style>

    {{-- Custom Split-Panel Login Page --}}
    <div class="askpro-login-wrapper">
        {{-- Left Panel: Branding (Desktop Only) --}}
        <div class="askpro-branding-panel">
            {{-- Animated Background --}}
            <div class="askpro-bg-pattern"></div>
            <div class="askpro-bg-blob-1"></div>
            <div class="askpro-bg-blob-2"></div>

            {{-- Content --}}
            <div class="askpro-branding-content">
                {{-- Logo --}}
                <div class="askpro-logo-wrapper">
                    <div class="askpro-logo">
                        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                </div>

                <h1 class="askpro-title">AskProAI Gateway</h1>
                <p class="askpro-subtitle">{{ __('Intelligente Terminverwaltung mit KI-gestuetzter Sprachassistenz.') }}</p>

                {{-- Features --}}
                <div class="askpro-features">
                    <div class="askpro-feature">
                        <div class="askpro-feature-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <span>{{ __('Automatische Terminbuchung') }}</span>
                    </div>
                    <div class="askpro-feature">
                        <div class="askpro-feature-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <span>{{ __('24/7 KI-Telefonassistent') }}</span>
                    </div>
                    <div class="askpro-feature">
                        <div class="askpro-feature-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <span>{{ __('Echtzeit-Analytics') }}</span>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="askpro-stats">
                    <div class="askpro-stat">
                        <div class="askpro-stat-value">50+</div>
                        <div class="askpro-stat-label">{{ __('Unternehmen') }}</div>
                    </div>
                    <div class="askpro-stat-divider"></div>
                    <div class="askpro-stat">
                        <div class="askpro-stat-value">10K+</div>
                        <div class="askpro-stat-label">{{ __('Termine/Monat') }}</div>
                    </div>
                    <div class="askpro-stat-divider"></div>
                    <div class="askpro-stat">
                        <div class="askpro-stat-value">99.9%</div>
                        <div class="askpro-stat-label">{{ __('Uptime') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Login Form --}}
        <div class="askpro-form-panel">
            <x-filament-panels::page.simple>
                @if (filament()->hasRegistration())
                    <x-slot name="subheading">
                        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                        {{ $this->registerAction }}
                    </x-slot>
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

                {{-- Footer --}}
                <div class="askpro-form-footer">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        {{ __('Mit der Anmeldung akzeptieren Sie unsere') }}
                        <a href="#" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ __('Nutzungsbedingungen') }}</a>
                        {{ __('und') }}
                        <a href="#" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ __('Datenschutz') }}</a>.
                    </p>
                </div>
            </x-filament-panels::page.simple>
        </div>
    </div>
</div>
